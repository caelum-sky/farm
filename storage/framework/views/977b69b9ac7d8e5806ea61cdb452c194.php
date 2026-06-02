<?php $__env->startSection('title', 'Profile Settings'); ?>
<?php $__env->startSection('body_class', 'profile-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="dashboard-hero profile-hero">
        <div>
            <span class="eyebrow">Profile settings</span>
            <h1>Manage account, security, and preferences.</h1>
            <p>Keep verification, contact details, privacy, and marketplace settings current.</p>
            <div class="trust-row dashboard-role-tags" aria-label="Verification status">
                <span><?php echo e($user->hasVerifiedEmail() ? 'Email verified' : 'Email unverified'); ?></span>
                <span><?php echo e($user->phone_verified_at ? 'Phone verified' : 'Phone unverified'); ?></span>
                <span><?php echo e(ucfirst($user->theme)); ?> theme</span>
            </div>
        </div>
        <a class="button ghost" href="<?php echo e(route('dashboard')); ?>">Back to Dashboard</a>
    </section>

    <section class="profile-grid">
        <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel profile-card','title' => 'Profile details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel profile-card','title' => 'Profile details']); ?>
            <form method="POST" action="<?php echo e(route('profile.update')); ?>" data-loading-form>
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>

                <div class="profile-summary">
                    <div class="avatar-preview" aria-hidden="true">
                        <?php if($user->profile_picture): ?>
                            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($user->profile_picture)); ?>" alt="">
                        <?php else: ?>
                            <span><?php echo e(strtoupper(substr($user->name, 0, 1))); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?php echo e($user->name); ?></strong>
                        <span><?php echo e($user->email); ?></span>
                    </div>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Full name</span>
                        <input type="text" name="name" value="<?php echo e(old('name', $user->name)); ?>" autocomplete="name" required>
                    </label>
                    <label>
                        <span>Username</span>
                        <input type="text" name="username" value="<?php echo e(old('username', $user->username)); ?>" autocomplete="username" placeholder="farmbridge-user">
                    </label>
                </div>

                <label>
                    <span>Farm or business</span>
                    <input type="text" name="farm_name" value="<?php echo e(old('farm_name', $user->farm_name)); ?>">
                </label>

                <label>
                    <span>Bio</span>
                    <textarea name="bio" rows="4" maxlength="600" placeholder="Short marketplace profile for buyers and partners."><?php echo e(old('bio', $user->bio)); ?></textarea>
                </label>

                <div class="form-grid">
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" autocomplete="email" required>
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?php echo e(old('phone', $user->phone)); ?>" autocomplete="tel">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Location</span>
                        <input type="text" name="location" value="<?php echo e(old('location', $user->location)); ?>" autocomplete="address-level2" required>
                    </label>
                    <label>
                        <span>Address</span>
                        <input type="text" name="address" value="<?php echo e(old('address', $user->address)); ?>" autocomplete="street-address">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Gender</span>
                        <select name="gender">
                            <option value="">Prefer not to set</option>
                            <option value="female" <?php if(old('gender', $user->gender) === 'female'): echo 'selected'; endif; ?>>Female</option>
                            <option value="male" <?php if(old('gender', $user->gender) === 'male'): echo 'selected'; endif; ?>>Male</option>
                            <option value="non_binary" <?php if(old('gender', $user->gender) === 'non_binary'): echo 'selected'; endif; ?>>Non-binary</option>
                            <option value="prefer_not_to_say" <?php if(old('gender', $user->gender) === 'prefer_not_to_say'): echo 'selected'; endif; ?>>Prefer not to say</option>
                        </select>
                    </label>
                    <label>
                        <span>Birthdate</span>
                        <input type="date" name="birthdate" value="<?php echo e(old('birthdate', optional($user->birthdate)->format('Y-m-d'))); ?>">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Theme</span>
                        <select name="theme">
                            <?php $__currentLoopData = ['harvest' => 'Harvest', 'field' => 'Field', 'sunset' => 'Sunset', 'night' => 'Night']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('theme', $user->theme) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <label>
                        <span>Default analytics range</span>
                        <select name="dashboard_range">
                            <?php $__currentLoopData = ['today' => 'Today', '3' => '3 days', '7' => '7 days', '30' => '30 days', 'all' => 'All time']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if((string) old('dashboard_range', $user->dashboard_range ?: '7') === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Profile visibility</span>
                        <select name="profile_visibility">
                            <option value="marketplace" <?php if(old('profile_visibility', $user->profile_visibility) === 'marketplace'): echo 'selected'; endif; ?>>Marketplace visible</option>
                            <option value="private" <?php if(old('profile_visibility', $user->profile_visibility) === 'private'): echo 'selected'; endif; ?>>Private</option>
                        </select>
                    </label>
                    <div class="settings-toggles">
                        <label class="check-line">
                            <input type="checkbox" name="notification_email" value="1" <?php if(old('notification_email', $user->notification_email)): echo 'checked'; endif; ?>>
                            <span>Email notifications</span>
                        </label>
                        <label class="check-line">
                            <input type="checkbox" name="share_location" value="1" <?php if(old('share_location', $user->share_location ?? true)): echo 'checked'; endif; ?>>
                            <span>Share region on listings</span>
                        </label>
                    </div>
                </div>

                <button class="button primary full" type="submit" data-loading-label="Saving...">Save Profile</button>
            </form>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>

        <div class="profile-side">
            <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel','title' => 'Verification']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel','title' => 'Verification']); ?>
                <div class="security-stack">
                    <article>
                        <strong>Email</strong>
                        <span><?php echo e($user->hasVerifiedEmail() ? 'Verified' : 'Needs verification'); ?></span>
                        <?php if (! ($user->hasVerifiedEmail())): ?>
                            <form method="POST" action="<?php echo e(route('verification.send')); ?>" data-loading-form>
                                <?php echo csrf_field(); ?>
                                <button class="button ghost full" type="submit" data-loading-label="Sending...">Send Verification Email</button>
                            </form>
                        <?php endif; ?>
                    </article>
                    <article>
                        <strong>Phone OTP</strong>
                        <span><?php echo e($user->phone_verified_at ? 'Verified' : 'Not verified'); ?></span>
                        <form method="POST" action="<?php echo e(route('profile.phone.otp')); ?>" data-loading-form>
                            <?php echo csrf_field(); ?>
                            <button class="button ghost full" type="submit" data-loading-label="Sending...">Send OTP</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('profile.phone.verify')); ?>" data-loading-form>
                            <?php echo csrf_field(); ?>
                            <label>
                                <span>6-digit code</span>
                                <input type="text" name="otp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                            </label>
                            <button class="button primary full" type="submit" data-loading-label="Verifying...">Verify Phone</button>
                        </form>
                    </article>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel','title' => 'Profile picture']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel','title' => 'Profile picture']); ?>
                <form method="POST" action="<?php echo e(route('profile.avatar')); ?>" enctype="multipart/form-data" data-loading-form>
                    <?php echo csrf_field(); ?>
                    <label>
                        <span>Upload avatar</span>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required>
                    </label>
                    <p class="form-alt">PNG, JPG, or WebP up to 2 MB.</p>
                    <button class="button primary full" type="submit" data-loading-label="Uploading...">Upload Picture</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel','title' => 'Validated ID']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel','title' => 'Validated ID']); ?>
                <form method="POST" action="<?php echo e(route('profile.identity')); ?>" enctype="multipart/form-data" data-loading-form>
                    <?php echo csrf_field(); ?>
                    <label>
                        <span>ID type</span>
                        <select name="document_type" required>
                            <option value="national_id">National ID</option>
                            <option value="farm_registration">Farm registration</option>
                            <option value="business_registration">Business registration</option>
                            <option value="drivers_license">Driver's license</option>
                        </select>
                    </label>
                    <label>
                        <span>Validated ID file</span>
                        <input type="file" name="identity_document" accept="image/png,image/jpeg,image/webp,application/pdf" required>
                    </label>
                    <label>
                        <span>Review notes</span>
                        <textarea name="notes" rows="3" placeholder="Optional context for verification reviewers."><?php echo e(old('notes')); ?></textarea>
                    </label>
                    <p class="form-alt">Upload a clear ID or registration document. PDF, JPG, PNG, or WebP up to 5 MB.</p>
                    <button class="button primary full" type="submit" data-loading-label="Uploading...">Upload Validated ID</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel','title' => 'Change password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel','title' => 'Change password']); ?>
                <form method="POST" action="<?php echo e(route('profile.password')); ?>" data-signup-form data-loading-form>
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <label>
                        <span>Current password</span>
                        <input type="password" name="current_password" autocomplete="current-password" required>
                    </label>
                    <label>
                        <span>New password</span>
                        <input type="password" name="password" autocomplete="new-password" required data-password aria-describedby="profile-password-hint">
                    </label>
                    <label>
                        <span>Confirm password</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" required data-password-confirm aria-describedby="profile-password-hint">
                    </label>
                    <p class="password-hint" id="profile-password-hint" data-password-hint>Use at least 8 characters.</p>
                    <button class="button primary full" type="submit" data-loading-label="Updating...">Update Password</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal83da05d6671401f6161388117d6f2c2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83da05d6671401f6161388117d6f2c2d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.panel','data' => ['class' => 'dashboard-panel','title' => 'Session activity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'dashboard-panel','title' => 'Session activity']); ?>
                <div class="session-list">
                    <?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <article>
                            <strong><?php echo e($session['current'] ? 'Current session' : 'Recent session'); ?></strong>
                            <span><?php echo e($session['ip_address'] ?: 'Unknown IP'); ?></span>
                            <small><?php echo e(\Illuminate\Support\Str::limit($session['user_agent'] ?: 'Unknown device', 74)); ?></small>
                            <small><?php echo e($session['last_activity']->diffForHumans()); ?></small>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $attributes = $__attributesOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__attributesOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83da05d6671401f6161388117d6f2c2d)): ?>
<?php $component = $__componentOriginal83da05d6671401f6161388117d6f2c2d; ?>
<?php unset($__componentOriginal83da05d6671401f6161388117d6f2c2d); ?>
<?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/profile/edit.blade.php ENDPATH**/ ?>