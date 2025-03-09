@props([
    'data' => []
])

@php
$title = $data['title'] ?? '';
$subtitle = $data['subtitle'] ?? '';
$description = $data['description'] ?? '';
$styles = $data['styles'] ?? [];
$pricingBoxes = $data['pricingBoxes'] ?? [];

// Helper function to get gradient definitions based on plan type
function getGradientColors($planType) {
    $gradients = [
        'Starter' => ['start' => '#a5b4fc', 'end' => '#4f46e5'],
        'Professional' => ['start' => '#8CCDFF', 'end' => '#0077FF'],
        'Enterprise' => ['start' => '#6EFFDF', 'end' => '#00DB9D']
    ];
    return $gradients[$planType] ?? $gradients['Starter'];
}

// Helper function to get CTA data with fallbacks
function getCTAData($box) {
    $defaultCTA = [
        'url' => '#',
        'text' => 'Get Started',
        'classes' => 'inline-block py-3 px-8 rounded-full border hover:bg-gradient-to-r hover:text-white hover:border-transparent hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1'
    ];

    if (!isset($box['cta']) && !isset($box['ctaLink'])) {
        return $defaultCTA;
    }

    if (is_array($box['cta'] ?? null)) {
        return [
            'url' => $box['cta']['url'] ?? $defaultCTA['url'],
            'text' => $box['cta']['text'] ?? $defaultCTA['text'],
            'classes' => $box['cta']['classes'] ?? $defaultCTA['classes']
        ];
    }

    return [
        'url' => $box['ctaLink'] ?? $defaultCTA['url'],
        'text' => $box['ctaText'] ?? $defaultCTA['text'],
        'classes' => $defaultCTA['classes']
    ];
}
@endphp

<div id="pricing" class="container mx-auto px-4 py-12">
    <!-- Pricing Section Header -->
    <div class="mx-auto px-4 text-center">
        <h2 class="text-5xl font-bold mb-4 pt-4" x-bind:style="darkMode ? 'color: #f3f4f6;' : 'color: #111827;'">{{ $title }}</h2>
        <p class="mb-8" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'">{{ $subtitle }}</p>
        <div class="inline-flex items-center gap-2 rounded-md text-sm px-3 py-1 text-center mb-8" x-bind:style="darkMode ? 'background-color: #060614;' : 'background-color: white;'">
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" x-bind:style="darkMode ? 'color: #6b7280;' : 'color: #9ca3af;'">
                <path d="M12.75 12.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM7.5 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM8.25 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM9.75 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM10.5 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM12.75 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM14.25 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM15 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM16.5 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM15 12.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM16.5 13.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"></path>
                <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clip-rule="evenodd"></path>
            </svg>               
            <span x-bind:style="darkMode ? 'color: #6b7280;' : 'color: #9ca3af;'">{{ $description }}</span>
        </div>
    </div>
    
    <!-- Pricing Boxes Container -->
    <div class="flex flex-col md:flex-row md:space-x-6 space-y-6 md:space-y-0 justify-center items-stretch max-w-7xl mx-auto">
        @foreach($pricingBoxes as $box)
            @php
                $gradientColors = getGradientColors($box['planType'] ?? 'Starter');
                $ctaData = getCTAData($box);
            @endphp
            <div class="md:w-1/3 flex">
                <div class="{{ $box['containerClasses'] ?? 'pricing-box rounded-xl shadow-lg overflow-hidden w-full text-center transition-transform transform hover:scale-105 h-full flex flex-col' }}"
                     x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.1); border: 1px solid #1f2937;' : 'background-color: rgba(255, 255, 255, 0.5); border: 1px solid #f3f4f6;'">
                    <div class="p-6 flex-grow flex flex-col">
                        <!-- Icon -->
                        <div class="flex justify-center items-center mb-4">
                            <svg class="h-14 w-14 mt-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <defs>
                                    <linearGradient id="iconGradient{{ $box['planType'] ?? 'default' }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                        <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                    </linearGradient>
                                </defs>
                                <path stroke="url(#iconGradient{{ $box['planType'] ?? 'default' }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $box['iconPath'] ?? 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' }}"/>
                            </svg>
                        </div>

                        <!-- Plan Type -->
                        <div class="text-center">
                            <h3 x-bind:style="darkMode ? 'color: #6b7280;' : 'color: #9ca3af;'">{{ $box['planType'] ?? 'Plan' }}</h3>
                            <p class="text-4xl font-bold mb-8" x-bind:style="darkMode ? 'color: #f3f4f6;' : 'color: #111827;'">{{ $box['price'] ?? '$0' }}</p>
                        </div>

                        <!-- Features -->
                        <ul class="my-4 text-left space-y-3 flex-grow">
                            @foreach ($box['features'] ?? [] as $index => $feature)
                                <li class="flex items-center justify-start" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'">
                                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <defs>
                                            <linearGradient id="checkGradient{{ $index }}{{ $box['planType'] ?? 'default' }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                                <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                            </linearGradient>
                                        </defs>
                                        <path stroke="url(#checkGradient{{ $index }}{{ $box['planType'] ?? 'default' }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ is_array($feature) ? ($feature['text'] ?? '') : $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <!-- CTA Button -->
                        <div class="mt-auto pt-4 text-center">
                            <a href="{{ $ctaData['url'] }}" 
                               class="{{ $ctaData['classes'] }}"
                               target="_blank" 
                               rel="noopener"
                               x-bind:style="darkMode ? 'color: #d1d5db; border-color: #374151;' : 'color: #374151; border-color: #d1d5db;'">
                                {{ $ctaData['text'] }}
                                <svg class="inline-block h-4 w-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <defs>
                                        <linearGradient id="btnGradient{{ $box['planType'] ?? 'default' }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                            <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                        </linearGradient>
                                    </defs>
                                    <path stroke="url(#btnGradient{{ $box['planType'] ?? 'default' }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17 m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
