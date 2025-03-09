@props(['data' => []])

@php
  $product = $data['product'] ?? null;
  $titleClass = $data['titleClass'] ?? 'font-semibold text-6xl';
  $title = $data['title'] ?? null;
  $subtitle = $data['subtitle'] ?? null;
  $description = $data['description'] ?? null;
  $dropdownIcon = $data['dropdownIcon'] ?? null;
  $buttonLinkIcon = $data['buttonLinkIcon'] ?? null;
  $secondaryIcon = $data['secondaryIcon'] ?? null;
  $buttonText = $data['buttonText'] ?? null;
  $buttonLink = $data['buttonLink'] ?? null;
  $secondaryText = $data['secondaryText'] ?? null;
  $secondaryLink = $data['secondaryLink'] ?? null;
  $secondaryIconClasses = $data['secondaryIconClasses'] ?? 'w-4 h-4 ml-2 inline-block align-middle';
  $iconMappings = $data['iconMappings'] ?? [
    'dropdownIcon' => 'heroicon-s-chevron-down',
    'buttonLinkIcon' => 'heroicon-s-shopping-cart',
    'secondaryIcon' => 'heroicon-s-chevron-right',
  ];
@endphp

<div class="container mx-auto px-4 mb-12 mt-0 md:mt-24" x-data="{}">
    <div class="flex flex-col items-center text-center max-w-3xl mx-auto">
        @if($product)
            <div class="px-3 py-1 text-sm inline-block" x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.5);' : 'background-color: rgba(255, 255, 255, 0.5);'">
                <span x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $product }}</span>
                @if($dropdownIcon)
                    <x-dynamic-component :component="$iconMappings['dropdownIcon']" class="w-4 h-4 ml-2 inline-block align-middle" />
                @endif
            </div>
        @endif

        @if($title)
            <h1 class="{{ $titleClass }}" style="line-height: normal;" x-bind:style="darkMode ? 'color: white; text-shadow: 0 0 10px rgba(255,255,255,0.8), 0 0 20px rgba(255,255,255,0.4);' : 'color: #111827;'">
                {!! $title !!}
            </h1>
        @endif

        @if($subtitle)
            <p class="font-bold my-4" x-bind:style="darkMode ? 'color: white; text-shadow: 0 0 8px rgba(255,255,255,0.8), 0 0 16px rgba(255,255,255,0.4);' : 'color: #1f2937;'">
                {{ $subtitle }}
            </p>
        @endif

        @if($description)
            <p class="mb-4 max-w-2xl" x-bind:style="darkMode ? 'color: #e5e7eb; text-shadow: 0 1px 3px rgba(0,0,0,0.6);' : 'color: #4b5563;'">
                {{ $description }}
            </p>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8">
            @if($buttonText && $buttonLink)
                <a href="{{ $buttonLink }}" class="bg-gradient-to-r from-teal-500 to-indigo-500 text-white text-xl py-2 px-5 rounded-full inline-flex items-center justify-center shadow-lg">
                    {{ $buttonText }}
                    @if($buttonLinkIcon)
                        <x-dynamic-component :component="$iconMappings['buttonLinkIcon']" class="text-white w-6 h-6 ml-2 inline-block align-middle" />
                    @endif
                </a>
            @endif

            @if($secondaryText && $secondaryLink)
                <a href="{{ $secondaryLink }}" target="_blank" class="text-sm bg-transparent px-4 py-1 backdrop-blur-md shadow-lg rounded-lg inline-flex items-center justify-center group" x-bind:style="darkMode ? 'color: white; border: 1px solid rgba(255, 255, 255, 0.3);' : 'color: #111827; border: 0;'">
                    {{ $secondaryText }}
                    @if($secondaryIcon)
                        <x-dynamic-component :component="$iconMappings['secondaryIcon']" class="{{ $secondaryIconClasses }}" />
                    @endif
                </a>
            @endif
        </div>
    </div>
</div> 