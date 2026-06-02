<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Device;
use App\Models\Dispute;
use App\Models\EquipmentMaintenanceLog;
use App\Models\EscrowTransaction;
use App\Models\KycVerification;
use App\Models\ListingMedia;
use App\Models\MarketplaceItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Report;
use App\Models\SiteSetting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Rules\TrustedImageUrl;
use App\Services\GeoService;
use App\Services\ModerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const ROLES = [
        User::ROLE_ADMIN => 'Admin',
        User::ROLE_FARMER => 'Farmer',
        User::ROLE_SELLER => 'Seller',
        User::ROLE_BUYER => 'Buyer',
        User::ROLE_COOPERATIVE => 'Cooperative',
        User::ROLE_DRIVER => 'Driver / Logistics',
        User::ROLE_INSPECTOR => 'Inspector / Verifier',
    ];

    private const THEMES = [
        'harvest' => 'Harvest Green',
        'field' => 'Field Light',
        'sunset' => 'Market Sunset',
        'night' => 'Night Operations',
    ];

    private const RANGE_OPTIONS = [
        'today' => 'Today',
        '3' => 'Last 3 days',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        'all' => 'All time',
    ];

    private const ORDER_STATUSES = [
        Inquiry::STATUS_PENDING => 'Pending',
        Inquiry::STATUS_APPROVED => 'Approved',
        Inquiry::STATUS_FULFILLED => 'Fulfilled',
        Inquiry::STATUS_CANCELLED => 'Cancelled',
        Inquiry::STATUS_DISPUTED => 'Disputed',
        Inquiry::STATUS_EXPIRED => 'Expired',
    ];

    public function dashboard(Request $request, ModerationService $moderation): View
    {
        $this->authorizeAdmin();
        $moderation->flagListingsNeedingReview();

        [$range, $startDate, $rangeLabel] = $this->resolveRange($request);
        $previousStartDate = $this->previousStartDate($range, $startDate);

        $usersQuery = $this->period(User::query(), $startDate);
        $postsQuery = $this->period(MarketplaceItem::query(), $startDate);
        $ordersQuery = $this->period(Inquiry::query(), $startDate);
        $orders = (clone $ordersQuery)->with('marketplaceItem')->get();

        $stats = [
            'users' => (clone $usersQuery)->count(),
            'posts' => (clone $postsQuery)->count(),
            'orders' => (clone $ordersQuery)->count(),
            'pendingOrders' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'featuredPosts' => (clone $postsQuery)->where('is_featured', true)->count(),
            'availablePosts' => (clone $postsQuery)->where('is_available', true)->count(),
            'estimatedValue' => $orders->sum(fn (Inquiry $order): float => $this->orderValue($order)),
        ];
        $previousStats = $this->previousStats($range, $previousStartDate, $startDate);
        $trends = [
            'users' => $this->trendPercent($stats['users'], $previousStats['users']),
            'posts' => $this->trendPercent($stats['posts'], $previousStats['posts']),
            'orders' => $this->trendPercent($stats['orders'], $previousStats['orders']),
            'estimatedValue' => $this->trendPercent($stats['estimatedValue'], $previousStats['estimatedValue']),
        ];

        $chart = $this->activityChart($range, $startDate);
        $valueChart = $this->valueChart($range, $startDate);
        $roleCounts = $this->period(User::query(), $startDate)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');
        $statusCounts = $this->period(Inquiry::query(), $startDate)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $categoryCounts = $this->period(MarketplaceItem::query(), $startDate)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');
        $orderPivot = $this->orderPivot($startDate);
        $postPivot = $this->postPivot($startDate);

        $topLocations = MarketplaceItem::query()
            ->selectRaw('location, count(*) as total')
            ->groupBy('location')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'location');
        $locationDrilldown = $this->locationDrilldown();

        $moderationQueue = MarketplaceItem::query()
            ->with('owner')
            ->where(function (Builder $query): void {
                $query->whereNotNull('flagged_reason')
                    ->orWhere('moderation_status', 'pending')
                    ->orWhere('report_count', '>', 3);
            })
            ->orderByRaw("case when flagged_reason is not null then 0 else 1 end")
            ->orderByDesc('report_count')
            ->latest()
            ->take(4)
            ->get();
        $pendingOrders = Inquiry::query()
            ->with(['user', 'marketplaceItem'])
            ->where('status', 'pending')
            ->latest()
            ->take(4)
            ->get();
        $siteSettings = SiteSetting::allSettings();
        $systemHealth = $this->systemHealth();
        $opsReadiness = [
            'pendingReviews' => MarketplaceItem::query()->where('moderation_status', 'pending')->count(),
            'openReports' => Report::query()->where('status', 'open')->count(),
            'openDisputes' => Dispute::query()->where('status', 'open')->count(),
            'pendingPayouts' => Payout::query()->where('status', 'pending')->count(),
            'supportTickets' => SupportTicket::query()->whereIn('status', ['open', 'pending'])->count(),
            'maintenanceDue' => EquipmentMaintenanceLog::query()
                ->whereIn('status', ['scheduled', 'overdue'])
                ->whereDate('due_date', '<=', now()->addDays(7))
                ->count(),
            'unverifiedUsers' => User::query()->where('kyc_status', '!=', 'verified')->count(),
            'ordersInEscrow' => Order::query()->where('status', Order::STATUS_ESCROW_HELD)->count(),
            'heldEscrows' => EscrowTransaction::query()->where('status', 'held')->count(),
            'failedPayments' => Payment::query()->whereIn('status', ['failed', 'chargeback', 'requires_action'])->count(),
            'activeBookings' => Booking::query()->whereIn('status', Booking::RESERVING_STATUSES)->count(),
            'pendingKyc' => KycVerification::query()->where('status', 'pending')->count(),
            'failedWebhooks' => WebhookEvent::query()->where('status', 'failed')->count(),
            'riskyDevices' => Device::query()->where('risk_score', '>=', 60)->count(),
        ];
        $recentUsers = User::query()->latest()->take(5)->get();
        $recentOrders = Inquiry::query()->with(['user', 'marketplaceItem'])->latest()->take(5)->get();
        $recentPosts = MarketplaceItem::query()->with('owner')->latest()->take(5)->get();
        $recentAuditLogs = AuditLog::query()->with('user')->latest()->take(5)->get();

        return view('admin.dashboard', [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'rangeOptions' => self::RANGE_OPTIONS,
            'stats' => $stats,
            'trends' => $trends,
            'chart' => $chart,
            'valueChart' => $valueChart,
            'roleCounts' => $roleCounts,
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'orderPivot' => $orderPivot,
            'postPivot' => $postPivot,
            'orderStatuses' => self::ORDER_STATUSES,
            'topLocations' => $topLocations,
            'locationDrilldown' => $locationDrilldown,
            'moderationQueue' => $moderationQueue,
            'pendingOrders' => $pendingOrders,
            'siteSettings' => $siteSettings,
            'systemHealth' => $systemHealth,
            'opsReadiness' => $opsReadiness,
            'recentUsers' => $recentUsers,
            'recentOrders' => $recentOrders,
            'recentPosts' => $recentPosts,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    public function export(string $type): StreamedResponse
    {
        $this->authorizeAdmin();

        abort_unless(in_array($type, ['users', 'posts', 'orders', 'audit'], true), 404);
        $this->logAdminAction('exported', 'Exported '.$type.' CSV.');

        $filename = 'farmbridge-'.$type.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($type): void {
            $handle = fopen('php://output', 'w');

            if ($type === 'users') {
                fputcsv($handle, ['id', 'name', 'username', 'email', 'role', 'location', 'theme', 'posts', 'orders', 'created_at']);
                User::query()
                    ->withCount(['marketplaceItems', 'inquiries'])
                    ->orderBy('id')
                    ->each(function (User $user) use ($handle): void {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->username,
                            $user->email,
                            $user->role,
                            $user->location,
                            $user->theme,
                            $user->marketplace_items_count,
                            $user->inquiries_count,
                            $user->created_at,
                        ]);
                    });
            }

            if ($type === 'posts') {
                fputcsv($handle, ['id', 'title', 'owner', 'category', 'transaction_type', 'price_label', 'quantity', 'location', 'featured', 'available', 'created_at']);
                MarketplaceItem::query()
                    ->with('owner')
                    ->orderBy('id')
                    ->each(function (MarketplaceItem $post) use ($handle): void {
                        fputcsv($handle, [
                            $post->id,
                            $post->title,
                            $post->owner->email,
                            $post->category,
                            $post->transaction_type,
                            $post->priceLabel(),
                            $post->quantity,
                            $post->location,
                            $post->is_featured ? 'yes' : 'no',
                            $post->is_available ? 'yes' : 'no',
                            $post->created_at,
                        ]);
                    });
            }

            if ($type === 'orders') {
                fputcsv($handle, ['id', 'post', 'buyer', 'quantity', 'status', 'contact_email', 'start_date', 'end_date', 'estimated_value', 'created_at']);
                Inquiry::query()
                    ->with(['user', 'marketplaceItem'])
                    ->orderBy('id')
                    ->each(function (Inquiry $order) use ($handle): void {
                        fputcsv($handle, [
                            $order->id,
                            $order->marketplaceItem?->title,
                            $order->user?->email,
                            $order->quantity,
                            $order->status,
                            $order->contact_email,
                            optional($order->start_date)->toDateString(),
                            optional($order->end_date)->toDateString(),
                            $this->orderValue($order),
                            $order->created_at,
                        ]);
                    });
            }

            if ($type === 'audit') {
                fputcsv($handle, ['id', 'admin', 'action', 'subject_type', 'subject_id', 'summary', 'created_at']);
                AuditLog::query()
                    ->with('user')
                    ->orderByDesc('id')
                    ->each(function (AuditLog $log) use ($handle): void {
                        fputcsv($handle, [
                            $log->id,
                            $log->user?->email,
                            $log->action,
                            $log->subject_type,
                            $log->subject_id,
                            $log->summary,
                            $log->created_at,
                        ]);
                    });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function audit(Request $request): View
    {
        $this->authorizeAdmin();

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $query->where(function (Builder $nested) use ($request): void {
                    $nested->where('action', 'like', '%'.$request->q.'%')
                        ->orWhere('summary', 'like', '%'.$request->q.'%')
                        ->orWhere('subject_type', 'like', '%'.$request->q.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('email', 'like', '%'.$request->q.'%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }

    public function users(Request $request): View
    {
        $this->authorizeAdmin();

        $users = User::query()
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $query->where(function (Builder $nested) use ($request): void {
                    $nested->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('username', 'like', '%'.$request->q.'%')
                        ->orWhere('email', 'like', '%'.$request->q.'%')
                        ->orWhere('farm_name', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->role))
            ->when($request->filled('joined_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->joined_from))
            ->when($request->filled('joined_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->joined_to))
            ->when($request->filled('verification'), function (Builder $query) use ($request): void {
                if ($request->verification === 'verified') {
                    $query->whereNotNull('email_verified_at');
                }

                if ($request->verification === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when($request->filled('region'), fn (Builder $query) => $query->where('location', 'like', '%'.$request->region.'%'))
            ->withCount(['marketplaceItems', 'inquiries'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'regions' => User::query()->select('location')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function createUser(): View
    {
        $this->authorizeAdmin();

        return view('admin.users.form', [
            'managedUser' => new User(),
            'roles' => self::ROLES,
            'themes' => self::THEMES,
            'rangeOptions' => self::RANGE_OPTIONS,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $user = User::create($this->validateUser($request, true));
        $this->logAdminAction('created', 'Created user '.$user->email.'.', $user);

        return redirect()->route('admin.users.index')->with('status', 'User account created.');
    }

    public function editUser(User $user): View
    {
        $this->authorizeAdmin();

        return view('admin.users.form', [
            'managedUser' => $user,
            'roles' => self::ROLES,
            'themes' => self::THEMES,
            'rangeOptions' => self::RANGE_OPTIONS,
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $user->update($this->validateUser($request, false, $user));
        $this->logAdminAction('updated', 'Updated user '.$user->email.'.', $user);

        return redirect()->route('admin.users.index')->with('status', 'User account updated.');
    }

    public function bulkUsers(Request $request): RedirectResponse|StreamedResponse
    {
        $this->authorizeAdmin();

        $attributes = $request->validate([
            'action' => ['required', Rule::in(['delete', 'export'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = collect($attributes['ids'])->map(fn ($id): int => (int) $id)->unique();

        if ($attributes['action'] === 'export') {
            $this->logAdminAction('exported', 'Exported selected users.', null, ['ids' => $ids->values()->all()]);

            return $this->csvDownload('farmbridge-selected-users-'.now()->format('Y-m-d-His').'.csv', function ($handle) use ($ids): void {
                fputcsv($handle, ['id', 'name', 'username', 'email', 'role', 'location', 'theme', 'created_at']);
                User::query()->whereIn('id', $ids)->orderBy('id')->each(function (User $user) use ($handle): void {
                    fputcsv($handle, [$user->id, $user->name, $user->username, $user->email, $user->role, $user->location, $user->theme, $user->created_at]);
                });
            });
        }

        $deleteIds = $ids->reject(fn (int $id): bool => $id === Auth::id());
        $deleted = User::query()->whereIn('id', $deleteIds)->delete();
        $this->logAdminAction('bulk_deleted', 'Deleted '.$deleted.' selected users.', null, ['ids' => $deleteIds->values()->all()]);

        return back()->with('status', $deleted.' users deleted.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($user->is(Auth::user()) && $request->input('role') !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove admin access from the account you are currently using.']);
        }

        $attributes = $request->validate([
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        $user->update($attributes);
        $this->logAdminAction('role_changed', 'Changed '.$user->email.' role to '.$user->role.'.', $user);

        return back()->with('status', 'User role updated.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($user->is(Auth::user())) {
            return back()->withErrors(['user' => 'You cannot delete the admin account you are currently using.']);
        }

        $this->logAdminAction('deleted', 'Deleted user '.$user->email.'.', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User account deleted.');
    }

    public function posts(Request $request, ModerationService $moderation): View
    {
        $this->authorizeAdmin();
        $moderation->flagListingsNeedingReview();

        $posts = MarketplaceItem::query()
            ->with('owner')
            ->withCount('inquiries')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $query->where(function (Builder $nested) use ($request): void {
                    $nested->where('title', 'like', '%'.$request->q.'%')
                        ->orWhere('description', 'like', '%'.$request->q.'%')
                        ->orWhere('location', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->category))
            ->when($request->filled('transaction_type'), fn (Builder $query) => $query->where('transaction_type', $request->transaction_type))
            ->when($request->filled('availability'), fn (Builder $query) => $query->where('is_available', $request->availability === 'available'))
            ->when($request->filled('moderation_status'), fn (Builder $query) => $query->where('moderation_status', $request->moderation_status))
            ->orderByRaw("case when moderation_status = 'pending' then 0 when flagged_reason is not null then 1 else 2 end")
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.posts.index', compact('posts'));
    }

    public function createPost(): View
    {
        $this->authorizeAdmin();

        return view('admin.posts.form', [
            'post' => new MarketplaceItem(),
            'owners' => $this->ownersForSelect(),
        ]);
    }

    public function storePost(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $attributes = $this->validatePost($request);
        $uploadedPath = $this->storePostPhoto($request);

        if ($uploadedPath) {
            $attributes['image_url'] = Storage::url($uploadedPath);
        }

        $post = MarketplaceItem::create($attributes);
        $this->recordPostPhoto($post, $uploadedPath, $request);
        $this->logAdminAction('created', 'Created marketplace post '.$post->title.'.', $post);

        return redirect()->route('admin.posts.index')->with('status', 'Marketplace post created.');
    }

    public function editPost(MarketplaceItem $marketplaceItem): View
    {
        $this->authorizeAdmin();

        return view('admin.posts.form', [
            'post' => $marketplaceItem,
            'owners' => $this->ownersForSelect(),
        ]);
    }

    public function updatePost(Request $request, MarketplaceItem $marketplaceItem): RedirectResponse
    {
        $this->authorizeAdmin();

        $attributes = $this->validatePost($request);
        $uploadedPath = $this->storePostPhoto($request);

        if ($uploadedPath) {
            $attributes['image_url'] = Storage::url($uploadedPath);
        }

        $marketplaceItem->update($attributes);
        $this->recordPostPhoto($marketplaceItem, $uploadedPath, $request);
        $this->logAdminAction('updated', 'Updated marketplace post '.$marketplaceItem->title.'.', $marketplaceItem);

        return redirect()->route('admin.posts.index')->with('status', 'Marketplace post updated.');
    }

    public function moderatePost(Request $request, MarketplaceItem $marketplaceItem): RedirectResponse
    {
        $this->authorizeAdmin();

        $attributes = $request->validate([
            'action' => ['required', Rule::in(['feature', 'unfeature', 'show', 'hide', 'approve', 'reject'])],
        ]);

        match ($attributes['action']) {
            'feature' => $marketplaceItem->update(['is_featured' => true]),
            'unfeature' => $marketplaceItem->update(['is_featured' => false]),
            'show' => $marketplaceItem->update(['is_available' => true, 'lifecycle_status' => MarketplaceItem::LIFECYCLE_ACTIVE]),
            'hide' => $marketplaceItem->update(['is_available' => false, 'lifecycle_status' => MarketplaceItem::LIFECYCLE_HIDDEN]),
            'approve' => $marketplaceItem->update([
                'moderation_status' => 'approved',
                'lifecycle_status' => MarketplaceItem::LIFECYCLE_ACTIVE,
                'is_available' => true,
                'flagged_reason' => null,
                'published_at' => $marketplaceItem->published_at ?: now(),
            ]),
            'reject' => $marketplaceItem->update([
                'moderation_status' => 'rejected',
                'lifecycle_status' => MarketplaceItem::LIFECYCLE_REJECTED,
                'is_available' => false,
            ]),
        };
        $this->logAdminAction('moderated', ucfirst($attributes['action']).' post '.$marketplaceItem->title.'.', $marketplaceItem);

        return back()->with('status', 'Marketplace post moderation saved.');
    }

    public function destroyPost(MarketplaceItem $marketplaceItem): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->logAdminAction('deleted', 'Deleted marketplace post '.$marketplaceItem->title.'.', $marketplaceItem);
        $marketplaceItem->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Marketplace post deleted.');
    }

    public function orders(Request $request): View
    {
        $this->authorizeAdmin();

        $orders = Inquiry::query()
            ->with(['user', 'marketplaceItem.owner'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $query->where(function (Builder $nested) use ($request): void {
                    $nested->where('contact_email', 'like', '%'.$request->q.'%')
                        ->orWhere('message', 'like', '%'.$request->q.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$request->q.'%'))
                        ->orWhereHas('marketplaceItem', fn (Builder $postQuery) => $postQuery->where('title', 'like', '%'.$request->q.'%'));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => self::ORDER_STATUSES,
        ]);
    }

    public function bulkOrders(Request $request): RedirectResponse|StreamedResponse
    {
        $this->authorizeAdmin();

        $attributes = $request->validate([
            'action' => ['required', Rule::in(['approve', 'delete', 'export'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:inquiries,id'],
        ]);

        $ids = collect($attributes['ids'])->map(fn ($id): int => (int) $id)->unique();

        if ($attributes['action'] === 'export') {
            $this->logAdminAction('exported', 'Exported selected orders.', null, ['ids' => $ids->values()->all()]);

            return $this->csvDownload('farmbridge-selected-orders-'.now()->format('Y-m-d-His').'.csv', function ($handle) use ($ids): void {
                fputcsv($handle, ['id', 'post', 'buyer', 'quantity', 'status', 'contact_email', 'created_at']);
                Inquiry::query()->with(['marketplaceItem', 'user'])->whereIn('id', $ids)->orderBy('id')->each(function (Inquiry $order) use ($handle): void {
                    fputcsv($handle, [$order->id, $order->marketplaceItem?->title, $order->user?->email, $order->quantity, $order->status, $order->contact_email, $order->created_at]);
                });
            });
        }

        if ($attributes['action'] === 'approve') {
            $updated = Inquiry::query()->whereIn('id', $ids)->update(array_merge(
                ['status' => Inquiry::STATUS_APPROVED],
                $this->statusTimestamps(Inquiry::STATUS_APPROVED),
            ));
            $this->logAdminAction('bulk_approved', 'Approved '.$updated.' selected orders.', null, ['ids' => $ids->values()->all()]);

            return back()->with('status', $updated.' orders approved.');
        }

        $deleted = Inquiry::query()->whereIn('id', $ids)->delete();
        $this->logAdminAction('bulk_deleted', 'Deleted '.$deleted.' selected orders.', null, ['ids' => $ids->values()->all()]);

        return back()->with('status', $deleted.' orders deleted.');
    }

    public function createOrder(): View
    {
        $this->authorizeAdmin();

        return view('admin.orders.form', [
            'order' => new Inquiry(),
            'users' => User::query()->orderBy('name')->get(),
            'posts' => MarketplaceItem::query()->with('owner')->orderBy('title')->get(),
            'statuses' => self::ORDER_STATUSES,
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $order = Inquiry::create($this->validateOrder($request));
        $this->logAdminAction('created', 'Created order #'.$order->id.'.', $order);

        return redirect()->route('admin.orders.index')->with('status', 'Order created.');
    }

    public function editOrder(Inquiry $inquiry): View
    {
        $this->authorizeAdmin();

        return view('admin.orders.form', [
            'order' => $inquiry,
            'users' => User::query()->orderBy('name')->get(),
            'posts' => MarketplaceItem::query()->with('owner')->orderBy('title')->get(),
            'statuses' => self::ORDER_STATUSES,
        ]);
    }

    public function updateOrder(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeAdmin();

        $inquiry->update($this->validateOrder($request));
        $this->logAdminAction('updated', 'Updated order #'.$inquiry->id.'.', $inquiry);

        return redirect()->route('admin.orders.index')->with('status', 'Order updated.');
    }

    public function updateOrderStatus(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeAdmin();

        $attributes = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::ORDER_STATUSES))],
        ]);

        $inquiry->update(array_merge($attributes, $this->statusTimestamps($attributes['status'])));
        $this->logAdminAction('status_changed', 'Changed order #'.$inquiry->id.' to '.$inquiry->status.'.', $inquiry);

        return back()->with('status', 'Order status updated.');
    }

    public function destroyOrder(Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->logAdminAction('deleted', 'Deleted order #'.$inquiry->id.'.', $inquiry);
        $inquiry->delete();

        return redirect()->route('admin.orders.index')->with('status', 'Order deleted.');
    }

    public function editSettings(): View
    {
        $this->authorizeAdmin();

        return view('admin.settings.edit', [
            'admin' => Auth::user(),
            'themes' => self::THEMES,
            'rangeOptions' => self::RANGE_OPTIONS,
            'siteSettings' => SiteSetting::allSettings(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $admin = Auth::user();
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('users', 'username')->ignore($admin->id)],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($admin->id)],
            'farm_name' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:160'],
            'theme' => ['required', Rule::in(array_keys(self::THEMES))],
            'dashboard_range' => ['required', Rule::in($this->rangeKeys())],
            'profile_notes' => ['nullable', 'string', 'max:1200'],
            'notification_email' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'homepage_headline' => ['required', 'string', 'max:180'],
            'homepage_copy' => ['required', 'string', 'max:500'],
            'site_announcement' => ['nullable', 'string', 'max:240'],
            'marketplace_status' => ['required', Rule::in(['open', 'limited', 'paused'])],
            'maintenance_mode' => ['nullable', 'boolean'],
            'orders_enabled' => ['nullable', 'boolean'],
            'rentals_enabled' => ['nullable', 'boolean'],
            'global_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'listing_review_required' => ['nullable', 'boolean'],
            'max_active_inquiries_per_user' => ['required', 'integer', 'min:1', 'max:500'],
            'support_email' => ['required', 'email', 'max:160'],
            'flagged_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        $admin->fill([
            'name' => $attributes['name'],
            'username' => $attributes['username'] ?: null,
            'email' => $attributes['email'],
            'farm_name' => $attributes['farm_name'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'location' => $attributes['location'],
            'theme' => $attributes['theme'],
            'dashboard_range' => $attributes['dashboard_range'],
            'profile_notes' => $attributes['profile_notes'] ?? null,
            'notification_email' => $request->boolean('notification_email'),
        ]);

        if (! empty($attributes['password'])) {
            $admin->password = $attributes['password'];
        }

        $admin->save();

        SiteSetting::putMany([
            'homepage_headline' => $attributes['homepage_headline'],
            'homepage_copy' => $attributes['homepage_copy'],
            'site_announcement' => $attributes['site_announcement'] ?? null,
            'marketplace_status' => $attributes['marketplace_status'],
            'maintenance_mode' => $request->boolean('maintenance_mode') ? '1' : '0',
            'orders_enabled' => $request->boolean('orders_enabled') ? '1' : '0',
            'rentals_enabled' => $request->boolean('rentals_enabled') ? '1' : '0',
            'global_tax_rate' => (string) $attributes['global_tax_rate'],
            'platform_fee_rate' => (string) $attributes['platform_fee_rate'],
            'listing_review_required' => $request->boolean('listing_review_required') ? '1' : '0',
            'max_active_inquiries_per_user' => (string) $attributes['max_active_inquiries_per_user'],
            'support_email' => $attributes['support_email'],
            'flagged_keywords' => $attributes['flagged_keywords'] ?? '',
        ]);
        $this->logAdminAction('settings_updated', 'Updated admin profile and website settings.', $admin);

        return redirect()->route('admin.settings.edit')->with('status', 'Admin and website settings saved.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }

    private function validateUser(Request $request, bool $creating, ?User $user = null): array
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('users', 'username')->ignore($user?->id)],
            'farm_name' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:600'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:220'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'non_binary', 'prefer_not_to_say'])],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'theme' => ['required', Rule::in(array_keys(self::THEMES))],
            'dashboard_range' => ['required', Rule::in($this->rangeKeys())],
            'profile_notes' => ['nullable', 'string', 'max:1200'],
            'notification_email' => ['nullable', 'boolean'],
            'profile_visibility' => ['nullable', Rule::in(['marketplace', 'private'])],
            'share_location' => ['nullable', 'boolean'],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', 'min:8'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'closed'])],
            'kyc_status' => ['nullable', Rule::in(['unverified', 'pending', 'verified', 'rejected'])],
            'risk_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $attributes['username'] = $attributes['username'] ?: null;
        $attributes['notification_email'] = $request->boolean('notification_email');
        $attributes['share_location'] = $request->boolean('share_location');
        $attributes['profile_visibility'] = $attributes['profile_visibility'] ?? 'marketplace';
        $attributes['status'] = $attributes['status'] ?? 'active';
        $attributes['kyc_status'] = $attributes['kyc_status'] ?? 'unverified';
        $attributes['risk_score'] = $attributes['risk_score'] ?? 0;

        if (empty($attributes['password'])) {
            unset($attributes['password']);
        }

        return $attributes;
    }

    private function validatePost(Request $request): array
    {
        $attributes = $request->validate([
            'owner_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS])],
            'transaction_type' => ['required', Rule::in([MarketplaceItem::TRANSACTION_SALE, MarketplaceItem::TRANSACTION_RENT])],
            'price' => ['nullable', 'numeric', 'min:0', Rule::requiredIf($request->input('transaction_type') === MarketplaceItem::TRANSACTION_SALE)],
            'rent_rate' => ['nullable', 'numeric', 'min:0', Rule::requiredIf($request->input('transaction_type') === MarketplaceItem::TRANSACTION_RENT)],
            'unit' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:160'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'harvest_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:1800'],
            'product_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:500', new TrustedImageUrl()],
            'report_count' => ['nullable', 'integer', 'min:0'],
            'moderation_status' => ['nullable', Rule::in(['approved', 'pending', 'rejected'])],
            'lifecycle_status' => ['nullable', Rule::in([
                MarketplaceItem::LIFECYCLE_REVIEW,
                MarketplaceItem::LIFECYCLE_ACTIVE,
                MarketplaceItem::LIFECYCLE_HIDDEN,
                MarketplaceItem::LIFECYCLE_REJECTED,
            ])],
            'flagged_reason' => ['nullable', 'string', 'max:500'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'max_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'cancellation_policy' => ['nullable', Rule::in(['flexible', 'standard', 'strict'])],
            'is_featured' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
        ]);
        unset($attributes['product_photo']);

        $attributes['is_featured'] = $request->boolean('is_featured');
        $attributes['is_available'] = $request->boolean('is_available');
        $attributes['price'] = $attributes['transaction_type'] === MarketplaceItem::TRANSACTION_SALE ? $attributes['price'] : null;
        $attributes['rent_rate'] = $attributes['transaction_type'] === MarketplaceItem::TRANSACTION_RENT ? $attributes['rent_rate'] : null;
        $attributes['image_url'] = ($attributes['image_url'] ?? null) ?: $this->defaultImage($attributes['category']);
        $attributes['report_count'] = $attributes['report_count'] ?? 0;
        $attributes['moderation_status'] = $attributes['moderation_status'] ?? 'approved';
        $attributes['lifecycle_status'] = $attributes['lifecycle_status']
            ?? ($attributes['is_available'] && $attributes['moderation_status'] === 'approved'
                ? MarketplaceItem::LIFECYCLE_ACTIVE
                : MarketplaceItem::LIFECYCLE_REVIEW);
        $attributes['flagged_reason'] = $attributes['flagged_reason'] ?? null;
        $attributes['min_order_quantity'] = $attributes['min_order_quantity'] ?? 0.01;
        $attributes['max_order_quantity'] = $attributes['max_order_quantity'] ?? null;
        $attributes['deposit_amount'] = $attributes['deposit_amount'] ?? 0;
        $attributes['cancellation_policy'] = $attributes['cancellation_policy'] ?? 'standard';
        $attributes['published_at'] = $attributes['is_available'] && $attributes['moderation_status'] === 'approved'
            ? now()
            : null;
        $attributes['geo_hash'] = app(GeoService::class)->bucket(
            isset($attributes['latitude']) ? (float) $attributes['latitude'] : null,
            isset($attributes['longitude']) ? (float) $attributes['longitude'] : null,
        );
        $attributes['last_inventory_sync_at'] = now();

        return $attributes;
    }

    private function storePostPhoto(Request $request): ?string
    {
        if (! $request->hasFile('product_photo')) {
            return null;
        }

        return $request->file('product_photo')->store('listing-media/admin', 'public');
    }

    private function recordPostPhoto(MarketplaceItem $post, ?string $uploadedPath, Request $request): void
    {
        if (! $uploadedPath) {
            return;
        }

        $file = $request->file('product_photo');

        ListingMedia::create([
            'marketplace_item_id' => $post->id,
            'type' => 'image',
            'disk' => 'public',
            'path' => $uploadedPath,
            'url' => Storage::url($uploadedPath),
            'alt_text' => $post->title,
            'sort_order' => 0,
            'moderation_status' => $post->moderation_status,
            'metadata' => [
                'source' => 'admin_upload',
                'original_name' => $file?->getClientOriginalName(),
                'size' => $file?->getSize(),
                'mime' => $file?->getMimeType(),
            ],
        ]);
    }

    private function validateOrder(Request $request): array
    {
        $attributes = $request->validate([
            'marketplace_item_id' => ['required', 'exists:marketplace_items,id'],
            'user_id' => ['required', 'exists:users,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['required', 'email', 'max:160'],
            'message' => ['required', 'string', 'max:1200'],
            'status' => ['required', Rule::in(array_keys(self::ORDER_STATUSES))],
        ]);

        return array_merge($attributes, $this->statusTimestamps($attributes['status']));
    }

    private function statusTimestamps(string $status): array
    {
        return match ($status) {
            Inquiry::STATUS_APPROVED => ['seller_accepted_at' => now()],
            Inquiry::STATUS_FULFILLED => ['fulfilled_at' => now(), 'payment_status' => 'captured', 'escrow_status' => 'released'],
            Inquiry::STATUS_CANCELLED => ['cancelled_at' => now(), 'payment_status' => 'voided', 'escrow_status' => 'cancelled'],
            Inquiry::STATUS_DISPUTED => ['dispute_status' => 'open'],
            Inquiry::STATUS_EXPIRED => ['expires_at' => now(), 'payment_status' => 'expired', 'escrow_status' => 'expired'],
            default => [],
        };
    }

    private function ownersForSelect()
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'farm_name', 'email']);
    }

    private function defaultImage(string $category): string
    {
        return $category === MarketplaceItem::CATEGORY_EQUIPMENT
            ? '/assets/equipment-tractor.png'
            : '/assets/produce-crates.png';
    }

    private function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', Auth::user()->dashboard_range ?? '7');

        if (! array_key_exists($range, self::RANGE_OPTIONS)) {
            $range = '7';
        }

        $startDate = match ($range) {
            'today' => now()->startOfDay(),
            '3' => now()->subDays(2)->startOfDay(),
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            default => null,
        };

        return [$range, $startDate, self::RANGE_OPTIONS[$range]];
    }

    private function previousStartDate(string $range, ?Carbon $startDate): ?Carbon
    {
        if (! $startDate || $range === 'all') {
            return null;
        }

        return match ($range) {
            'today' => now()->subDay()->startOfDay(),
            '3' => now()->subDays(5)->startOfDay(),
            '7' => now()->subDays(13)->startOfDay(),
            '30' => now()->subDays(59)->startOfDay(),
            default => null,
        };
    }

    private function previousStats(string $range, ?Carbon $previousStartDate, ?Carbon $currentStartDate): array
    {
        if ($range === 'all' || ! $previousStartDate || ! $currentStartDate) {
            return [
                'users' => 0,
                'posts' => 0,
                'orders' => 0,
                'estimatedValue' => 0,
            ];
        }

        $orders = Inquiry::query()
            ->with('marketplaceItem')
            ->where('created_at', '>=', $previousStartDate)
            ->where('created_at', '<', $currentStartDate)
            ->get();

        return [
            'users' => User::query()->where('created_at', '>=', $previousStartDate)->where('created_at', '<', $currentStartDate)->count(),
            'posts' => MarketplaceItem::query()->where('created_at', '>=', $previousStartDate)->where('created_at', '<', $currentStartDate)->count(),
            'orders' => $orders->count(),
            'estimatedValue' => $orders->sum(fn (Inquiry $order): float => $this->orderValue($order)),
        ];
    }

    private function trendPercent(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function rangeKeys(): array
    {
        return array_map('strval', array_keys(self::RANGE_OPTIONS));
    }

    private function period(Builder $query, ?Carbon $startDate): Builder
    {
        return $query->when($startDate, function (Builder $periodQuery) use ($query, $startDate): void {
            $periodQuery->where($query->getModel()->getTable().'.created_at', '>=', $startDate);
        });
    }

    private function systemHealth(): array
    {
        $start = microtime(true);
        $database = 'connected';

        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $database = 'offline';
        }

        $dbLatency = round((microtime(true) - $start) * 1000, 1);
        $apiLatency = defined('LARAVEL_START')
            ? round((microtime(true) - LARAVEL_START) * 1000, 1)
            : $dbLatency;

        return [
            'database' => $database,
            'dbLatency' => $dbLatency,
            'apiLatency' => $apiLatency,
            'users' => User::query()->count(),
            'posts' => MarketplaceItem::query()->count(),
            'orders' => Inquiry::query()->count(),
        ];
    }

    private function activityChart(string $range, ?Carbon $startDate): array
    {
        $chartStart = $startDate ?: now()->subDays(29)->startOfDay();
        $days = match ($range) {
            'today' => 1,
            '3' => 3,
            '7' => 7,
            default => 30,
        };

        $users = $this->countsByDate(User::query(), $chartStart);
        $posts = $this->countsByDate(MarketplaceItem::query(), $chartStart);
        $orders = $this->countsByDate(Inquiry::query(), $chartStart);
        $rows = [];

        for ($index = $days - 1; $index >= 0; $index--) {
            $date = now()->subDays($index)->startOfDay();
            $key = $date->toDateString();

            $rows[] = [
                'label' => $date->format('M d'),
                'users' => $users[$key] ?? 0,
                'posts' => $posts[$key] ?? 0,
                'orders' => $orders[$key] ?? 0,
            ];
        }

        return $rows;
    }

    private function valueChart(string $range, ?Carbon $startDate): array
    {
        $chartStart = $startDate ?: now()->subDays(29)->startOfDay();
        $days = match ($range) {
            'today' => 1,
            '3' => 3,
            '7' => 7,
            default => 30,
        };

        $orders = Inquiry::query()
            ->with('marketplaceItem')
            ->where('created_at', '>=', $chartStart)
            ->whereIn('status', ['approved', 'fulfilled'])
            ->get()
            ->groupBy(fn (Inquiry $order): string => $order->created_at->toDateString())
            ->map(fn ($orders): float => $orders->sum(fn (Inquiry $order): float => $this->orderValue($order)))
            ->all();
        $rows = [];

        for ($index = $days - 1; $index >= 0; $index--) {
            $date = now()->subDays($index)->startOfDay();
            $key = $date->toDateString();

            $rows[] = [
                'label' => $date->format('M d'),
                'value' => round($orders[$key] ?? 0, 2),
            ];
        }

        return $rows;
    }

    private function countsByDate(Builder $query, Carbon $startDate): array
    {
        return $query
            ->where('created_at', '>=', $startDate)
            ->get(['created_at'])
            ->groupBy(fn ($record): string => $record->created_at->toDateString())
            ->map(fn ($records): int => $records->count())
            ->all();
    }

    private function orderValue(Inquiry $order): float
    {
        $item = $order->marketplaceItem;

        if (! $item || ! in_array($order->status, ['approved', 'fulfilled'], true)) {
            return 0;
        }

        $rate = $item->isRental() ? $item->rent_rate : $item->price;

        return (float) $order->quantity * (float) $rate;
    }

    private function locationDrilldown(): array
    {
        $posts = MarketplaceItem::query()
            ->select(['title', 'location'])
            ->orderBy('location')
            ->get();
        $total = max(1, $posts->count());

        return $posts
            ->groupBy('location')
            ->map(function ($items, string $location) use ($total): array {
                return [
                    'region' => $location,
                    'total' => $items->count(),
                    'percent' => round(($items->count() / $total) * 100),
                    'cities' => $items->map(fn (MarketplaceItem $post): array => [
                        'name' => $post->location,
                        'post' => $post->title,
                    ])->values()->all(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function orderPivot(?Carbon $startDate): array
    {
        $orders = $this->period(Inquiry::query()->with('marketplaceItem'), $startDate)->get();
        $rows = [];

        foreach ([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS] as $category) {
            $rows[$category] = array_fill_keys(array_keys(self::ORDER_STATUSES), 0);
            $rows[$category]['total'] = 0;
        }

        foreach ($orders as $order) {
            $category = $order->marketplaceItem?->category ?? MarketplaceItem::CATEGORY_GOODS;
            $status = array_key_exists($order->status, self::ORDER_STATUSES) ? $order->status : 'pending';
            $rows[$category][$status] += 1;
            $rows[$category]['total'] += 1;
        }

        return $rows;
    }

    private function postPivot(?Carbon $startDate): array
    {
        $posts = $this->period(MarketplaceItem::query(), $startDate)->get();
        $rows = [];

        foreach ([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS] as $category) {
            $rows[$category] = [
                MarketplaceItem::TRANSACTION_SALE => 0,
                MarketplaceItem::TRANSACTION_RENT => 0,
                'featured' => 0,
                'available' => 0,
                'total' => 0,
            ];
        }

        foreach ($posts as $post) {
            $category = $post->category;
            $rows[$category][$post->transaction_type] += 1;
            $rows[$category]['featured'] += $post->is_featured ? 1 : 0;
            $rows[$category]['available'] += $post->is_available ? 1 : 0;
            $rows[$category]['total'] += 1;
        }

        return $rows;
    }

    private function csvDownload(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer): void {
            $handle = fopen('php://output', 'w');
            $writer($handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function logAdminAction(string $action, string $summary, object|null $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'summary' => $summary,
            'metadata' => $metadata ?: null,
        ]);
    }
}
