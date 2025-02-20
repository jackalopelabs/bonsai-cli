@props(['data' => []])

@php
    $title = $data['title'] ?? '';
    $link = $data['link'] ?? '';
    $imagePath = $data['imagePath'] ?? '';
    $buttonText = $data['buttonText'] ?? 'Learn More';
    
    // Get global styles from parent if available
    $globalStyles = $data['globalStyles'] ?? [];
    
    // Style classes from data with global fallback
    $containerClasses = $data['containerClasses'] ?? $globalStyles['containerClasses'] ?? '';
    $imageClasses = $data['imageClasses'] ?? $globalStyles['imageClasses'] ?? '';
    $contentContainerClasses = $data['contentContainerClasses'] ?? $globalStyles['contentContainerClasses'] ?? '';
    $titleClasses = $data['titleClasses'] ?? $globalStyles['titleClasses'] ?? '';
    $buttonClasses = $data['buttonClasses'] ?? $globalStyles['buttonClasses'] ?? '';
    $buttonIconClasses = $data['buttonIconClasses'] ?? $globalStyles['buttonIconClasses'] ?? '';
@endphp

<div class="{{ $containerClasses }}">
    <img src="{{ $imagePath }}" alt="{{ $title }}" class="{{ $imageClasses }}">
    <div class="{{ $contentContainerClasses }}">
        <h3 class="{{ $titleClasses }}">{{ $title }}</h3>
        <a href="{{ $link }}" class="{{ $buttonClasses }}">
            {{ $buttonText }}
            <x-heroicon-s-arrow-right class="{{ $buttonIconClasses }}" />
        </a>
    </div>
</div> 