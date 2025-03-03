@props([
    'data' => []
])

@php
$title = $data['title'] ?? '';
$subtitle = $data['subtitle'] ?? '';
$description = $data['description'] ?? '';
$styles = $data['styles'] ?? [];
$pricingBoxes = $data['pricingBoxes'] ?? [];
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
            <div class="md:w-1/3 flex">
                <div class="{{ $box['containerClasses'] ?? 'pricing-box bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-xl shadow-lg overflow-hidden w-full text-center transition-transform transform hover:scale-105 border border-gray-100 dark:border-gray-800 h-full flex flex-col' }}">
                    <div class="p-6 flex-grow flex flex-col">
                        <!-- Icon -->
                        <div class="flex justify-center items-center mb-4">
                            <svg class="h-14 w-14 mt-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                {!! $box['iconGradientDef'] ?? '' !!}
                                {!! $box['iconPath'] ?? '' !!}
                            </svg>
                        </div>

                        <!-- Plan Type -->
                        <div class="text-center">
                            <h3 class="text-gray-400 dark:text-gray-500">{{ $box['planType'] }}</h3>
                            <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8">{{ $box['price'] }}</p>
                        </div>

                        <!-- Features -->
                        <ul class="my-4 text-left space-y-3 flex-grow">
                            @foreach ($box['features'] as $feature)
                                <li class="flex items-center justify-start text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        {!! $feature['checkGradientDef'] ?? '' !!}
                                        {!! $feature['checkPath'] ?? '' !!}
                                    </svg>
                                    {{ $feature['text'] }}
                                </li>
                            @endforeach
                        </ul>

                        <!-- CTA Button -->
                        <div class="mt-auto pt-4 text-center">
                            <a href="{{ $box['cta']['url'] }}" 
                               class="{{ $box['cta']['classes'] }}"
                               target="_blank" 
                               rel="noopener">
                                {{ $box['cta']['text'] }}
                                <svg class="inline-block h-4 w-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    {!! $box['cta']['iconGradientDef'] ?? '' !!}
                                    {!! $box['cta']['iconPath'] ?? '' !!}
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
