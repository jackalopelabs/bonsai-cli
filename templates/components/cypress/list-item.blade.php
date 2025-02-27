@props(['data' => []])

@php
  $number = $data['number'] ?? '';
  $itemName = $data['itemName'] ?? '';
  $text = $data['text'] ?? '';
  
  // Get global styles from parent if available
  $globalStyles = $data['globalStyles'] ?? [];
  
  // Style classes from data with global fallback
  $listItemClasses = $data['listItemClasses'] ?? $globalStyles['listItemClasses'] ?? 'flex items-start py-2';
  $numberClasses = $data['numberClasses'] ?? $globalStyles['numberClasses'] ?? 'flex-shrink-0 flex items-center justify-center text-white mr-4 bg-gray-600 dark:bg-gray-700 rounded-full w-8 h-8 text-sm';
  $contentClasses = $data['contentClasses'] ?? $globalStyles['contentClasses'] ?? '';
  $titleClasses = $data['titleClasses'] ?? $globalStyles['titleClasses'] ?? 'font-semibold dark:text-gray-200';
  $textClasses = $data['textClasses'] ?? $globalStyles['textClasses'] ?? 'text-sm text-gray-500 dark:text-gray-400';
@endphp

<li class="{{ $listItemClasses }}">
  <span class="{{ $numberClasses }}">{{ $number }}</span>
  <div class="{{ $contentClasses }}">
    <p class="{{ $titleClasses }}">{{ $itemName }}</p>
    @if($text)
      <p class="{{ $textClasses }}">{{ $text }}</p>
    @endif
  </div>
</li> 