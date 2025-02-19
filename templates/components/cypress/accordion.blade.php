@props(['data' => []])

@php
  $item = $data['item'] ?? [];
  $id = $item['id'] ?? '';
  $title = $item['title'] ?? '';
  $icon = $item['icon'] ?? '';
  $content = $item['content'] ?? '';
@endphp

<div class="mb-2">
    <button 
        class="w-full text-left p-3 rounded-lg bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 hover:bg-opacity-70 dark:hover:bg-opacity-20 transition-all duration-200"
        x-on:click="activeAccordion = '{{ $id }}'; $dispatch('accordion-toggled', { id: '{{ $id }}' })"
        :class="{ 'bg-opacity-70 dark:bg-opacity-20': activeAccordion === '{{ $id }}' }"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                @if($icon)
                    <x-dynamic-component :component="$icon" class="h-5 w-5 text-indigo-500 mr-2" />
                @endif
                <span class="text-gray-900 dark:text-gray-100">{{ $title }}</span>
            </div>
            <svg 
                class="h-5 w-5 text-gray-500 transform transition-transform duration-200"
                :class="{ 'rotate-180': activeAccordion === '{{ $id }}' }"
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
        @if($content)
            <p class="text-sm text-gray-500 mt-2">{{ $content }}</p>
        @endif
    </button>
</div> 