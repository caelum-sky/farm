<?php $__env->startSection('title', 'Manage Users'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">User management</span>
                <h1>Accounts, roles, themes, and access.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="<?php echo e(route('admin.export', 'users')); ?>">Export CSV</a>
                <a class="button primary" href="<?php echo e(route('admin.users.create')); ?>">Create User</a>
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

        <form class="admin-filter" method="GET" action="<?php echo e(route('admin.users.index')); ?>">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Name, username, email">
            </label>
            <label>
                <span>Role</span>
                <select name="role">
                    <option value="">All roles</option>
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(request('role') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </label>
            <button class="button ghost" type="button" data-filter-toggle>Advanced</button>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <aside class="filter-drawer" data-filter-drawer aria-hidden="true">
            <form method="GET" action="<?php echo e(route('admin.users.index')); ?>">
                <div class="drawer-heading">
                    <div>
                        <span class="eyebrow">Advanced filters</span>
                        <h2>User segments</h2>
                    </div>
                    <button type="button" data-filter-close aria-label="Close filters">Close</button>
                </div>
                <label>
                    <span>Date joined from</span>
                    <input type="date" name="joined_from" value="<?php echo e(request('joined_from')); ?>">
                </label>
                <label>
                    <span>Date joined to</span>
                    <input type="date" name="joined_to" value="<?php echo e(request('joined_to')); ?>">
                </label>
                <label>
                    <span>Verification status</span>
                    <select name="verification">
                        <option value="">Any status</option>
                        <option value="verified" <?php if(request('verification') === 'verified'): echo 'selected'; endif; ?>>Verified</option>
                        <option value="unverified" <?php if(request('verification') === 'unverified'): echo 'selected'; endif; ?>>Unverified</option>
                    </select>
                </label>
                <label>
                    <span>Region</span>
                    <select name="region">
                        <option value="">All regions</option>
                        <?php $__currentLoopData = $regions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($region); ?>" <?php if(request('region') === $region): echo 'selected'; endif; ?>><?php echo e($region); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
                <input type="hidden" name="q" value="<?php echo e(request('q')); ?>">
                <input type="hidden" name="role" value="<?php echo e(request('role')); ?>">
                <button class="button primary full" type="submit">Apply Advanced Filters</button>
            </form>
        </aside>

        <form class="bulk-actions" method="POST" action="<?php echo e(route('admin.users.bulk')); ?>" data-bulk-form data-confirm="Apply this bulk action to the selected users?">
            <?php echo csrf_field(); ?>
            <label>
                <span>Bulk action</span>
                <select name="action" required>
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
                        <th><input type="checkbox" data-bulk-select-all aria-label="Select all users"></th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Verification</th>
                        <th>Joined</th>
                        <th>Posts</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Select"><input type="checkbox" class="bulk-checkbox" value="<?php echo e($user->id); ?>" aria-label="Select <?php echo e($user->name); ?>"></td>
                            <td data-label="User">
                                <strong><?php echo e($user->name); ?></strong>
                                <span><?php echo e($user->username ? '@'.$user->username.' - ' : ''); ?><?php echo e($user->email); ?></span>
                            </td>
                            <td data-label="Role"><?php echo e(ucfirst($user->role)); ?></td>
                            <td data-label="Location"><?php echo e($user->location); ?></td>
                            <td data-label="Verification">
                                <span class="status-pill <?php echo e($user->email_verified_at ? 'is-good' : 'is-warm'); ?>"><?php echo e($user->email_verified_at ? 'Verified' : 'Unverified'); ?></span>
                            </td>
                            <td data-label="Joined"><?php echo e($user->created_at->format('M d, Y')); ?></td>
                            <td data-label="Posts"><?php echo e($user->marketplace_items_count); ?></td>
                            <td data-label="Orders"><?php echo e($user->inquiries_count); ?></td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="<?php echo e(route('admin.users.edit', $user)); ?>">Edit</a>
                                    <form method="POST" action="<?php echo e(route('admin.users.role', $user)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <select name="role" onchange="this.form.submit()" aria-label="Change role for <?php echo e($user->name); ?>">
                                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($value); ?>" <?php if($user->role === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" data-confirm="Delete this user and all related posts/orders?">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap"><?php echo e($users->links()); ?></div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/users/index.blade.php ENDPATH**/ ?>