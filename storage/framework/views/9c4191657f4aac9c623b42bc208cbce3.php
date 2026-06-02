<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'status',
    'label' => null,
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
    'status',
    'label' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    $normalized = strtolower(str_replace([' ', '_'], '-', (string) $status));
?>

<span <?php echo e($attributes->merge(['class' => 'status-badge status-'.$normalized])); ?>>
    <?php echo e($label ?? ucfirst(str_replace(['_', '-'], ' ', (string) $status))); ?>

</span>
<?php /**PATH C:\Users\Dagoo\Documents\Codex\2026-05-19\first-create-a-dynamic-responsive-website\resources\views/components/ui/status-badge.blade.php ENDPATH**/ ?>