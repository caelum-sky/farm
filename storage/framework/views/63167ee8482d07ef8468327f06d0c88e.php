<?php $__env->startSection('title', 'Marketplace'); ?>
<?php $__env->startSection('body_class', 'marketplace-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-hero compact">
        <span class="eyebrow">Browse listings</span>
        <h1>Equipment rentals, equipment sales, and fresh farm goods.</h1>
        <p>Search local listings and contact the farmer or owner directly.</p>
    </section>

    <section class="market-section page-section">
        <form class="market-toolbar" method="GET" action="<?php echo e(route('marketplace.index')); ?>" data-loading-form>
            <label>
                <span>Search</span>
                <input type="search" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Search listing" autocomplete="off">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <option value="equipment" <?php if(($filters['category'] ?? '') === 'equipment'): echo 'selected'; endif; ?>>Equipment</option>
                    <option value="goods" <?php if(($filters['category'] ?? '') === 'goods'): echo 'selected'; endif; ?>>Farm goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select name="transaction_type">
                    <option value="">Sale and rent</option>
                    <option value="sale" <?php if(($filters['transaction_type'] ?? '') === 'sale'): echo 'selected'; endif; ?>>For sale</option>
                    <option value="rent" <?php if(($filters['transaction_type'] ?? '') === 'rent'): echo 'selected'; endif; ?>>For rent</option>
                </select>
            </label>
            <label>
                <span>Location</span>
                <input type="search" name="location" value="<?php echo e($filters['location'] ?? ''); ?>" placeholder="Province or city">
            </label>
            <label>
                <span>Sort</span>
                <select name="sort">
                    <option value="newest" <?php if(($filters['sort'] ?? 'newest') === 'newest'): echo 'selected'; endif; ?>>Newest</option>
                    <option value="price_low" <?php if(($filters['sort'] ?? '') === 'price_low'): echo 'selected'; endif; ?>>Price: low first</option>
                    <option value="price_high" <?php if(($filters['sort'] ?? '') === 'price_high'): echo 'selected'; endif; ?>>Price: high first</option>
                    <option value="availability" <?php if(($filters['sort'] ?? '') === 'availability'): echo 'selected'; endif; ?>>Most available</option>
                </select>
            </label>
            <button class="button primary" type="submit" data-loading-label="Filtering...">Filter</button>
        </form>

        <div class="result-summary" role="status">
            Showing <?php echo e($items->firstItem() ?? 0); ?>-<?php echo e($items->lastItem() ?? 0); ?> of <?php echo e($items->total()); ?> marketplace results.
        </div>

        <div class="skeleton-grid" aria-hidden="true" hidden data-skeleton>
            <?php for($index = 0; $index < 3; $index++): ?>
                <div class="skeleton-card"></div>
            <?php endfor; ?>
        </div>

        <div class="listing-grid" aria-live="polite">
            <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="listing-card reveal">
                    <a href="<?php echo e(route('marketplace.show', $item)); ?>" class="listing-image">
                        <img src="<?php echo e($item->image_url); ?>" alt="<?php echo e($item->title); ?>" loading="lazy" decoding="async">
                        <?php if (isset($component)) { $__componentOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.status-badge','data' => ['status' => $item->isRental() ? 'rent' : 'sale','label' => $item->isRental() ? 'Rent' : 'Sale']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->isRental() ? 'rent' : 'sale'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->isRental() ? 'Rent' : 'Sale')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8)): ?>
<?php $attributes = $__attributesOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8; ?>
<?php unset($__attributesOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8)): ?>
<?php $component = $__componentOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8; ?>
<?php unset($__componentOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8); ?>
<?php endif; ?>
                    </a>
                    <div class="listing-body">
                        <div class="listing-meta">
                            <span><?php echo e(ucfirst($item->category)); ?></span>
                            <span><?php echo e($item->location); ?></span>
                        </div>
                        <h3><a href="<?php echo e(route('marketplace.show', $item)); ?>"><?php echo e($item->title); ?></a></h3>
                        <p><?php echo e(\Illuminate\Support\Str::limit($item->description, 108)); ?></p>
                        <div class="listing-foot">
                            <strong><?php echo e($item->priceLabel()); ?></strong>
                            <small><?php echo e(number_format($item->availableQuantity(), 2)); ?> available</small>
                        </div>
                        <div class="trust-row" aria-label="Listing trust signals">
                            <span><?php echo e($item->owner?->kyc_status === 'verified' ? 'Verified seller' : 'Seller review pending'); ?></span>
                            <span>Score <?php echo e($item->listing_score ?? 50); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginal3607a477fdef7402bc742abad5df9c51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3607a477fdef7402bc742abad5df9c51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.empty-state','data' => ['message' => 'No listings matched your filters.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['message' => 'No listings matched your filters.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3607a477fdef7402bc742abad5df9c51)): ?>
<?php $attributes = $__attributesOriginal3607a477fdef7402bc742abad5df9c51; ?>
<?php unset($__attributesOriginal3607a477fdef7402bc742abad5df9c51); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3607a477fdef7402bc742abad5df9c51)): ?>
<?php $component = $__componentOriginal3607a477fdef7402bc742abad5df9c51; ?>
<?php unset($__componentOriginal3607a477fdef7402bc742abad5df9c51); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pagination-wrap">
            <?php echo e($items->links()); ?>

        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/marketplace/index.blade.php ENDPATH**/ ?>