@props([
    'class' => ''
])

@php
$featureGridData = [
    'sectionTitle' => 'Features',
    'subtitle' => 'Everything you need to build amazing WordPress sites',
    // ... other data from your YAML
];
@endphp

<div class="{{ $class }}">
    <x-bonsai::feature-grid :data="$featureGridData" />
</div> 