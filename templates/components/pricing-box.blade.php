@props([
    'icon', // Icon component name (e.g., 'heroicon-o-command-line')
    'iconColor', // Icon color class
    'planType', // e.g., 'Basic', 'Pro'
    'price', // e.g., 'Free', '$99'
    'features', // Array of feature strings
    'ctaLink', // Link for the CTA button
    'ctaText', // Text for the CTA button
    'ctaColor', // Class for button background color
    'iconBtn', // Icon for the CTA button
    'iconBtnColor', // Icon color for the CTA button
])

<div class="bg-white bg-opacity-50 rounded-xl shadow-lg overflow-hidden mx-auto md:mx-0 text-center my-3 transition-transform transform hover:scale-105 {{ $planType == 'Pro' ? 'border border-emerald-500' : ($planType == 'Sensei' ? 'border border-yellow-500' : '') }}">
    <div class="p-6">
        <svg 
            class="{{ $iconColor }} inline-block h-12 w-12 mt-8 mb-4"
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
        >
            @if(str_contains($icon, 'command-line'))
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            @elseif(str_contains($icon, 'puzzle-piece'))
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
            @elseif(str_contains($icon, 'star'))
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            @endif
        </svg>
        <h3 class="text-gray-400">{{ $planType }}</h3>
        <p class="text-4xl font-bold">{!! $price !!}</p>
        <hr class="border-t border-gray-200 my-5" />
        <ul class="my-4 text-left space-y-3">
            @foreach ($features as $feature)
                <li class="flex items-center justify-start text-gray-500">
                    <svg class="w-5 h-5 {{ $planType == 'Sensei' ? 'text-yellow-500' : 'text-emerald-500' }} mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $feature }}
                </li>
            @endforeach
        </ul>
        <hr class="border-t border-gray-200 my-5" />
        <a href="{{ $ctaLink }}" class="inline-block py-2 px-6 rounded-full {{ $ctaColor }}" target="_blank">
            {{ $ctaText }}
            <svg 
                class="{{ $iconBtnColor }} inline-block h-4 w-4 ml-2"
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
            >
                @if(str_contains($iconBtn, 'arrow-right'))
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                @elseif(str_contains($iconBtn, 'shopping-cart'))
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                @endif
            </svg>
        </a>
    </div>
</div> 