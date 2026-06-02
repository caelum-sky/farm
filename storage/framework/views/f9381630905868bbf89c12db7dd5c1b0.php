<?php $__env->startSection('title', 'Manage Orders'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Order management</span>
                <h1>Inquiry orders, rental requests, buyer messages, and statuses.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="<?php echo e(route('admin.export', 'orders')); ?>">Export CSV</a>
                <a class="button primary" href="<?php echo e(route('admin.orders.create')); ?>">Create Order</a>
            </div>
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

        <form class="admin-filter" method="GET" action="<?php echo e(route('admin.orders.index')); ?>">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Buyer, email, post, message">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </label>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <form class="bulk-actions" method="POST" action="<?php echo e(route('admin.orders.bulk')); ?>" data-bulk-form data-confirm="Apply this bulk action to the selected orders?">
            <?php echo csrf_field(); ?>
            <label>
                <span>Bulk action</span>
                <select name="action" required>
                    <option value="approve">Approve selected</option>
                    <option value="export">Export selected</option>
                    <option value="delete">Delete selected</option>
                </select>
            </label>
            <div data-bulk-inputs></div>
            <button class="button primary" type="submit">Apply</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" data-bulk-select-all aria-label="Select all orders"></th>
                        <th>Order</th>
                        <th>Buyer</th>
                        <th>Post owner</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Select"><input type="checkbox" class="bulk-checkbox" value="<?php echo e($order->id); ?>" aria-label="Select order <?php echo e($order->id); ?>"></td>
                            <td data-label="Order">
                                <strong><?php echo e($order->marketplaceItem->title); ?></strong>
                                <span><?php echo e(\Illuminate\Support\Str::limit($order->message, 72)); ?></span>
                            </td>
                            <td data-label="Buyer">
                                <strong><?php echo e($order->user->name); ?></strong>
                                <span><?php echo e($order->contact_email); ?></span>
                            </td>
                            <td data-label="Post owner"><?php echo e($order->marketplaceItem->owner->name); ?></td>
                            <td data-label="Quantity"><?php echo e(number_format((float) $order->quantity, 2)); ?></td>
                            <td data-label="Status">
                                <span class="status-pill status-<?php echo e($order->status); ?>"><?php echo e(ucfirst($order->status)); ?></span>
                                <form class="quick-status-form" method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <select name="status" onchange="this.form.submit()" aria-label="Update order status">
                                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($value); ?>" <?php if($order->status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            </td>
                            <td data-label="Dates">
                                <?php echo e(optional($order->start_date)->format('M d') ?: 'Anytime'); ?>

                                <?php if($order->end_date): ?>
                                    - <?php echo e($order->end_date->format('M d')); ?>

                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="<?php echo e(route('admin.orders.edit', $order)); ?>">Edit</a>
                                    <form method="POST" action="<?php echo e(route('admin.orders.destroy', $order)); ?>" data-confirm="Delete this order?">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap"><?php echo e($orders->links()); ?></div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>