@props(['data' => []])

@php
  $number = $data['number'] ?? '';
  $itemName = $data['itemName'] ?? '';
  $text = $data['text'] ?? '';
  
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
  <span class="{{ $numberClasses }}">{{ $number }}</span>
  <div class="{{ $contentClasses }}">
    <p class="{{ $titleClasses }}">{{ $itemName }}</p>
    @if($text)
      <p class="{{ $textClasses }}">{{ $text }}</p>
    @endif
  </div>
</li> 