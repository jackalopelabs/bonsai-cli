@props([
    'class' => ''
])

@php
$pricingData = [
    'title' => 'Choose Your Plan',
    'subtitle' => 'Limited-time pricing available now',
    'description' => 'Select the plan that best suits your needs. Lock in your price early and keep it forever, or until you cancel.',
    'pricingBoxes' => [
        [
            'icon' => 'heroicon-o-command-line',
            'iconColor' => 'text-gray-400',
            'planType' => 'Basic',
            'price' => 'Free',
            'features' => [
                'Generate components',
                'Basic templates',
                'Documentation access'
            ],
            'ctaLink' => '#get-started',
            'ctaText' => 'Get Started',
            'ctaColor' => 'bg-white',
            'iconBtn' => 'heroicon-o-arrow-right',
            'iconBtnColor' => 'text-gray-500'
        ],
        [
            'icon' => 'heroicon-o-puzzle-piece',
            'iconColor' => 'text-gray-500',
            'planType' => 'Pro',
            'price' => '$99<span class="text-xs text-gray-400">/yr</span>',
            'features' => [
                'All Basic features',
                'Custom components',
                'Advanced templates',
                'Priority support',
                'Early access'
            ],
            'ctaLink' => '#buy-pro',
            'ctaText' => 'Buy Now',
            'ctaColor' => 'bg-gradient-to-r from-emerald-600 to-green-500 text-white',
            'iconBtn' => 'heroicon-o-shopping-cart',
            'iconBtnColor' => 'text-white'
        ]
    ]
];
@endphp

<section class="py-24" id="plans">
    <div class="py-12">
        <div class="mx-auto px-4 text-center">
            <div class="inline-flex items-center gap-2 rounded-md bg-white text-sm px-3 py-1 text-center mb-4">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-gray-400">{{ $pricingData['subtitle'] }}</span>
            </div>
            <h2 class="text-5xl font-bold text-gray-900 mb-4 pt-4">{{ $pricingData['title'] }}</h2>
            <p class="text-gray-500 mb-8">{{ $pricingData['description'] }}</p>
        </div>
    </div>

    {{-- Render the pricing boxes --}}
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-center items-start space-y-8 md:space-y-0 md:space-x-8">
            @foreach ($pricingData['pricingBoxes'] as $box)
                <x-pricing-box 
                    :icon="$box['icon']"
                    :iconColor="$box['iconColor']"
                    :planType="$box['planType']"
                    :price="$box['price']"
                    :features="$box['features']"
                    :ctaLink="$box['ctaLink']"
                    :ctaText="$box['ctaText']"
                    :ctaColor="$box['ctaColor']"
                    :iconBtn="$box['iconBtn']"
                    :iconBtnColor="$box['iconBtnColor']"
                />
            @endforeach
        </div>
    </div>
</section> 