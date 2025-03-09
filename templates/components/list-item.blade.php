@props(['data' => []])

@php
  $number = $data['number'] ?? '';
  $itemName = $data['itemName'] ?? '';
  $text = $data['text'] ?? '';
  $useDarkMode = $data['useDarkMode'] ?? false;
  
  // Get global styles from parent if available
  $globalStyles = $data['globalStyles'] ?? [];
  
  // Style classes from data with global fallback
  $listItemClasses = $data['listItemClasses'] ?? $globalStyles['listItemClasses'] ?? '';
  $numberClasses = $data['numberClasses'] ?? $globalStyles['numberClasses'] ?? '';
  $contentClasses = $data['contentClasses'] ?? $globalStyles['contentClasses'] ?? '';
  $titleClasses = $data['titleClasses'] ?? $globalStyles['titleClasses'] ?? '';
  $textClasses = $data['textClasses'] ?? $globalStyles['textClasses'] ?? '';
@endphp

<li class="{{ $listItemClasses }}">
  <span class="{{ $numberClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'background-color: #374151;' : 'background-color: #4b5563;'" @endif>{{ $number }}</span>
  <div class="{{ $contentClasses }}">
    <p class="{{ $titleClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'color: #e5e7eb;' : 'color: #1f2937;'" @endif>{{ $itemName }}</p>
    @if($text)
      <p class="{{ $textClasses }}" @if($useDarkMode) x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'" @endif>{{ $text }}</p>
    @endif
  </div>
</li> 