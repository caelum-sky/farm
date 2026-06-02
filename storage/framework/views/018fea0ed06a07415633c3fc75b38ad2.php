<?php $__env->startSection('title', 'Farm Marketplace'); ?>
<?php $__env->startSection('body_class', 'landing-page'); ?>

<?php $__env->startSection('content'); ?>
    <section class="hero-shell">
        <div class="hero-copy">
            <span class="eyebrow">Farm trade without the middle mile</span>
            <h1><?php echo e($siteSettings['homepage_headline']); ?></h1>
            <p><?php echo e($siteSettings['homepage_copy']); ?></p>
            <div class="hero-actions">
                <a class="button primary" href="<?php echo e(route('marketplace.index')); ?>">Explore Marketplace</a>
                <a class="button ghost" href="<?php echo e(route('signup')); ?>">Start Selling</a>
                <a class="button glass" href="<?php echo e(route('login')); ?>">Admin Login</a>
            </div>
            <div class="trust-strip" aria-label="FarmBridge marketplace stats">
                <span><strong data-count="420">0</strong> farm listings</span>
                <span><strong data-count="38">0</strong> regions served</span>
                <span><strong data-count="24">0</strong> hour response goal</span>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <img src="<?php echo e(asset('assets/hero-farm-market.png')); ?>" alt="" decoding="async">
            <div class="float-card float-card-a">
                <span>Rent</span>
                <strong>Harvester</strong>
                <small>$240 / day</small>
            </div>
            <div class="float-card float-card-b">
                <span>Fresh</span>
                <strong>Tomatoes</strong>
                <small>80 crates</small>
            </div>
        </div>
    </section>

    <?php if(! empty($siteSettings['site_announcement'])): ?>
        <section class="announcement-band">
            <span><?php echo e(ucfirst($siteSettings['marketplace_status'] ?? 'open')); ?></span>
            <p><?php echo e($siteSettings['site_announcement']); ?></p>
        </section>
    <?php endif; ?>

    <section class="admin-access-band">
        <div>
            <span class="eyebrow">Admin access</span>
            <h2>Run the marketplace from a seeded admin dashboard.</h2>
            <p>Use the admin account to review members, active listings, featured inventory, and pending inquiries.</p>
        </div>
        <a class="button primary" href="<?php echo e(route('login')); ?>">Open Admin Login</a>
    </section>

    <section class="category-band" aria-label="Marketplace categories">
        <article>
            <img src="<?php echo e(asset('assets/equipment-tractor.png')); ?>" alt="Tractor and farm tools" loading="lazy" decoding="async">
            <div>
                <span>Equipment</span>
                <h2>Rent or sell machines when nearby farms need them.</h2>
            </div>
        </article>
        <article>
            <img src="<?php echo e(asset('assets/produce-crates.png')); ?>" alt="Fresh harvested produce crates" loading="lazy" decoding="async">
            <div>
                <span>Farm Goods</span>
                <h2>Move fresh harvests faster from field to buyer.</h2>
            </div>
        </article>
        <article>
            <img src="<?php echo e(asset('assets/farmer-market.png')); ?>" alt="Farmer market delivery" loading="lazy" decoding="async">
            <div>
                <span>Local Deals</span>
                <h2>Coordinate orders, pickups, and seasonal supply.</h2>
            </div>
        </article>
    </section>

    <section class="market-section" id="marketplace">
        <div class="section-heading">
            <span class="eyebrow">Live marketplace</span>
            <h2>Featured farm listings</h2>
            <p>Filter by purpose and search what is ready for sale or rental.</p>
        </div>

        <div class="market-toolbar" data-filter-toolbar>
            <label>
                <span>Search</span>
                <input type="search" placeholder="Tomatoes, pump, tractor..." data-market-search>
            </label>
            <label>
                <span>Category</span>
                <select data-market-category>
                    <option value="all">All categories</option>
                    <option value="equipment">Equipment</option>
                    <option value="goods">Farm goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select data-market-type>
                    <option value="all">Sale and rent</option>
                    <option value="sale">For sale</option>
                    <option value="rent">For rent</option>
                </select>
            </label>
        </div>

        <div class="listing-grid" data-listing-grid aria-live="polite">
            <?php $__currentLoopData = $featured; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isRent = $item->transaction_type === 'rent';
                    $price = $isRent ? $item->rent_rate : $item->price;
                    $href = isset($item->id) ? route('marketplace.show', $item) : route('marketplace.index');
                ?>
                <article class="listing-card reveal"
                    data-category="<?php echo e($item->category); ?>"
                    data-type="<?php echo e($item->transaction_type); ?>"
                    data-title="<?php echo e(strtolower($item->title.' '.$item->description.' '.$item->location)); ?>">
                    <a href="<?php echo e($href); ?>" class="listing-image">
                        <img src="<?php echo e($item->image_url); ?>" alt="<?php echo e($item->title); ?>" loading="lazy" decoding="async">
                        <?php if (isset($component)) { $__componentOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf5a194c1ccdd1698e9a89f0cb5bf2c8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.status-badge','data' => ['status' => $isRent ? 'rent' : 'sale','label' => $isRent ? 'Rent' : 'Sale']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isRent ? 'rent' : 'sale'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isRent ? 'Rent' : 'Sale')]); ?>
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
                        <h3><a href="<?php echo e($href); ?>"><?php echo e($item->title); ?></a></h3>
                        <p><?php echo e(\Illuminate\Support\Str::limit($item->description, 108)); ?></p>
                        <div class="listing-foot">
                            <strong>$<?php echo e(number_format((float) $price, 2)); ?> / <?php echo e($item->unit); ?></strong>
                            <small><?php echo e(number_format((float) $item->quantity)); ?> available</small>
                        </div>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if (isset($component)) { $__componentOriginal3607a477fdef7402bc742abad5df9c51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3607a477fdef7402bc742abad5df9c51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.empty-state','data' => ['message' => 'No matching listings yet.','dataEmptyState' => true,'hidden' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['message' => 'No matching listings yet.','data-empty-state' => true,'hidden' => true]); ?>
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
    </section>

    <section class="seller-panel">
        <div>
            <span class="eyebrow">For farmers</span>
            <h2>Post harvests, equipment rentals, or tools for sale in minutes.</h2>
            <p>Create listings with quantity, price, location, condition, and rental dates. Buyers can send inquiries directly from each item page.</p>
        </div>
        <a class="button primary" href="<?php echo e(route('signup')); ?>">Create Account</a>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/landing.blade.php ENDPATH**/ ?>