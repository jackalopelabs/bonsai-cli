@props(['data' => []])

@php
  $title = $data['title'] ?? '';
  $link = $data['link'] ?? '#';
  $imagePath = $data['imagePath'] ?? '';
  $buttonText = $data['buttonText'] ?? 'Learn more';

  // Style classes
  $containerClasses = $data['containerClasses'] ?? 'cta bg-white bg-opacity-50 rounded-lg flex items-center space-x-8 mt-2';
  $imageClasses = $data['imageClasses'] ?? 'w-44 h-44 object-cover rounded-xl';
  $contentContainerClasses = $data['contentContainerClasses'] ?? 'flex items-center justify-between flex-1';
  $titleClasses = $data['titleClasses'] ?? 'text-xl font-semibold';
  $buttonClasses = $data['buttonClasses'] ?? 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50';
  $buttonIconClasses = $data['buttonIconClasses'] ?? 'w-4 h-4 ml-2';
@endphp

<div class="{{ $containerClasses }}">
    <!-- Image -->
    <img src="{{ $imagePath }}" alt="Feature illustration" class="{{ $imageClasses }}">

    <!-- Text content container -->
    <div class="{{ $contentContainerClasses }}">
        <!-- Title -->
        <h2 class="{{ $titleClasses }}">{!! $title !!}</h2>
        <!-- Button -->
        <a href="{{ $link }}" class="{{ $buttonClasses }}">
            {{ $buttonText }}
            <svg class="{{ $buttonIconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div> 