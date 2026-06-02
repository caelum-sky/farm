<?php $__env->startSection('title', 'Manage Posts'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Post management</span>
                <h1>Listings, availability, featured placement, and user posts.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="<?php echo e(route('admin.export', 'posts')); ?>">Export CSV</a>
                <a class="button primary" href="<?php echo e(route('admin.posts.create')); ?>">Create Post</a>
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

        <form class="admin-filter" method="GET" action="<?php echo e(route('admin.posts.index')); ?>">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Title, location, description">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <option value="equipment" <?php if(request('category') === 'equipment'): echo 'selected'; endif; ?>>Equipment</option>
                    <option value="goods" <?php if(request('category') === 'goods'): echo 'selected'; endif; ?>>Goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select name="transaction_type">
                    <option value="">All modes</option>
                    <option value="sale" <?php if(request('transaction_type') === 'sale'): echo 'selected'; endif; ?>>Sale</option>
                    <option value="rent" <?php if(request('transaction_type') === 'rent'): echo 'selected'; endif; ?>>Rent</option>
                </select>
            </label>
            <label>
                <span>Availability</span>
                <select name="availability">
                    <option value="">Any</option>
                    <option value="available" <?php if(request('availability') === 'available'): echo 'selected'; endif; ?>>Available</option>
                    <option value="hidden" <?php if(request('availability') === 'hidden'): echo 'selected'; endif; ?>>Hidden</option>
                </select>
            </label>
            <label>
                <span>Moderation</span>
                <select name="moderation_status">
                    <option value="">Any status</option>
                    <option value="approved" <?php if(request('moderation_status') === 'approved'): echo 'selected'; endif; ?>>Approved</option>
                    <option value="pending" <?php if(request('moderation_status') === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                    <option value="rejected" <?php if(request('moderation_status') === 'rejected'): echo 'selected'; endif; ?>>Rejected</option>
                </select>
            </label>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Owner</th>
                        <th>Mode</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Flag reason</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Post">
                                <strong><?php echo e($post->title); ?></strong>
                                <span><?php echo e(ucfirst($post->category)); ?> - <?php echo e($post->location); ?></span>
                            </td>
                            <td data-label="Owner"><?php echo e($post->owner->name); ?></td>
                            <td data-label="Mode"><?php echo e(ucfirst($post->transaction_type)); ?></td>
                            <td data-label="Price"><?php echo e($post->priceLabel()); ?></td>
                            <td data-label="Status">
                                <span class="status-pill status-<?php echo e($post->moderation_status); ?>"><?php echo e(ucfirst($post->moderation_status)); ?></span>
                                <span class="status-pill <?php echo e($post->is_available ? 'is-good' : 'is-muted'); ?>"><?php echo e($post->is_available ? 'Available' : 'Hidden'); ?></span>
                                <?php if($post->is_featured): ?>
                                    <span class="status-pill is-warm">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Flag reason"><?php echo e($post->flagged_reason ?: 'None'); ?></td>
                            <td data-label="Orders"><?php echo e($post->inquiries_count); ?></td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="<?php echo e(route('admin.posts.edit', $post)); ?>">Edit</a>
                                    <a href="<?php echo e(route('marketplace.show', $post)); ?>">View</a>
                                    <button type="button"
                                        data-preview-post
                                        data-title="<?php echo e($post->title); ?>"
                                        data-owner="<?php echo e($post->owner->name); ?>"
                                        data-image="<?php echo e($post->image_url); ?>"
                                        data-price="<?php echo e($post->priceLabel()); ?>"
                                        data-location="<?php echo e($post->location); ?>"
                                        data-description="<?php echo e($post->description); ?>"
                                        data-reason="<?php echo e($post->flagged_reason ?: 'No flags'); ?>">Preview</button>
                                    <form method="POST" action="<?php echo e(route('admin.posts.moderate', $post)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="action" value="<?php echo e($post->is_featured ? 'unfeature' : 'feature'); ?>">
                                        <button type="submit"><?php echo e($post->is_featured ? 'Unfeature' : 'Feature'); ?></button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.posts.moderate', $post)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="action" value="<?php echo e($post->is_available ? 'hide' : 'show'); ?>">
                                        <button type="submit"><?php echo e($post->is_available ? 'Hide' : 'Show'); ?></button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.posts.destroy', $post)); ?>" data-confirm="Delete this marketplace post and its related orders?">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8">No posts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap"><?php echo e($posts->links()); ?></div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/posts/index.blade.php ENDPATH**/ ?>