<?php $__env->startSection('title', 'Sign Up'); ?>
<?php $__env->startSection('body_class', 'auth-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="auth-shell">
        <div class="auth-art">
            <img src="<?php echo e(asset('assets/farmer-market.png')); ?>" alt="Farmer preparing produce for market" decoding="async">
            <div>
                <span class="eyebrow">Join the network</span>
                <h1>Bring your equipment, goods, and local farm supply online.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="<?php echo e(route('signup.store')); ?>" data-signup-form data-loading-form>
            <?php echo csrf_field(); ?>
            <div class="form-heading">
                <span class="eyebrow">Sign up</span>
                <h2>Create your account</h2>
            </div>

            <?php if($errors->any()): ?>
                <div class="form-errors" role="alert" aria-live="assertive">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p><?php echo e($error); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <div class="form-grid">
                <label>
                    <span>Full name</span>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" autocomplete="name" required>
                </label>
                <label>
                    <span>Farm or business</span>
                    <input type="text" name="farm_name" value="<?php echo e(old('farm_name')); ?>">
                </label>
            </div>

            <label>
                <span>Email address</span>
                <input type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required>
            </label>

            <div class="form-grid">
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?php echo e(old('phone')); ?>" autocomplete="tel">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" value="<?php echo e(old('location')); ?>" required>
                </label>
            </div>

            <label>
                <span>Account type</span>
                <select name="role" required>
                    <option value="farmer" <?php if(old('role') === 'farmer'): echo 'selected'; endif; ?>>Farmer</option>
                    <option value="seller" <?php if(old('role') === 'seller'): echo 'selected'; endif; ?>>Seller</option>
                    <option value="buyer" <?php if(old('role') === 'buyer'): echo 'selected'; endif; ?>>Buyer</option>
                    <option value="cooperative" <?php if(old('role') === 'cooperative'): echo 'selected'; endif; ?>>Cooperative</option>
                </select>
            </label>

            <div class="form-grid">
                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="new-password" required data-password aria-describedby="password-hint">
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required data-password-confirm aria-describedby="password-hint">
                </label>
            </div>

            <p class="password-hint" id="password-hint" data-password-hint>Use at least 8 characters.</p>

            <button class="button primary full" type="submit" data-loading-label="Creating...">Create Account</button>
            <p class="form-alt">Already have an account? <a href="<?php echo e(route('login')); ?>">Login</a></p>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/auth/register.blade.php ENDPATH**/ ?>