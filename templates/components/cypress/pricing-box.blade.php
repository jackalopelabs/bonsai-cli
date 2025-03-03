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
        'Starter' => ['start' => '#7e22ce', 'end' => '#d8b4fe'],
        'Professional' => ['start' => '#047857', 'end' => '#6ee7b7'],
        'Enterprise' => ['start' => '#b45309', 'end' => '#fcd34d']
    ];
    return $gradients[$planType] ?? $gradients['Starter'];
}
@endphp

<div class="container mx-auto px-4 py-12">
    <!-- Pricing Section Header -->
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $title }}</h2>
        <h3 class="text-xl text-gray-600 dark:text-gray-400 mb-2">{{ $subtitle }}</h3>
        <p class="text-gray-500 dark:text-gray-500 max-w-2xl mx-auto">{{ $description }}</p>
    </div>
    
    <!-- Pricing Boxes Container -->
    <div class="flex flex-col md:flex-row md:space-x-6 space-y-6 md:space-y-0 justify-center items-stretch max-w-7xl mx-auto">
        @foreach($pricingBoxes as $box)
            @php
                $gradientColors = getGradientColors($box['planType'] ?? 'Starter');
            @endphp
            <div class="md:w-1/3 flex">
                <div class="{{ $box['containerClasses'] ?? 'pricing-box bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-xl shadow-lg overflow-hidden w-full text-center transition-transform transform hover:scale-105 border border-gray-100 dark:border-gray-800 h-full flex flex-col' }}">
                    <div class="p-6 flex-grow flex flex-col">
                        <!-- Icon -->
                        <div class="flex justify-center items-center mb-4">
                            <svg class="h-14 w-14 mt-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <defs>
                                    <linearGradient id="iconGradient{{ $box['planType'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                        <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                    </linearGradient>
                                </defs>
                                <path stroke="url(#iconGradient{{ $box['planType'] }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $box['iconPath'] ?? 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' }}"/>
                            </svg>
                        </div>

                        <!-- Plan Type -->
                        <div class="text-center">
                            <h3 class="text-gray-400 dark:text-gray-500">{{ $box['planType'] }}</h3>
                            <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8">{{ $box['price'] }}</p>
                        </div>

                        <!-- Features -->
                        <ul class="my-4 text-left space-y-3 flex-grow">
                            @foreach ($box['features'] as $index => $feature)
                                <li class="flex items-center justify-start text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <defs>
                                            <linearGradient id="checkGradient{{ $index }}{{ $box['planType'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                                <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                            </linearGradient>
                                        </defs>
                                        <path stroke="url(#checkGradient{{ $index }}{{ $box['planType'] }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ is_array($feature) ? ($feature['text'] ?? '') : $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <!-- CTA Button -->
                        <div class="mt-auto pt-4 text-center">
                            <a href="{{ is_array($box['cta']) ? ($box['cta']['url'] ?? '#') : ($box['ctaLink'] ?? '#') }}" 
                               class="{{ is_array($box['cta']) ? ($box['cta']['classes'] ?? '') : 'inline-block py-3 px-8 rounded-full border border-gray-300 dark:border-gray-800 text-gray-800 dark:text-gray-300 hover:bg-gradient-to-r hover:text-white hover:border-transparent hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1' }}"
                               target="_blank" 
                               rel="noopener">
                                {{ is_array($box['cta']) ? ($box['cta']['text'] ?? 'Get Started') : ($box['ctaText'] ?? 'Get Started') }}
                                <svg class="inline-block h-4 w-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <defs>
                                        <linearGradient id="btnGradient{{ $box['planType'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="{{ $gradientColors['start'] }}"/>
                                            <stop offset="100%" stop-color="{{ $gradientColors['end'] }}"/>
                                        </linearGradient>
                                    </defs>
                                    <path stroke="url(#btnGradient{{ $box['planType'] }})" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17 m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
