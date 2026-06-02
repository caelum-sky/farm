<?php

namespace App\Services;

use App\Events\BookingRequested;
use App\Events\PaymentAuthorized;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\BookingLine;
use App\Models\EscrowTransaction;
use App\Models\Inquiry;
use App\Models\MarketplaceItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly WalletLedgerService $ledger)
    {
    }

    public function createCheckout(User $buyer, MarketplaceItem $listing, array $attributes): Order
    {
        return DB::transaction(function () use ($buyer, $listing, $attributes): Order {
            $settings = SiteSetting::allSettings();
            $lockedListing = MarketplaceItem::query()
                ->whereKey($listing->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCheckoutAllowed($buyer, $lockedListing, $attributes, $settings);

            $quantity = (float) $attributes['quantity'];
            $startDate = $attributes['start_date'] ?? null;
            $endDate = $attributes['end_date'] ?? null;
            $amounts = $this->quoteAmounts($lockedListing, $quantity, $startDate, $endDate, $settings);
            $seller = $lockedListing->owner;

            $inquiry = Inquiry::create([
                'type' => $lockedListing->isRental() ? 'booking_request' : 'purchase_request',
                'marketplace_item_id' => $lockedListing->id,
                'user_id' => $buyer->id,
                'message' => $attributes['message'] ?? 'Checkout request created.',
                'quantity' => $quantity,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'contact_phone' => $attributes['contact_phone'] ?? $buyer->phone,
                'contact_email' => $attributes['contact_email'] ?? $buyer->email,
                'status' => Inquiry::STATUS_PENDING,
                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_amount' => $amounts['tax_amount'],
                'platform_fee_amount' => $amounts['platform_fee_amount'],
                'escrow_amount' => $amounts['total_amount'],
                'payment_status' => 'authorized',
                'escrow_status' => 'held',
                'expires_at' => now()->addHours(48),
                'return_due_at' => $lockedListing->isRental() && $endDate ? Carbon::parse($endDate)->endOfDay() : null,
                'buyer_confirmed_at' => now(),
            ]);

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'invoice_number' => $this->invoiceNumber(),
                'buyer_id' => $buyer->id,
                'seller_id' => $seller?->id,
                'organization_id' => $seller?->organization_id,
                'source_inquiry_id' => $inquiry->id,
                'type' => $lockedListing->isRental() ? 'rental' : 'sale',
                'status' => Order::STATUS_ESCROW_HELD,
                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_amount' => $amounts['tax_amount'],
                'platform_fee_amount' => $amounts['platform_fee_amount'],
                'deposit_amount' => $amounts['deposit_amount'],
                'total_amount' => $amounts['total_amount'],
                'accepted_at' => now(),
                'confirmed_at' => now(),
                'invoice_issued_at' => now(),
                'refund_deadline_at' => now()->addDays(7),
            ]);

            $orderItem = $order->items()->create([
                'marketplace_item_id' => $lockedListing->id,
                'title' => $lockedListing->title,
                'item_type' => $lockedListing->transaction_type,
                'quantity' => $quantity,
                'unit_amount' => $lockedListing->isRental() ? $lockedListing->rent_rate : $lockedListing->price,
                'line_total_amount' => $amounts['subtotal_amount'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'metadata' => [
                    'unit' => $lockedListing->unit,
                    'location' => $lockedListing->location,
                    'cancellation_policy' => $lockedListing->cancellation_policy,
                ],
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'inquiry_id' => $inquiry->id,
                'user_id' => $buyer->id,
                'provider' => 'manual',
                'provider_reference' => 'AUTH-'.$order->order_number,
                'idempotency_key' => 'checkout:'.$order->order_number.':authorize',
                'status' => 'authorized',
                'amount' => $amounts['total_amount'],
                'currency' => 'USD',
                'metadata' => ['source' => 'farmbridge_checkout'],
                'authorized_at' => now(),
            ]);

            $escrow = EscrowTransaction::create([
                'order_id' => $order->id,
                'inquiry_id' => $inquiry->id,
                'payment_id' => $payment->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller?->id,
                'status' => 'held',
                'amount' => $amounts['total_amount'],
                'currency' => 'USD',
                'held_at' => now(),
                'notes' => 'Manual escrow hold recorded by FarmBridge checkout.',
            ]);

            if ($lockedListing->isRental()) {
                $booking = Booking::create([
                    'order_id' => $order->id,
                    'inquiry_id' => $inquiry->id,
                    'marketplace_item_id' => $lockedListing->id,
                    'renter_id' => $buyer->id,
                    'owner_id' => $seller?->id,
                    'status' => Booking::STATUS_REQUESTED,
                    'quantity' => $quantity,
                    'starts_at' => Carbon::parse($startDate)->startOfDay(),
                    'ends_at' => Carbon::parse($endDate)->endOfDay(),
                    'deposit_amount' => $amounts['deposit_amount'],
                ]);

                event(new BookingRequested($booking));

                BookingLine::create([
                    'booking_id' => $booking->id,
                    'order_item_id' => $orderItem->id,
                    'marketplace_item_id' => $lockedListing->id,
                    'status' => 'reserved',
                    'quantity' => $quantity,
                    'rate_amount' => $lockedListing->rent_rate,
                    'line_total_amount' => $amounts['subtotal_amount'],
                    'starts_at' => Carbon::parse($startDate)->startOfDay(),
                    'ends_at' => Carbon::parse($endDate)->endOfDay(),
                    'metadata' => ['days' => $amounts['days']],
                ]);
            } else {
                $lockedListing->increment('reserved_quantity', $quantity);
            }

            Shipment::create([
                'order_id' => $order->id,
                'inquiry_id' => $inquiry->id,
                'status' => 'pending',
                'delivery_method' => $attributes['delivery_method'] ?? 'pickup',
                'pickup_address' => $lockedListing->location,
                'dropoff_address' => $attributes['dropoff_address'] ?? $buyer->location,
                'pickup_latitude' => $lockedListing->latitude,
                'pickup_longitude' => $lockedListing->longitude,
            ]);

            if ($seller) {
                $sellerWallet = $this->ledger->ensureWallet($seller);
                $sellerPending = max(0, $amounts['subtotal_amount'] - $amounts['platform_fee_amount']);
                $this->ledger->creditPending($sellerWallet, $sellerPending, 'escrow_pending', $escrow, 'order:'.$order->id.':seller-pending', [
                    'order_number' => $order->order_number,
                    'buyer_id' => $buyer->id,
                ]);

                AppNotification::create([
                    'user_id' => $seller->id,
                    'type' => 'checkout.created',
                    'status' => 'pending',
                    'subject' => 'New escrow-funded request',
                    'body' => $buyer->name.' created '.$order->order_number.' for '.$lockedListing->title.'.',
                    'data' => ['order_id' => $order->id, 'inquiry_id' => $inquiry->id],
                ]);
            }

            AppNotification::create([
                'user_id' => $buyer->id,
                'type' => 'escrow.held',
                'status' => 'pending',
                'subject' => 'Escrow hold created',
                'body' => 'Your payment authorization is being held in escrow until fulfillment.',
                'data' => ['order_id' => $order->id, 'escrow_id' => $escrow->id],
            ]);

            event(new PaymentAuthorized($payment));

            return $order->fresh(['buyer', 'seller', 'items.listing', 'payments', 'bookings', 'escrowTransactions', 'shipments']);
        });
    }

    public function quoteAmounts(MarketplaceItem $listing, float $quantity, ?string $startDate, ?string $endDate, array $settings = []): array
    {
        $settings = $settings ?: SiteSetting::allSettings();
        $days = 1;

        if ($listing->isRental() && $startDate && $endDate) {
            $days = max(1, (int) Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
        }

        $rate = $listing->isRental() ? (float) $listing->rent_rate : (float) $listing->price;
        $subtotal = round($rate * $quantity * $days, 2);
        $tax = round($subtotal * ((float) ($settings['global_tax_rate'] ?? 0) / 100), 2);
        $fee = round($subtotal * ((float) ($settings['platform_fee_rate'] ?? 0) / 100), 2);
        $deposit = $listing->isRental() ? (float) $listing->deposit_amount : 0.0;

        return [
            'days' => $days,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'platform_fee_amount' => $fee,
            'deposit_amount' => $deposit,
            'total_amount' => round($subtotal + $tax + $fee + $deposit, 2),
        ];
    }

    private function assertCheckoutAllowed(User $buyer, MarketplaceItem $listing, array $attributes, array $settings): void
    {
        if (! $buyer->isActive()) {
            throw ValidationException::withMessages(['account' => 'Your account must be active before checkout.']);
        }

        if (! $listing->isPubliclyVisible()) {
            throw ValidationException::withMessages(['listing' => 'This listing is no longer available.']);
        }

        if ($listing->owner_id === $buyer->id) {
            throw ValidationException::withMessages(['listing' => 'You cannot checkout your own listing.']);
        }

        if ($settings['orders_enabled'] !== '1') {
            throw ValidationException::withMessages(['listing' => 'Orders and booking requests are temporarily paused.']);
        }

        if ($listing->isRental() && $settings['rentals_enabled'] !== '1') {
            throw ValidationException::withMessages(['listing' => 'Equipment rentals are temporarily paused.']);
        }

        $activeLimit = max(1, (int) ($settings['max_active_inquiries_per_user'] ?? 25));
        $activeCount = $buyer->inquiries()->whereIn('status', Inquiry::RESERVING_STATUSES)->count();

        if ($activeCount >= $activeLimit) {
            throw ValidationException::withMessages(['listing' => 'You have too many active requests. Resolve an existing request before creating another one.']);
        }

        $quantity = (float) $attributes['quantity'];
        $minimum = max(0.01, (float) $listing->min_order_quantity);
        $maximum = $listing->max_order_quantity ? (float) $listing->max_order_quantity : null;

        if ($quantity < $minimum) {
            throw ValidationException::withMessages(['quantity' => 'Minimum quantity is '.number_format($minimum, 2).' '.$listing->unit.'.']);
        }

        if ($maximum && $quantity > $maximum) {
            throw ValidationException::withMessages(['quantity' => 'Maximum quantity is '.number_format($maximum, 2).' '.$listing->unit.'.']);
        }

        if ($listing->isRental()) {
            if (empty($attributes['start_date']) || empty($attributes['end_date'])) {
                throw ValidationException::withMessages(['start_date' => 'Rental checkout requires start and end dates.']);
            }

            if ($listing->hasRentalConflict($attributes['start_date'], $attributes['end_date'], $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'Those rental dates are no longer available.']);
            }

            return;
        }

        if ($quantity > $listing->availableQuantity()) {
            throw ValidationException::withMessages([
                'quantity' => 'Only '.number_format($listing->availableQuantity(), 2).' '.$listing->unit.' remain available.',
            ]);
        }
    }

    private function orderNumber(): string
    {
        do {
            $number = 'FB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('invoice_number', $number)->exists());

        return $number;
    }
}
