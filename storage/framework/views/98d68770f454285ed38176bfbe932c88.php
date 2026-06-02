<?php $__env->startSection('title', 'Login'); ?>
<?php $__env->startSection('body_class', 'auth-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="auth-shell">
        <div class="auth-art">
            <img src="<?php echo e(asset('assets/hero-farm-market.png')); ?>" alt="FarmBridge marketplace" decoding="async">
            <div>
                <span class="eyebrow">Welcome back</span>
                <h1>Manage listings, messages, rentals, and harvest orders.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="<?php echo e(route('login.store')); ?>" data-loading-form>
            <?php echo csrf_field(); ?>
            <div class="form-heading">
                <span class="eyebrow">Login</span>
                <h2>Open your FarmBridge account</h2>
            </div>

            <?php if($errors->any()): ?>
                <div class="form-errors" role="alert" aria-live="assertive">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p><?php echo e($error); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <label>
                <span>Email address</span>
                <input type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <label class="check-line">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>

            <button class="button primary full" type="submit" data-loading-label="Opening...">Login</button>
            <p class="form-alt">Forgot your password? <a href="<?php echo e(route('password.request')); ?>">Reset it</a></p>
            <p class="form-alt">New to FarmBridge? <a href="<?php echo e(route('signup')); ?>">Create an account</a></p>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/auth/login.blade.php ENDPATH**/ ?>