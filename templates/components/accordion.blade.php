@props(['item'])

@php
    // Get icon name from the full heroicon name (e.g., 'heroicon-o-template' -> 'template')
    $iconName = $item['icon'] ? str_replace(['heroicon-o-', 'heroicon-s-'], '', $item['icon']) : '';
    $isOutline = str_starts_with($item['icon'], 'heroicon-o-');

    // SVG paths for different icons
    $iconPaths = [
        'template' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
        'command-line' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'puzzle-piece' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'
    ];
@endphp

<div class="my-4">
    <div 
        @click="$dispatch('accordion-toggled', { id: '{{ $item['id'] }}' })" 
        :class="{ 'bg-white bg-opacity-50 rounded-xl p-3': activeAccordion === '{{ $item['id'] }}' }" 
        class="flex items-center space-x-2 cursor-pointer px-3"
    >
        {{-- Render icon only if it is provided and not empty --}}
        @if(!empty($item['icon']))
            <div 
                :class="{ 'bg-white rounded-full': activeAccordion !== '{{ $item['id'] }}', 'bg-gradient-to-r from-emerald-600 to-green-500 rounded-full': activeAccordion === '{{ $item['id'] }}' }" 
                class="h-10 w-10 flex items-center justify-center mr-2"
            >
                <svg 
                    :class="activeAccordion === '{{ $item['id'] }}' ? 'text-white' : 'text-gray-700'" 
                    class="inline-block h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                >
                    <path 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        stroke-width="2" 
                        d="{{ $iconPaths[$iconName] ?? 'M4 6h16M4 12h16M4 18h16' }}"
                    />
                </svg>
            </div>
        @endif

        <div class="flex-1">
            <div class="font-bold">{!! $item['title'] !!}</div>
            <div 
                x-show="activeAccordion === '{{ $item['id'] }}'" 
                x-collapse 
                style="display: none;"
            >
                <p class="text-gray-400">{{ $item['content'] }}</p>
            </div>
        </div>
    </div>
</div> 