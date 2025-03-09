@props(['data' => []])

@php
    $title = $data['title'] ?? '';
    $link = $data['link'] ?? '';
    $imagePath = $data['imagePath'] ?? '';
    $buttonText = $data['buttonText'] ?? 'Learn More';
    $useDarkMode = $data['useDarkMode'] ?? false;
    
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

<div class="{{ $containerClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.1);' : 'background-color: rgba(255, 255, 255, 0.5);'" @endif>
    <img src="{{ $imagePath }}" alt="{{ $title }}" class="{{ $imageClasses }}">
    <div class="{{ $contentContainerClasses }}">
        <h3 class="{{ $titleClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'color: #f3f4f6;' : 'color: #111827;'" @endif>{{ $title }}</h3>
        <a href="{{ $link }}" class="{{ $buttonClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'color: #d1d5db; background-color: #1f2937; border-color: #374151;' : 'color: #374151; background-color: white; border-color: #d1d5db;'" @endif>
            {{ $buttonText }}
            <x-heroicon-s-arrow-right class="{{ $buttonIconClasses }}" />
        </a>
    </div>
</div> 