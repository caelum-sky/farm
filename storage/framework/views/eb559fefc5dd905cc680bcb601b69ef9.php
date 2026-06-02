<?php
    $items = [
        [
            'label' => 'Overview',
            'route' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
            'icon' => 'M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-5H4v5Zm10-2h6v-5h-6v5Z',
        ],
        [
            'label' => 'Users',
            'route' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
            'icon' => 'M16 11a4 4 0 1 0-3.46-6A4 4 0 0 0 16 11ZM8 12a4 4 0 1 0-3.46-6A4 4 0 0 0 8 12Zm8 2c-3.31 0-6 1.57-6 3.5V20h12v-2.5c0-1.93-2.69-3.5-6-3.5ZM8 14c-3.31 0-6 1.57-6 3.5V20h6v-2.5c0-1.3.79-2.47 2.08-3.36A9.65 9.65 0 0 0 8 14Z',
        ],
        [
            'label' => 'Posts',
            'route' => route('admin.posts.index'),
            'active' => request()->routeIs('admin.posts.*'),
            'icon' => 'M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm2 4v3h10V8H7Zm0 6v2h6v-2H7Z',
        ],
        [
            'label' => 'Orders',
            'route' => route('admin.orders.index'),
            'active' => request()->routeIs('admin.orders.*'),
            'icon' => 'M6 3h12l2 4v14H4V7l2-4Zm1.24 2-1 2h11.52l-1-2H7.24ZM8 11h8v2H8v-2Zm0 4h6v2H8v-2Z',
        ],
        [
            'label' => 'Audit',
            'route' => route('admin.audit.index'),
            'active' => request()->routeIs('admin.audit.*'),
            'icon' => 'M12 2 4 5v6c0 5.05 3.41 9.74 8 11 4.59-1.26 8-5.95 8-11V5l-8-3Zm-1 14-4-4 1.41-1.41L11 13.17l5.59-5.58L18 9l-7 7Z',
        ],
        [
            'label' => 'Settings',
            'route' => route('admin.settings.edit'),
            'active' => request()->routeIs('admin.settings.*'),
            'icon' => 'M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65-2-3.46-2.49 1a7.2 7.2 0 0 0-1.7-.98L15 3.25h-4l-.35 2.68c-.6.24-1.17.56-1.7.98l-2.49-1-2 3.46 2.11 1.65c-.04.32-.07.65-.07.98s.02.66.07.98l-2.11 1.65 2 3.46 2.49-1c.53.41 1.1.74 1.7.98L11 20.75h4l.35-2.68c.6-.24 1.17-.56 1.7-.98l2.49 1 2-3.46-2.11-1.65ZM13 15.5A3.5 3.5 0 1 1 13 8a3.5 3.5 0 0 1 0 7.5Z',
        ],
    ];
?>

<nav class="admin-tabs" aria-label="Admin modules">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($item['route']); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $item['active']]); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
            <span class="admin-nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="<?php echo e($item['icon']); ?>" />
                </svg>
            </span>
            <span><?php echo e($item['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/components/admin/navigation.blade.php ENDPATH**/ ?>