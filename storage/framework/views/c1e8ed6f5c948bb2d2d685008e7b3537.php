<?php $__env->startSection('title', 'Forgot Password'); ?>
<?php $__env->startSection('body_class', 'auth-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="auth-shell compact-auth">
        <div class="auth-art">
            <img src="<?php echo e(asset('assets/produce-crates.png')); ?>" alt="Fresh produce ready for delivery" decoding="async">
            <div>
                <span class="eyebrow">Account recovery</span>
                <h1>Reset access without losing marketplace history.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="<?php echo e(route('password.email')); ?>" data-loading-form>
            <?php echo csrf_field(); ?>
            <div class="form-heading">
                <span class="eyebrow">Forgot password</span>
                <h2>Send a reset link</h2>
                <p>Enter the email on your FarmBridge account. We will send a secure reset link.</p>
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

            <button class="button primary full" type="submit" data-loading-label="Sending...">Send Reset Link</button>
            <p class="form-alt"><a href="<?php echo e(route('login')); ?>">Back to login</a></p>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/auth/forgot-password.blade.php ENDPATH**/ ?>