@props([
    'class' => ''
])

@php
$featureGridData = config('templates.bonsai.sections.feature_grid.data', [
    'sectionTitle' => 'Features',
    'subtitle' => 'Everything you need to build amazing WordPress sites',
    'features' => [],
    'sectionClasses' => 'py-24 bg-gray-50',
    'containerClasses' => 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
    'headerContainerClasses' => 'text-center mb-16',
    'titleClasses' => 'text-4xl font-bold text-gray-900 mb-4',
    'subtitleClasses' => 'text-xl text-gray-600',
    'gridContainerClasses' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8',
    'featureCardClasses' => 'bg-white rounded-lg shadow-sm p-8 hover:shadow-lg transition-shadow duration-300',
    'iconWrapperClasses' => 'flex items-center justify-center w-12 h-12 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 text-white mb-6',
    'iconClasses' => 'w-6 h-6',
    'featureTitleClasses' => 'text-xl font-semibold text-gray-900 mb-3',
    'featureDescriptionClasses' => 'text-gray-600'
]);
@endphp

<div class="{{ $class }}">
    <x-bonsai::feature-grid :data="$featureGridData" />
</div> 