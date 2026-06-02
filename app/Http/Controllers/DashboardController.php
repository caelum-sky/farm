<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\EquipmentMaintenanceLog;
use App\Models\Inquiry;
use App\Models\InspectionReport;
use App\Models\MarketplaceItem;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\SavedSearch;
use App\Models\Shipment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        abort_unless($user->isActive(), 403);

        $listings = $user->marketplaceItems()
            ->latest()
            ->withCount('inquiries')
            ->get();

        $receivedInquiries = Inquiry::query()
            ->whereHas('marketplaceItem', fn ($query) => $query->where('owner_id', $user->id))
            ->with(['marketplaceItem', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $sentInquiries = $user->inquiries()
            ->with('marketplaceItem')
            ->latest()
            ->take(8)
            ->get();

        $roleCards = $this->roleCards($user);
        $quickActions = $this->quickActions($user);
        $roleAlerts = $this->roleAlerts($user);
        $roleProfile = $this->roleProfile($user);
        $recentOrders = Order::query()
            ->where(function ($query) use ($user): void {
                $query->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->with(['items', 'escrowTransactions', 'shipments'])
            ->latest()
            ->take(5)
            ->get();
        $recentNotifications = AppNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();

        return view('dashboard', compact(
            'user',
            'listings',
            'receivedInquiries',
            'sentInquiries',
            'roleCards',
            'quickActions',
            'roleAlerts',
            'roleProfile',
            'recentOrders',
            'recentNotifications',
        ));
    }

    private function roleProfile(User $user): array
    {
        $title = $user->farm_name ?: $user->name;

        return match ($user->role) {
            User::ROLE_FARMER, User::ROLE_SELLER => [
                'eyebrow' => 'Seller operations',
                'title' => $title,
                'description' => 'Manage listings, booking requests, inventory health, escrow, and upcoming maintenance from one mobile-ready workspace.',
            ],
            User::ROLE_BUYER => [
                'eyebrow' => 'Buyer workspace',
                'title' => $title,
                'description' => 'Search nearby supply, track protected requests, monitor escrow, and keep purchases moving from inquiry to delivery.',
            ],
            User::ROLE_COOPERATIVE => [
                'eyebrow' => 'Cooperative command center',
                'title' => $title,
                'description' => 'Coordinate pooled inventory, shared member requests, maintenance windows, and regional marketplace activity.',
            ],
            User::ROLE_DRIVER => [
                'eyebrow' => 'Logistics route board',
                'title' => $title,
                'description' => 'Track assigned deliveries, proof-of-pickup, proof-of-delivery, support follow-ups, and payout readiness.',
            ],
            User::ROLE_INSPECTOR => [
                'eyebrow' => 'Verification workspace',
                'title' => $title,
                'description' => 'Review inspection queues, evidence reports, listing risk signals, and operational support tickets.',
            ],
            default => [
                'eyebrow' => 'Dashboard',
                'title' => $title,
                'description' => ucfirst(str_replace('_', ' ', $user->role)).' workspace based in '.$user->location.'.',
            ],
        };
    }

    private function roleCards($user): array
    {
        $wallet = $user->wallet;
        $activeListings = $user->marketplaceItems()->where('is_available', true)->count();
        $receivedRequests = Inquiry::query()
            ->whereHas('marketplaceItem', fn ($query) => $query->where('owner_id', $user->id))
            ->whereIn('status', Inquiry::RESERVING_STATUSES)
            ->count();

        if (in_array($user->role, [User::ROLE_FARMER, User::ROLE_SELLER], true)) {
            return [
                ['label' => 'Available earnings', 'value' => '$'.number_format((float) ($wallet?->available_balance ?? 0), 2), 'hint' => 'Ready for payout'],
                ['label' => 'Pending escrow', 'value' => '$'.number_format((float) ($wallet?->pending_balance ?? 0), 2), 'hint' => 'Awaiting fulfillment'],
                ['label' => 'Active listings', 'value' => number_format($activeListings), 'hint' => 'Live marketplace posts'],
                ['label' => 'Booking requests', 'value' => number_format($receivedRequests), 'hint' => 'Needs response'],
            ];
        }

        if ($user->role === User::ROLE_BUYER) {
            return [
                ['label' => 'Active orders', 'value' => number_format($user->buyerOrders()->whereNotIn('status', ['fulfilled', 'cancelled'])->count()), 'hint' => 'Open purchases or rentals'],
                ['label' => 'Escrow tracking', 'value' => number_format($user->buyerOrders()->where('status', Order::STATUS_ESCROW_HELD)->count()), 'hint' => 'Funds protected'],
                ['label' => 'Saved searches', 'value' => number_format(SavedSearch::where('user_id', $user->id)->count()), 'hint' => 'Alerts and filters'],
                ['label' => 'Sent requests', 'value' => number_format($user->inquiries()->count()), 'hint' => 'Marketplace activity'],
            ];
        }

        if ($user->role === User::ROLE_COOPERATIVE) {
            return [
                ['label' => 'Pooled listings', 'value' => number_format($activeListings), 'hint' => 'Shared inventory'],
                ['label' => 'Member requests', 'value' => number_format($receivedRequests), 'hint' => 'Needs coordination'],
                ['label' => 'Pending escrow', 'value' => '$'.number_format((float) ($wallet?->pending_balance ?? 0), 2), 'hint' => 'Shared wallet'],
                ['label' => 'Maintenance due', 'value' => number_format(EquipmentMaintenanceLog::whereHas('marketplaceItem', fn ($query) => $query->where('owner_id', $user->id))->whereIn('status', ['scheduled', 'overdue'])->count()), 'hint' => 'Equipment readiness'],
            ];
        }

        if ($user->role === User::ROLE_DRIVER) {
            return [
                ['label' => 'Assigned deliveries', 'value' => number_format(Shipment::where('driver_id', $user->id)->whereNotIn('status', ['delivered', 'cancelled'])->count()), 'hint' => 'Route queue'],
                ['label' => 'Completed stops', 'value' => number_format(Shipment::where('driver_id', $user->id)->where('status', 'delivered')->count()), 'hint' => 'Proof of delivery'],
                ['label' => 'Open tickets', 'value' => number_format(SupportTicket::where('user_id', $user->id)->whereIn('status', ['open', 'pending'])->count()), 'hint' => 'Support follow-up'],
                ['label' => 'Wallet', 'value' => '$'.number_format((float) ($wallet?->available_balance ?? 0), 2), 'hint' => 'Driver payouts'],
            ];
        }

        if ($user->role === User::ROLE_INSPECTOR) {
            return [
                ['label' => 'Pending inspections', 'value' => number_format(InspectionReport::where('inspector_id', $user->id)->where('status', 'pending')->count()), 'hint' => 'Verification queue'],
                ['label' => 'Completed reports', 'value' => number_format(InspectionReport::where('inspector_id', $user->id)->where('status', 'completed')->count()), 'hint' => 'Evidence captured'],
                ['label' => 'Risk reviews', 'value' => number_format(MarketplaceItem::where('moderation_status', 'pending')->count()), 'hint' => 'Listings needing attention'],
                ['label' => 'Support tickets', 'value' => number_format(SupportTicket::whereIn('status', ['open', 'pending'])->count()), 'hint' => 'Operational follow-up'],
            ];
        }

        return [
            ['label' => 'Listings', 'value' => number_format($activeListings), 'hint' => 'Marketplace posts'],
            ['label' => 'Received inquiries', 'value' => number_format($receivedRequests), 'hint' => 'Needs response'],
            ['label' => 'Sent inquiries', 'value' => number_format($user->inquiries()->count()), 'hint' => 'Buyer activity'],
            ['label' => 'Wallet', 'value' => '$'.number_format((float) ($wallet?->available_balance ?? 0), 2), 'hint' => 'Available balance'],
        ];
    }

    private function quickActions($user): array
    {
        if ($user->role === User::ROLE_COOPERATIVE) {
            return [
                ['label' => 'Pooled inventory', 'href' => route('marketplace.create'), 'description' => 'Publish shared produce and equipment.'],
                ['label' => 'Member requests', 'href' => route('dashboard'), 'description' => 'Coordinate bookings and regional needs.'],
                ['label' => 'Marketplace map', 'href' => route('marketplace.index'), 'description' => 'Monitor nearby supply and demand.'],
            ];
        }

        if (in_array($user->role, User::SELLER_ROLES, true)) {
            return [
                ['label' => 'Create listing', 'href' => route('marketplace.create'), 'description' => 'Publish produce, equipment, or rentals.'],
                ['label' => 'Review requests', 'href' => route('dashboard'), 'description' => 'Accept, coordinate, or decline buyers.'],
                ['label' => 'Marketplace', 'href' => route('marketplace.index'), 'description' => 'Compare pricing and demand.'],
            ];
        }

        if ($user->role === User::ROLE_BUYER) {
            return [
                ['label' => 'Search nearby', 'href' => route('marketplace.index'), 'description' => 'Find equipment and harvest supply.'],
                ['label' => 'Track escrow', 'href' => route('dashboard'), 'description' => 'Follow orders from hold to release.'],
                ['label' => 'Reorder', 'href' => route('marketplace.index'), 'description' => 'Repeat common farm purchases.'],
            ];
        }

        if ($user->role === User::ROLE_DRIVER) {
            return [
                ['label' => 'Route queue', 'href' => route('dashboard'), 'description' => 'Review assigned pickups and deliveries.'],
                ['label' => 'Support desk', 'href' => route('dashboard'), 'description' => 'Check delivery blockers and tickets.'],
                ['label' => 'Marketplace context', 'href' => route('marketplace.index'), 'description' => 'View shipment origin listings.'],
            ];
        }

        if ($user->role === User::ROLE_INSPECTOR) {
            return [
                ['label' => 'Inspection queue', 'href' => route('dashboard'), 'description' => 'Review pending verification work.'],
                ['label' => 'Risk review', 'href' => route('marketplace.index'), 'description' => 'Check listings awaiting confidence signals.'],
                ['label' => 'Support context', 'href' => route('dashboard'), 'description' => 'Follow evidence and dispute needs.'],
            ];
        }

        return [
            ['label' => 'Open marketplace', 'href' => route('marketplace.index'), 'description' => 'Browse active farm supply.'],
            ['label' => 'Check dashboard', 'href' => route('dashboard'), 'description' => 'Review active work.'],
        ];
    }

    private function roleAlerts($user): array
    {
        $alerts = [];
        $receivedRequests = Inquiry::query()
            ->whereHas('marketplaceItem', fn ($query) => $query->where('owner_id', $user->id))
            ->whereIn('status', Inquiry::RESERVING_STATUSES)
            ->count();

        if ($user->kyc_status !== 'verified') {
            $alerts[] = ['title' => 'Verification incomplete', 'body' => 'Complete KYC before high-value payouts, rentals, and cooperative actions.'];
        }

        if (in_array($user->role, User::SELLER_ROLES, true)) {
            $maintenanceDue = EquipmentMaintenanceLog::whereHas('marketplaceItem', fn ($query) => $query->where('owner_id', $user->id))
                ->whereIn('status', ['scheduled', 'overdue'])
                ->whereDate('due_date', '<=', now()->addDays(7))
                ->count();

            if ($maintenanceDue > 0) {
                $alerts[] = ['title' => 'Maintenance window', 'body' => $maintenanceDue.' equipment maintenance tasks are due within 7 days.'];
            }
        }

        if ($user->role === User::ROLE_BUYER) {
            $activeBookings = Booking::where('renter_id', $user->id)->whereIn('status', Booking::RESERVING_STATUSES)->count();

            if ($activeBookings > 0) {
                $alerts[] = ['title' => 'Booking timeline active', 'body' => 'You have '.$activeBookings.' rental booking timelines to monitor.'];
            }
        }

        if ($user->role === User::ROLE_COOPERATIVE && $receivedRequests > 0) {
            $alerts[] = ['title' => 'Member coordination queue', 'body' => $receivedRequests.' shared inventory requests need assignment or response.'];
        }

        if ($user->role === User::ROLE_DRIVER) {
            $assignedShipments = Shipment::where('driver_id', $user->id)->whereNotIn('status', ['delivered', 'cancelled'])->count();

            if ($assignedShipments > 0) {
                $alerts[] = ['title' => 'Deliveries in progress', 'body' => $assignedShipments.' shipment stops need pickup, delivery, or proof updates.'];
            }
        }

        if ($user->role === User::ROLE_INSPECTOR) {
            $pendingInspections = InspectionReport::where('inspector_id', $user->id)->where('status', 'pending')->count();

            if ($pendingInspections > 0) {
                $alerts[] = ['title' => 'Inspection queue', 'body' => $pendingInspections.' equipment or listing inspections are awaiting review.'];
            }
        }

        return $alerts;
    }
}
