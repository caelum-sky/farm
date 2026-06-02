<?php $__env->startSection('title', 'Audit Trail'); ?>
<?php $__env->startSection('body_class', 'admin-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Audit trail</span>
                <h1>Admin actions, exports, moderation, and settings changes.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="<?php echo e(route('admin.export', 'audit')); ?>">Export CSV</a>
                <a class="button primary" href="<?php echo e(route('admin.dashboard')); ?>">Overview</a>
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

        <form class="admin-filter compact-filter" method="GET" action="<?php echo e(route('admin.audit.index')); ?>">
            <label>
                <span>Search audit trail</span>
                <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Action, admin, model, summary">
            </label>
            <button class="button primary" type="submit">Search</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Admin</th>
                        <th>Subject</th>
                        <th>Summary</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Action"><span class="status-pill is-good"><?php echo e(str_replace('_', ' ', $log->action)); ?></span></td>
                            <td data-label="Admin"><?php echo e($log->user?->email ?: 'System'); ?></td>
                            <td data-label="Subject">
                                <strong><?php echo e(class_basename($log->subject_type ?: 'System')); ?></strong>
                                <span><?php echo e($log->subject_id ? '#'.$log->subject_id : 'Global'); ?></span>
                            </td>
                            <td data-label="Summary"><?php echo e($log->summary); ?></td>
                            <td data-label="When"><?php echo e($log->created_at->format('M d, Y g:i A')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5">No audit entries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap"><?php echo e($logs->links()); ?></div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/admin/audit/index.blade.php ENDPATH**/ ?>