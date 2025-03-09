@props(['data' => []])

@php
  $item = $data['item'] ?? [];
  // Get icon name from the full heroicon name (e.g., 'heroicon-o-template' -> 'template')
  $iconName = $item['icon'] ? str_replace(['heroicon-o-', 'heroicon-s-'], '', $item['icon']) : '';
  $isOutline = str_starts_with($item['icon'], 'heroicon-o-');

  // Style classes
  $containerClasses = $data['containerClasses'] ?? 'my-4';
  $accordionClasses = $data['accordionClasses'] ?? 'flex items-center space-x-2 cursor-pointer px-3';
  $activeAccordionClasses = $data['activeAccordionClasses'] ?? 'rounded-xl p-3';
  $iconContainerClasses = $data['iconContainerClasses'] ?? 'h-10 w-10 flex items-center justify-center mr-2';
  $activeIconContainerClasses = $data['activeIconContainerClasses'] ?? 'bg-gradient-to-r from-emerald-600 to-green-500 rounded-full';
  $inactiveIconContainerClasses = $data['inactiveIconContainerClasses'] ?? 'rounded-full';
  $iconClasses = $data['iconClasses'] ?? 'inline-block h-4 w-4';
  $activeIconClasses = $data['activeIconClasses'] ?? 'text-white';
  $inactiveIconClasses = $data['inactiveIconClasses'] ?? '';
  $titleClasses = $data['titleClasses'] ?? 'font-bold';
  $contentClasses = $data['contentClasses'] ?? '';
  $contentWrapperClasses = $data['contentWrapperClasses'] ?? 'flex-1';

  // SVG paths for different icons
  $iconPaths = $data['iconPaths'] ?? [
      'template' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
      'command-line' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
      'puzzle-piece' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'
  ];
@endphp

<div class="{{ $containerClasses }}">
    <div 
        @click="$dispatch('accordion-toggled', { id: '{{ $item['id'] }}' })" 
        :class="{ '{{ $activeAccordionClasses }}': activeAccordion === '{{ $item['id'] }}' }" 
        class="{{ $accordionClasses }}"
        :style="activeAccordion === '{{ $item['id'] }}' ? (darkMode ? 'background-color: rgba(6, 6, 20, 0.1);' : 'background-color: rgba(255, 255, 255, 0.5);') : ''"
    >
        {{-- Render icon only if it is provided and not empty --}}
        @if(!empty($item['icon']))
            <div 
                :class="{ '{{ $inactiveIconContainerClasses }}': activeAccordion !== '{{ $item['id'] }}', '{{ $activeIconContainerClasses }}': activeAccordion === '{{ $item['id'] }}' }" 
                class="{{ $iconContainerClasses }}"
                :style="activeAccordion !== '{{ $item['id'] }}' ? (darkMode ? 'background-color: #1f2937;' : 'background-color: white;') : ''"
            >
                <svg 
                    :class="activeAccordion === '{{ $item['id'] }}' ? '{{ $activeIconClasses }}' : '{{ $inactiveIconClasses }}'" 
                    class="{{ $iconClasses }}"
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                    :style="activeAccordion !== '{{ $item['id'] }}' ? (darkMode ? 'color: #d1d5db;' : 'color: #4b5563;') : ''"
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

        <div class="{{ $contentWrapperClasses }}">
            <div class="{{ $titleClasses }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{!! $item['title'] !!}</div>
            <div 
                x-show="activeAccordion === '{{ $item['id'] }}'" 
                x-collapse 
                style="display: none;"
            >
                <p class="{{ $contentClasses }}" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'">{{ $item['content'] }}</p>
            </div>
        </div>
    </div>
</div> 