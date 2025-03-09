@props([
    'data' => []
])

@php
// Extract data with defaults
$containerClasses = $data['containerClasses'] ?? 'container mx-auto';
$brandData = $data['brand'] ?? [
    'classes' => [
        'container' => 'flex items-center hover:opacity-80 transition-opacity',
        'icon' => 'h-8 w-8 mr-2 p-1',
        'text' => 'font-semibold text-xl tracking-tight'
    ]
];
$menuGroups = $data['menuGroups'] ?? [];
$socialLinks = $data['socialLinks'] ?? [];
$legalLinks = $data['legalLinks'] ?? [];
$copyright = $data['copyright'] ?? [
    'text' => '© ' . date("Y") . ' Jackalope Labs, LLC',
    'tagline' => 'Follow the white rabbit'
];
$styles = $data['styles'] ?? [
    'footer' => [
        'grid' => 'grid grid-cols-2 gap-12 sm:grid-cols-4 mt-8 md:mt-0 md:order-3',
        'heading' => 'font-semibold',
        'list' => 'mt-4',
        'socialContainer' => 'flex mt-4 gap-4',
        'socialLink' => 'backdrop-blur-md shadow-lg rounded-full p-2',
        'divider' => 'border-t',
        'bottomBar' => 'max-w-6xl mx-auto px-4 py-4 md:flex md:items-center md:justify-between',
        'legalLinks' => 'flex justify-center space-x-6 md:order-2',
        'legalLink' => 'mt-2',
        'copyright' => 'mt-4 md:mt-0 md:order-1',
        'copyrightText' => 'text-center text-sm',
        'tagline' => ''
    ]
];
@endphp

<footer>
    <div class="{{ $containerClasses }} mt-36">
        <div class="max-w-6xl mx-auto px-4 py-8 flex flex-wrap items-start justify-between">
            {{-- Brand --}}
            <a href="{{ $brandData['url'] }}" class="{{ $brandData['classes']['container'] }}">
                @if(isset($brandData['iconSvg']))
                    {!! str_replace(['\"', '\&quot;'], '"', $brandData['iconSvg']) !!}
                @endif
                <span class="{{ $brandData['classes']['text'] }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $brandData['name'] }}</span>
            </a>

            {{-- Menu Groups --}}
            <div class="{{ $styles['footer']['grid'] }}">
                @foreach($menuGroups as $group)
                    <div>
                        <h3 class="{{ $styles['footer']['heading'] }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $group['title'] }}</h3>
                        <ul class="{{ $styles['footer']['list'] }}">
                            @foreach($group['links'] as $link)
                                <a href="{{ $link['url'] }}" @if($link['external'] ?? false) target="_blank" @endif x-bind:style="darkMode ? 'color: #d1d5db;' : 'color: #374151;'">
                                    <li>{{ $link['label'] }}</li>
                                </a>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                {{-- Social Links --}}
                @if(!empty($socialLinks))
                    <div>
                        <h3 class="{{ $styles['footer']['heading'] }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $socialLinks['title'] }}</h3>
                        <div class="{{ $styles['footer']['socialContainer'] }}">
                            @foreach($socialLinks['links'] as $social)
                                <a href="{{ $social['url'] }}" class="{{ $styles['footer']['socialLink'] }}" aria-label="{{ $social['label'] }}" x-bind:style="darkMode ? 'color: white; background-color: rgba(55, 65, 81, 0.5);' : 'color: #374151; background-color: rgba(243, 244, 246, 0.5);'">
                                    <svg class="w-4 h-4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" x-bind:style="darkMode ? 'fill: white;' : 'fill: currentColor;'">
                                        <title>{{ $social['label'] }}</title>
                                        <path d="{{ $social['icon'] }}"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <hr class="{{ $styles['footer']['divider'] }}" x-bind:style="darkMode ? 'border-color: #374151;' : 'border-color: #e5e7eb;'">
    <div class="{{ $containerClasses }}">
        <div class="{{ $styles['footer']['bottomBar'] }}">
            <div class="{{ $styles['footer']['legalLinks'] }}">
                @foreach($legalLinks as $link)
                    <a href="{{ $link['url'] }}" class="{{ $styles['footer']['legalLink'] }}" x-bind:style="darkMode ? 'color: #d1d5db;' : 'color: #374151;'">{{ $link['label'] }}</a>
                @endforeach
                <a href="#" class="rounded-full p-2" x-bind:style="darkMode ? 'background-color: #4b5563;' : 'background-color: #4b5563;'">
                    <x-heroicon-o-arrow-up class="h-6 w-6 text-white"/>
                </a>
            </div>
            <div class="{{ $styles['footer']['copyright'] }}">
                <p class="{{ $styles['footer']['copyrightText'] }}" x-bind:style="darkMode ? 'color: #d1d5db;' : 'color: #374151;'">
                    {{ $copyright['text'] }}
                    <span class="{{ $styles['footer']['tagline'] }}" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'">| {{ $copyright['tagline'] }}</span>
                </p>
            </div>
        </div>
    </div>
</footer> 