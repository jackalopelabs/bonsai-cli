@props(['data' => []])

@php
    $title = $data['title'] ?? '';
    $link = $data['link'] ?? '';
    $imagePath = $data['imagePath'] ?? '';
    $buttonText = $data['buttonText'] ?? 'Learn More';
    
    // Get global styles from parent if available
    $globalStyles = $data['globalStyles'] ?? [];
    
    // Style classes from data with global fallback
    $containerClasses = $data['containerClasses'] ?? $globalStyles['containerClasses'] ?? 'cta bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-lg flex items-center space-x-8 mt-2';
    $imageClasses = $data['imageClasses'] ?? $globalStyles['imageClasses'] ?? 'w-44 h-44 object-cover rounded-xl';
    $contentContainerClasses = $data['contentContainerClasses'] ?? $globalStyles['contentContainerClasses'] ?? 'flex items-center justify-between flex-1';
    $titleClasses = $data['titleClasses'] ?? $globalStyles['titleClasses'] ?? 'text-xl font-semibold text-gray-900 dark:text-gray-100';
    $buttonClasses = $data['buttonClasses'] ?? $globalStyles['buttonClasses'] ?? 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-midnight-950 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-opacity-10';
    $buttonIconClasses = $data['buttonIconClasses'] ?? $globalStyles['buttonIconClasses'] ?? 'w-4 h-4 ml-2';
@endphp

<div class="{{ $containerClasses }}">
    <img src="{{ $imagePath }}" alt="{{ $title }}" class="{{ $imageClasses }}">
    <div class="{{ $contentContainerClasses }}">
        <h2 class="{{ $titleClasses }}">{{ $title }}</h2>
        <a href="{{ $link }}" class="{{ $buttonClasses }}">
            {{ $buttonText }}
            <x-heroicon-s-arrow-right class="{{ $buttonIconClasses }}" />
        </a>
    </div>
</div> 