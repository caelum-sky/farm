<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'FarmBridge'); ?> | FarmBridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/app.css'))); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/argon-dashboard.css')); ?>?v=<?php echo e(filemtime(public_path('assets/argon-dashboard.css'))); ?>">
</head>
<body class="<?php echo $__env->yieldContent('body_class'); ?> theme-<?php echo e(auth()->check() ? auth()->user()->theme : 'harvest'); ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="site-header" data-header>
        <a class="brand" href="<?php echo e(route('home')); ?>" aria-label="FarmBridge home">
            <span class="brand-mark">FB</span>
            <span>FarmBridge</span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-navigation" data-nav-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav" id="site-navigation" aria-label="Primary navigation" data-site-nav>
            <a href="<?php echo e(route('marketplace.index')); ?>" <?php if(request()->routeIs('marketplace.*')): ?> aria-current="page" <?php endif; ?>>Marketplace</a>
            <?php if(auth()->guard()->check()): ?>
                <?php if(auth()->user()->isAdmin()): ?>
                    <a class="nav-pill" href="<?php echo e(route('admin.dashboard')); ?>" <?php if(request()->routeIs('admin.*')): ?> aria-current="page" <?php endif; ?>>Admin Console</a>
                    <a href="<?php echo e(route('profile.edit')); ?>" <?php if(request()->routeIs('profile.*') || request()->routeIs('verification.*')): ?> aria-current="page" <?php endif; ?>>Profile</a>
                <?php else: ?>
                    <a href="<?php echo e(route('dashboard')); ?>" <?php if(request()->routeIs('dashboard')): ?> aria-current="page" <?php endif; ?>>Dashboard</a>
                    <a href="<?php echo e(route('profile.edit')); ?>" <?php if(request()->routeIs('profile.*') || request()->routeIs('verification.*')): ?> aria-current="page" <?php endif; ?>>Profile</a>
                    <?php if(auth()->user()->canCreateListings()): ?>
                        <a class="nav-pill" href="<?php echo e(route('marketplace.create')); ?>" <?php if(request()->routeIs('marketplace.create')): ?> aria-current="page" <?php endif; ?>>List Item</a>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="nav-link-button" type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" <?php if(request()->routeIs('login')): ?> aria-current="page" <?php endif; ?>>Login</a>
                <a class="nav-pill" href="<?php echo e(route('signup')); ?>" <?php if(request()->routeIs('signup')): ?> aria-current="page" <?php endif; ?>>Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if(session('status') || $errors->any()): ?>
        <div class="toast-stack" aria-live="polite">
            <?php if(session('status')): ?>
                <div class="flash" role="status"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="flash danger" role="alert" aria-live="assertive">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p><?php echo e($error); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <main id="main-content" tabindex="-1">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="site-footer">
        <div>
            <strong>FarmBridge</strong>
            <span>Equipment, harvests, and local farm trade in one place.</span>
        </div>
        <div class="footer-links">
            <a href="<?php echo e(route('marketplace.index')); ?>">Browse</a>
            <?php if(auth()->guard()->check()): ?>
                <?php if(auth()->user()->isAdmin()): ?>
                    <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a>
                    <a href="<?php echo e(route('admin.audit.index')); ?>">Audit</a>
                    <a href="<?php echo e(route('admin.settings.edit')); ?>">Settings</a>
                <?php elseif(auth()->user()->canCreateListings()): ?>
                    <a href="<?php echo e(route('marketplace.create')); ?>">Sell or Rent</a>
                <?php else: ?>
                    <a href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo e(route('profile.edit')); ?>">Profile</a>
            <?php else: ?>
                <a href="<?php echo e(route('signup')); ?>">Join</a>
            <?php endif; ?>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/app.js'))); ?>" defer></script>
</body>
</html>
<?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/layouts/app.blade.php ENDPATH**/ ?>