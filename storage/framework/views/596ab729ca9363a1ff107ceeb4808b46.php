<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'eyebrow' => null,
    'actionHref' => null,
    'actionLabel' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => null,
    'eyebrow' => null,
    'actionHref' => null,
    'actionLabel' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<section <?php echo e($attributes->merge(['class' => 'ui-panel'])); ?>>
    <?php if($title || $eyebrow || $actionHref): ?>
        <div class="panel-heading">
            <div>
                <?php if($eyebrow): ?>
                    <span class="eyebrow"><?php echo e($eyebrow); ?></span>
                <?php endif; ?>
                <?php if($title): ?>
                    <h2><?php echo e($title); ?></h2>
                <?php endif; ?>
            </div>
            <?php if($actionHref && $actionLabel): ?>
                <a href="<?php echo e($actionHref); ?>"><?php echo e($actionLabel); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php echo e($slot); ?>

</section>
<?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/components/ui/panel.blade.php ENDPATH**/ ?>