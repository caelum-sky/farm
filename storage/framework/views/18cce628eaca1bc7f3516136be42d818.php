<?php $__env->startSection('title', 'Admin Settings'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Admin settings</span>
                <h1>Profile, password, theme, and website controls.</h1>
            </div>
            <a class="button ghost" href="<?php echo e(route('admin.dashboard')); ?>">Back to Overview</a>
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

        <form class="admin-form settings-form" method="POST" action="<?php echo e(route('admin.settings.update')); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Account</span>
                    <h2>Admin identity</h2>
                </div>
                <div class="admin-form-grid">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="<?php echo e(old('name', $admin->name)); ?>" required>
                    </label>
                    <label>
                        <span>Username</span>
                        <input type="text" name="username" value="<?php echo e(old('username', $admin->username)); ?>">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo e(old('email', $admin->email)); ?>" required>
                    </label>
                    <label>
                        <span>Operations name</span>
                        <input type="text" name="farm_name" value="<?php echo e(old('farm_name', $admin->farm_name)); ?>">
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?php echo e(old('phone', $admin->phone)); ?>">
                    </label>
                    <label>
                        <span>Location</span>
                        <input type="text" name="location" value="<?php echo e(old('location', $admin->location)); ?>" required>
                    </label>
                    <label>
                        <span>Theme</span>
                        <select name="theme" data-theme-preview required>
                            <?php $__currentLoopData = $themes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('theme', $admin->theme ?: 'harvest') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <label>
                        <span>Default analytics range</span>
                        <select name="dashboard_range" required>
                            <?php $__currentLoopData = $rangeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if((string) old('dashboard_range', $admin->dashboard_range ?: '7') === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="notification_email" value="1" <?php if(old('notification_email', $admin->notification_email)): echo 'checked'; endif; ?>>
                        <span>Email notifications</span>
                    </label>
                </div>
                <label>
                    <span>Profile notes</span>
                    <textarea name="profile_notes" rows="4"><?php echo e(old('profile_notes', $admin->profile_notes)); ?></textarea>
                </label>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Security</span>
                    <h2>Change password</h2>
                </div>
                <div class="admin-form-grid">
                    <label>
                        <span>New password</span>
                        <input type="password" name="password" autocomplete="new-password">
                    </label>
                    <label>
                        <span>Confirm password</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                    </label>
                </div>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Website</span>
                    <h2>Landing page controls</h2>
                </div>
                <label>
                    <span>Homepage headline</span>
                    <input type="text" name="homepage_headline" value="<?php echo e(old('homepage_headline', $siteSettings['homepage_headline'])); ?>" required>
                </label>
                <label>
                    <span>Homepage copy</span>
                    <textarea name="homepage_copy" rows="4" required><?php echo e(old('homepage_copy', $siteSettings['homepage_copy'])); ?></textarea>
                </label>
                <div class="admin-form-grid">
                    <label>
                        <span>Announcement</span>
                        <input type="text" name="site_announcement" value="<?php echo e(old('site_announcement', $siteSettings['site_announcement'])); ?>">
                    </label>
                    <label>
                        <span>Marketplace status</span>
                        <select name="marketplace_status" required>
                            <option value="open" <?php if(old('marketplace_status', $siteSettings['marketplace_status']) === 'open'): echo 'selected'; endif; ?>>Open</option>
                            <option value="limited" <?php if(old('marketplace_status', $siteSettings['marketplace_status']) === 'limited'): echo 'selected'; endif; ?>>Limited</option>
                            <option value="paused" <?php if(old('marketplace_status', $siteSettings['marketplace_status']) === 'paused'): echo 'selected'; endif; ?>>Paused</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">System configuration</span>
                    <h2>Marketplace operations</h2>
                </div>
                <div class="admin-form-grid">
                    <label class="check-line">
                        <input type="checkbox" name="maintenance_mode" value="1" <?php if(old('maintenance_mode', $siteSettings['maintenance_mode']) === '1'): echo 'checked'; endif; ?>>
                        <span>Enable maintenance mode</span>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="orders_enabled" value="1" <?php if(old('orders_enabled', $siteSettings['orders_enabled']) === '1'): echo 'checked'; endif; ?>>
                        <span>Enable order inquiries</span>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="rentals_enabled" value="1" <?php if(old('rentals_enabled', $siteSettings['rentals_enabled']) === '1'): echo 'checked'; endif; ?>>
                        <span>Enable rentals</span>
                    </label>
                    <label>
                        <span>Global tax rate (%)</span>
                        <input type="number" name="global_tax_rate" min="0" max="100" step="0.01" value="<?php echo e(old('global_tax_rate', $siteSettings['global_tax_rate'])); ?>" required>
                    </label>
                    <label>
                        <span>Platform fee rate (%)</span>
                        <input type="number" name="platform_fee_rate" min="0" max="100" step="0.01" value="<?php echo e(old('platform_fee_rate', $siteSettings['platform_fee_rate'])); ?>" required>
                    </label>
                    <label>
                        <span>Max active requests per user</span>
                        <input type="number" name="max_active_inquiries_per_user" min="1" max="500" value="<?php echo e(old('max_active_inquiries_per_user', $siteSettings['max_active_inquiries_per_user'])); ?>" required>
                    </label>
                    <label>
                        <span>Contact email alias</span>
                        <input type="email" name="support_email" value="<?php echo e(old('support_email', $siteSettings['support_email'])); ?>" required>
                    </label>
                    <label>
                        <span>Auto-flag keywords</span>
                        <input type="text" name="flagged_keywords" value="<?php echo e(old('flagged_keywords', $siteSettings['flagged_keywords'])); ?>">
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="listing_review_required" value="1" <?php if(old('listing_review_required', $siteSettings['listing_review_required']) === '1'): echo 'checked'; endif; ?>>
                        <span>Require admin review before new user listings go live</span>
                    </label>
                </div>
            </section>

            <button class="button primary full" type="submit">Save Admin and Website Settings</button>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/settings/edit.blade.php ENDPATH**/ ?>