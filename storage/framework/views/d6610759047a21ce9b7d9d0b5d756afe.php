<?php $__env->startSection('title', 'Admin Dashboard'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-hero">
            <div>
                <span class="eyebrow">Admin dashboard</span>
                <h1>Manage FarmBridge from one responsive console.</h1>
                <p>Track users, orders, marketplace posts, site settings, and operations health.</p>
            </div>
            <form class="admin-range" method="GET" action="<?php echo e(route('admin.dashboard')); ?>">
                <label>
                    <span>Analytics range</span>
                    <select name="range" onchange="this.form.submit()">
                        <?php $__currentLoopData = $rangeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if((string) $range === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
                <button class="button primary" type="submit">Apply</button>
            </form>
        </header>

        <?php if (isset($component)) { $__componentOriginalf8a7fa098c56a7855bbd274e8d57cf7b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8a7fa098c56a7855bbd274e8d57cf7b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.navigation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.navigation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8a7fa098c56a7855bbd274e8d57cf7b)): ?>
<?php $attributes = $__attributesOriginalf8a7fa098c56a7855bbd274e8d57cf7b; ?>
<?php unset($__attributesOriginalf8a7fa098c56a7855bbd274e8d57cf7b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8a7fa098c56a7855bbd274e8d57cf7b)): ?>
<?php $component = $__componentOriginalf8a7fa098c56a7855bbd274e8d57cf7b; ?>
<?php unset($__componentOriginalf8a7fa098c56a7855bbd274e8d57cf7b); ?>
<?php endif; ?>

        <section class="admin-command-bar">
            <div>
                <span class="status-pill status-<?php echo e($siteSettings['marketplace_status']); ?>"><?php echo e(ucfirst($siteSettings['marketplace_status'])); ?></span>
                <strong><?php echo e($siteSettings['site_announcement'] ?: 'FarmBridge operations are ready.'); ?></strong>
            </div>
            <div class="admin-export-actions">
                <a href="<?php echo e(route('admin.export', 'users')); ?>">Export Users</a>
                <a href="<?php echo e(route('admin.export', 'posts')); ?>">Export Posts</a>
                <a href="<?php echo e(route('admin.export', 'orders')); ?>">Export Orders</a>
                <a href="<?php echo e(route('admin.export', 'audit')); ?>">Export Audit</a>
            </div>
        </section>

        <section class="admin-kpi-grid" aria-label="<?php echo e($rangeLabel); ?> analytics">
            <article>
                <span>Users</span>
                <strong><?php echo e(number_format($stats['users'])); ?></strong>
                <small><?php echo e($rangeLabel); ?></small>
                <em class="trend-badge <?php echo e($trends['users'] === null || $trends['users'] >= 0 ? 'is-up' : 'is-down'); ?>">
                    <?php echo e($trends['users'] === null ? 'New' : (($trends['users'] >= 0 ? '+' : '').$trends['users'].'%')); ?>

                </em>
            </article>
            <article>
                <span>Posts</span>
                <strong><?php echo e(number_format($stats['posts'])); ?></strong>
                <small><?php echo e($stats['availablePosts']); ?> available</small>
                <em class="trend-badge <?php echo e($trends['posts'] === null || $trends['posts'] >= 0 ? 'is-up' : 'is-down'); ?>">
                    <?php echo e($trends['posts'] === null ? 'New' : (($trends['posts'] >= 0 ? '+' : '').$trends['posts'].'%')); ?>

                </em>
            </article>
            <article>
                <span>Orders</span>
                <strong><?php echo e(number_format($stats['orders'])); ?></strong>
                <small><?php echo e($stats['pendingOrders']); ?> pending</small>
                <em class="trend-badge <?php echo e($trends['orders'] === null || $trends['orders'] >= 0 ? 'is-up' : 'is-down'); ?>">
                    <?php echo e($trends['orders'] === null ? 'New' : (($trends['orders'] >= 0 ? '+' : '').$trends['orders'].'%')); ?>

                </em>
            </article>
            <article>
                <span>Estimated value</span>
                <strong>$<?php echo e(number_format($stats['estimatedValue'], 2)); ?></strong>
                <small>Approved and fulfilled</small>
                <em class="trend-badge <?php echo e($trends['estimatedValue'] === null || $trends['estimatedValue'] >= 0 ? 'is-up' : 'is-down'); ?>">
                    <?php echo e($trends['estimatedValue'] === null ? 'New' : (($trends['estimatedValue'] >= 0 ? '+' : '').$trends['estimatedValue'].'%')); ?>

                </em>
            </article>
        </section>

        <section class="admin-action-grid">
            <a href="<?php echo e(route('admin.users.create')); ?>">
                <span>Users</span>
                <strong>Create or edit accounts</strong>
            </a>
            <a href="<?php echo e(route('admin.posts.create')); ?>">
                <span>Posts</span>
                <strong>Publish or manage listings</strong>
            </a>
            <a href="<?php echo e(route('admin.orders.create')); ?>">
                <span>Orders</span>
                <strong>Create or update orders</strong>
            </a>
            <a href="<?php echo e(route('admin.settings.edit')); ?>">
                <span>Settings</span>
                <strong>Theme, password, site copy</strong>
            </a>
        </section>

        <section class="admin-analytics-grid">
            <article class="admin-panel wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Activity</span>
                        <h2>Orders vs revenue</h2>
                    </div>
                    <small><?php echo e($rangeLabel); ?></small>
                </div>
                <canvas class="admin-canvas tall" data-chart="multi-line"
                    data-labels='<?php echo json_encode(collect($chart)->pluck('label'), 15, 512) ?>'
                    data-orders='<?php echo json_encode(collect($chart)->pluck('orders'), 15, 512) ?>'
                    data-revenue='<?php echo json_encode(collect($valueChart)->pluck('value'), 15, 512) ?>'></canvas>
                <div class="chart-legend">
                    <span><i class="bar-orders"></i> Orders</span>
                    <span><i class="bar-users"></i> Revenue</span>
                </div>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Order mix</h2>
                    <a href="<?php echo e(route('admin.orders.index')); ?>">Manage</a>
                </div>
                <canvas class="admin-canvas" data-chart="doughnut"
                    data-labels='<?php echo json_encode($statusCounts->keys()->map(fn ($status) => ucfirst($status))->values(), 15, 512) ?>'
                    data-values='<?php echo json_encode($statusCounts->values(), 15, 512) ?>'></canvas>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Value trend</h2>
                    <small>Approved and fulfilled</small>
                </div>
                <canvas class="admin-canvas" data-chart="line"
                    data-labels='<?php echo json_encode(collect($valueChart)->pluck('label'), 15, 512) ?>'
                    data-values='<?php echo json_encode(collect($valueChart)->pluck('value'), 15, 512) ?>'></canvas>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Post categories</h2>
                    <a href="<?php echo e(route('admin.posts.index')); ?>">Manage</a>
                </div>
                <canvas class="admin-canvas" data-chart="doughnut"
                    data-labels='<?php echo json_encode($categoryCounts->keys()->map(fn ($category) => ucfirst($category))->values(), 15, 512) ?>'
                    data-values='<?php echo json_encode($categoryCounts->values(), 15, 512) ?>'></canvas>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">System health</span>
                        <h2>Server status</h2>
                    </div>
                </div>
                <div class="metric-list health-list">
                    <div>
                        <span>Database</span>
                        <strong><?php echo e(ucfirst($systemHealth['database'])); ?></strong>
                    </div>
                    <div>
                        <span>DB latency</span>
                        <strong><?php echo e($systemHealth['dbLatency']); ?>ms</strong>
                    </div>
                    <div>
                        <span>API latency</span>
                        <strong><?php echo e($systemHealth['apiLatency']); ?>ms</strong>
                    </div>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Risk ops</span>
                        <h2>Production queues</h2>
                    </div>
                </div>
                <div class="metric-list">
                    <div>
                        <span>Listing reviews</span>
                        <strong><?php echo e($opsReadiness['pendingReviews']); ?></strong>
                    </div>
                    <div>
                        <span>Open reports</span>
                        <strong><?php echo e($opsReadiness['openReports']); ?></strong>
                    </div>
                    <div>
                        <span>Disputes</span>
                        <strong><?php echo e($opsReadiness['openDisputes']); ?></strong>
                    </div>
                    <div>
                        <span>Pending payouts</span>
                        <strong><?php echo e($opsReadiness['pendingPayouts']); ?></strong>
                    </div>
                    <div>
                        <span>Support tickets</span>
                        <strong><?php echo e($opsReadiness['supportTickets']); ?></strong>
                    </div>
                    <div>
                        <span>Unverified users</span>
                        <strong><?php echo e($opsReadiness['unverifiedUsers']); ?></strong>
                    </div>
                    <div>
                        <span>Orders in escrow</span>
                        <strong><?php echo e($opsReadiness['ordersInEscrow']); ?></strong>
                    </div>
                    <div>
                        <span>Held escrows</span>
                        <strong><?php echo e($opsReadiness['heldEscrows']); ?></strong>
                    </div>
                    <div>
                        <span>Payment risks</span>
                        <strong><?php echo e($opsReadiness['failedPayments']); ?></strong>
                    </div>
                    <div>
                        <span>Active bookings</span>
                        <strong><?php echo e($opsReadiness['activeBookings']); ?></strong>
                    </div>
                    <div>
                        <span>Pending KYC</span>
                        <strong><?php echo e($opsReadiness['pendingKyc']); ?></strong>
                    </div>
                    <div>
                        <span>Webhook failures</span>
                        <strong><?php echo e($opsReadiness['failedWebhooks']); ?></strong>
                    </div>
                    <div>
                        <span>Risky devices</span>
                        <strong><?php echo e($opsReadiness['riskyDevices']); ?></strong>
                    </div>
                </div>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Location spread</h2>
                    <small>Listings by region</small>
                </div>
                <canvas class="admin-canvas" data-chart="bars"
                    data-labels='<?php echo json_encode($topLocations->keys()->values(), 15, 512) ?>'
                    data-values='<?php echo json_encode($topLocations->values(), 15, 512) ?>'></canvas>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Order status</h2>
                    <a href="<?php echo e(route('admin.orders.index')); ?>">Manage</a>
                </div>
                <div class="metric-list">
                    <?php $__empty_1 = true; $__currentLoopData = $statusCounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div>
                            <span><?php echo e(ucfirst($status)); ?></span>
                            <strong><?php echo e($total); ?></strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No orders in this range.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>User roles</h2>
                    <a href="<?php echo e(route('admin.users.index')); ?>">Manage</a>
                </div>
                <div class="metric-list">
                    <?php $__empty_1 = true; $__currentLoopData = $roleCounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div>
                            <span><?php echo e(ucfirst($role)); ?></span>
                            <strong><?php echo e($total); ?></strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No new users in this range.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Post categories</h2>
                    <a href="<?php echo e(route('admin.posts.index')); ?>">Manage</a>
                </div>
                <div class="metric-list">
                    <?php $__empty_1 = true; $__currentLoopData = $categoryCounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div>
                            <span><?php echo e(ucfirst($category)); ?></span>
                            <strong><?php echo e($total); ?></strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No posts in this range.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Top locations</h2>
                </div>
                <div class="location-drilldown">
                    <?php $__empty_1 = true; $__currentLoopData = $locationDrilldown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <details>
                            <summary>
                                <span><?php echo e($location['region']); ?></span>
                                <strong><?php echo e($location['total']); ?></strong>
                            </summary>
                            <i style="--progress: <?php echo e($location['percent']); ?>%"></i>
                            <div>
                                <?php $__currentLoopData = $location['cities']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <small><?php echo e($city['name']); ?> - <?php echo e($city['post']); ?></small>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </details>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No marketplace locations yet.</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="admin-pivot-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Pivot table</span>
                        <h2>Orders by category and status</h2>
                    </div>
                </div>
                <div class="admin-table-wrap compact">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th><?php echo e($label); ?></th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $orderPivot; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td data-label="Category"><?php echo e(ucfirst($category)); ?></td>
                                    <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <td data-label="<?php echo e($label); ?>"><?php echo e($row[$status]); ?></td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <td data-label="Total"><strong><?php echo e($row['total']); ?></strong></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Pivot table</span>
                        <h2>Posts by category and mode</h2>
                    </div>
                </div>
                <div class="admin-table-wrap compact">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Sale</th>
                                <th>Rent</th>
                                <th>Featured</th>
                                <th>Available</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $postPivot; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td data-label="Category"><?php echo e(ucfirst($category)); ?></td>
                                    <td data-label="Sale"><?php echo e($row['sale']); ?></td>
                                    <td data-label="Rent"><?php echo e($row['rent']); ?></td>
                                    <td data-label="Featured"><?php echo e($row['featured']); ?></td>
                                    <td data-label="Available"><?php echo e($row['available']); ?></td>
                                    <td data-label="Total"><strong><?php echo e($row['total']); ?></strong></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="admin-ops-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Moderation</span>
                        <h2>Posts needing attention</h2>
                    </div>
                    <a href="<?php echo e(route('admin.posts.index')); ?>">All posts</a>
                </div>
                <div class="admin-list action-list">
                    <?php $__empty_1 = true; $__currentLoopData = $moderationQueue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div>
                            <strong><?php echo e($post->title); ?></strong>
                            <span><?php echo e($post->owner->name); ?> - <?php echo e($post->is_available ? 'Visible' : 'Hidden'); ?> - <?php echo e($post->is_featured ? 'Featured' : 'Not featured'); ?></span>
                            <small>Reason: <?php echo e($post->flagged_reason ?: 'Needs admin review.'); ?></small>
                            <div class="admin-row-actions">
                                <form method="POST" action="<?php echo e(route('admin.posts.moderate', $post)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit">Approve</button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.posts.moderate', $post)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit">Reject</button>
                                </form>
                                <a href="<?php echo e(route('admin.posts.edit', $post)); ?>">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No moderation work right now.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Queue</span>
                        <h2>Pending orders</h2>
                    </div>
                    <a href="<?php echo e(route('admin.orders.index', ['status' => 'pending'])); ?>">All pending</a>
                </div>
                <div class="admin-list action-list">
                    <?php $__empty_1 = true; $__currentLoopData = $pendingOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div>
                            <strong><?php echo e($order->marketplaceItem->title); ?></strong>
                            <span><?php echo e($order->user->name); ?> - <?php echo e($order->contact_email); ?></span>
                            <form class="quick-status-form" method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <select name="status" onchange="this.form.submit()">
                                    <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($status); ?>" <?php if($order->status === $status): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </form>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No pending orders.</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="admin-recent-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent users</h2>
                    <a href="<?php echo e(route('admin.users.index')); ?>">View all</a>
                </div>
                <div class="admin-list">
                    <?php $__currentLoopData = $recentUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.users.edit', $user)); ?>">
                            <strong><?php echo e($user->name); ?></strong>
                            <span><?php echo e($user->email); ?> - <?php echo e(ucfirst($user->role)); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent posts</h2>
                    <a href="<?php echo e(route('admin.posts.index')); ?>">View all</a>
                </div>
                <div class="admin-list">
                    <?php $__currentLoopData = $recentPosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.posts.edit', $post)); ?>">
                            <strong><?php echo e($post->title); ?></strong>
                            <span><?php echo e($post->owner->name); ?> - <?php echo e($post->priceLabel()); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent orders</h2>
                    <a href="<?php echo e(route('admin.orders.index')); ?>">View all</a>
                </div>
                <div class="admin-list">
                    <?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.orders.edit', $order)); ?>">
                            <strong><?php echo e($order->marketplaceItem->title); ?></strong>
                            <span><?php echo e($order->user->name); ?> - <?php echo e(ucfirst($order->status)); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Audit trail</h2>
                    <a href="<?php echo e(route('admin.audit.index')); ?>">View all</a>
                </div>
                <div class="admin-list">
                    <?php $__currentLoopData = $recentAuditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.audit.index', ['q' => $log->action])); ?>">
                            <strong><?php echo e(ucfirst(str_replace('_', ' ', $log->action))); ?></strong>
                            <span><?php echo e($log->summary); ?> - <?php echo e(optional($log->created_at)->diffForHumans()); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>
        </section>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>