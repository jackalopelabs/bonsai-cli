@props(['data' => []])

@php
  $number = $data['number'] ?? '';
  $itemName = $data['itemName'] ?? '';
  $text = $data['text'] ?? '';
@endphp

<div class="flex items-start space-x-4 bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-lg p-3">
    <div class="shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-500 text-white rounded-full">
        {{ $number }}
    </div>
    <div>
        <h4 class="text-gray-900 dark:text-gray-100 font-semibold">{{ $itemName }}</h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $text }}</p>
    </div>
</div> 