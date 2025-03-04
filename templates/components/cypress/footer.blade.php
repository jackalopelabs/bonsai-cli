@props([
    'data' => []
])

@php
// Extract data with defaults
$containerClasses = $data['containerClasses'] ?? 'container mx-auto';
$brandData = $data['brand'] ?? [
    'classes' => [
        'container' => 'flex items-center',
        'icon' => 'h-8 w-8 mr-2 p-1',
        'text' => 'font-semibold text-xl tracking-tight text-gray-800'
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
        'heading' => 'text-gray-700 font-semibold',
        'list' => 'text-gray-600 mt-4',
        'socialContainer' => 'flex mt-4 gap-4',
        'socialLink' => 'text-gray-600 hover:text-gray-500 backdrop-blur-md shadow-lg rounded-full p-2',
        'divider' => 'border-t',
        'bottomBar' => 'max-w-6xl mx-auto px-4 py-4 md:flex md:items-center md:justify-between',
        'legalLinks' => 'flex justify-center space-x-6 md:order-2',
        'legalLink' => 'text-gray-600 hover:text-gray-500 mt-2',
        'copyright' => 'mt-4 md:mt-0 md:order-1',
        'copyrightText' => 'text-center text-gray-600 text-sm',
        'tagline' => 'text-gray-200'
    ]
];
@endphp

<footer>
    <div class="{{ $containerClasses }} mt-36">
        <div class="max-w-6xl mx-auto px-4 py-8 flex flex-wrap items-start justify-between">
            {{-- Brand --}}
            <div class="{{ $brandData['classes']['container'] }}">
                @if(isset($brandData['iconSvg']))
                    {!! str_replace(['\"', '\&quot;'], '"', $brandData['iconSvg']) !!}
                @endif
                <span class="{{ $brandData['classes']['text'] }}">{{ $brandData['name'] }}</span>
            </div>

            {{-- Menu Groups --}}
            <div class="{{ $styles['footer']['grid'] }}">
                @foreach($menuGroups as $group)
                    <div>
                        <h3 class="{{ $styles['footer']['heading'] }}">{{ $group['title'] }}</h3>
                        <ul class="{{ $styles['footer']['list'] }}">
                            @foreach($group['links'] as $link)
                                <a href="{{ $link['url'] }}" @if($link['external'] ?? false) target="_blank" @endif>
                                    <li>{{ $link['label'] }}</li>
                                </a>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                {{-- Social Links --}}
                @if(!empty($socialLinks))
                    <div>
                        <h3 class="{{ $styles['footer']['heading'] }}">{{ $socialLinks['title'] }}</h3>
                        <div class="{{ $styles['footer']['socialContainer'] }}">
                            @foreach($socialLinks['links'] as $social)
                                <a href="{{ $social['url'] }}" class="{{ $styles['footer']['socialLink'] }}" aria-label="{{ $social['label'] }}">
                                    <svg class="w-4 h-4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
    <hr class="{{ $styles['footer']['divider'] }}">
    <div class="{{ $containerClasses }}">
        <div class="{{ $styles['footer']['bottomBar'] }}">
            <div class="{{ $styles['footer']['legalLinks'] }}">
                @foreach($legalLinks as $link)
                    <a href="{{ $link['url'] }}" class="{{ $styles['footer']['legalLink'] }}">{{ $link['label'] }}</a>
                @endforeach
                <a href="#" class="bg-gray-600 hover:bg-gray-500 rounded-full p-2">
                    <x-heroicon-o-arrow-up class="h-6 w-6 text-white"/>
                </a>
            </div>
            <div class="{{ $styles['footer']['copyright'] }}">
                <p class="{{ $styles['footer']['copyrightText'] }}">
                    {{ $copyright['text'] }}
                    <span class="{{ $styles['footer']['tagline'] }}">| {{ $copyright['tagline'] }}</span>
                </p>
            </div>
        </div>
    </div>
</footer> 