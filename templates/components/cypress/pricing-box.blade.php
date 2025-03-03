@props([
    'data' => []
])

@php
$title = $data['title'] ?? 'Choose Your Plan';
$subtitle = $data['subtitle'] ?? 'Limited-time pricing available now';
$description = $data['description'] ?? 'Select the plan that best suits your needs.';

// Get global styles
$styles = $data['pricingBoxStyles'] ?? [];
$containerClasses = $styles['containerClasses'] ?? 'bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-xl shadow-lg overflow-hidden w-full md:max-w-sm mx-auto md:mx-0 text-center my-3 transition-transform transform hover:scale-105 border border-gray-100 dark:border-gray-800';
$iconClasses = $styles['iconClasses'] ?? 'inline-block h-12 w-12 mt-8 mb-4';
$planTypeClasses = $styles['planTypeClasses'] ?? 'text-gray-400 dark:text-gray-500';
$priceClasses = $styles['priceClasses'] ?? 'text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8';
$featureListClasses = $styles['featureListClasses'] ?? 'my-4 text-left space-y-3';
$featureItemClasses = $styles['featureItemClasses'] ?? 'flex items-center justify-start text-gray-500 dark:text-gray-400';
$featureIconClasses = $styles['featureIconClasses'] ?? 'w-5 h-5 mr-2';
$ctaButtonClasses = $styles['ctaButtonClasses'] ?? 'inline-block py-2 px-6 rounded-full';
$ctaIconClasses = $styles['ctaIconClasses'] ?? 'inline-block h-4 w-4 ml-2';

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
                <div class="{{ $containerClasses }}">
                    <div class="p-6">
                        <!-- Icon -->
                        <svg 
                            class="{{ $box['iconColor'] ?? 'text-gray-400' }} {{ $iconClasses }}"
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            @if(str_contains($box['icon'] ?? '', 'command-line'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            @elseif(str_contains($box['icon'] ?? '', 'puzzle-piece'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                            @elseif(str_contains($box['icon'] ?? '', 'star'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                            @endif
                        </svg>

                        <!-- Plan Type -->
                        <h3 class="{{ $planTypeClasses }}">{{ $box['planType'] ?? 'Basic' }}</h3>

                        <!-- Price -->
                        <p class="{{ $priceClasses }}">{!! $box['price'] ?? 'Free' !!}</p>

                        <!-- Features -->
                        <ul class="{{ $featureListClasses }}">
                            @foreach ($box['features'] ?? [] as $feature)
                                <li class="{{ $featureItemClasses }}">
                                    <svg class="{{ $featureIconClasses }} {{ $box['planType'] == 'Sensei' ? 'text-yellow-500' : 'text-emerald-500' }}" 
                                         xmlns="http://www.w3.org/2000/svg" 
                                         fill="none" 
                                         viewBox="0 0 24 24" 
                                         stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <!-- CTA Button -->
                        <a href="{{ $box['ctaLink'] ?? '#' }}" 
                           class="{{ $ctaButtonClasses }} {{ $box['ctaColor'] ?? 'bg-white' }} {{ $box['ctaColor'] === 'bg-white' ? 'dark:bg-midnight-950 dark:text-gray-300 dark:border dark:border-gray-700' : '' }}" 
                           target="_blank"
                        >
                            {{ $box['ctaText'] ?? 'Get Started' }}
                            <svg 
                                class="{{ $ctaIconClasses }} {{ $box['iconBtnColor'] ?? 'text-gray-500' }} {{ $box['iconBtnColor'] === 'text-gray-500' ? 'dark:text-gray-400' : '' }}"
                                xmlns="http://www.w3.org/2000/svg" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
                                @if(str_contains($box['iconBtn'] ?? '', 'arrow-right'))
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M7 5l-7 7 7 7"/>
                                @elseif(str_contains($box['iconBtn'] ?? '', 'shopping-cart'))
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17
                                             m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                @endif
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
