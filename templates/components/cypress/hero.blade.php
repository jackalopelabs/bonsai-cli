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
  $imagePaths = $data['imagePaths'] ?? [];
  
  // Style classes from data
  $containerClasses = $data['containerClasses'] ?? '';
  $columnClasses = $data['columnClasses'] ?? '';
  $imageColumnClasses = $data['imageColumnClasses'] ?? '';
  $textColumnClasses = $data['textColumnClasses'] ?? '';
  $productTagClasses = $data['productTagClasses'] ?? '';
  $productIconClasses = $data['productIconClasses'] ?? '';
  $buttonClasses = $data['buttonClasses'] ?? '';
  $buttonIconClasses = $data['buttonIconClasses'] ?? '';
  $secondaryClasses = $data['secondaryClasses'] ?? '';
  $secondaryIconClasses = $data['secondaryIconClasses'] ?? '';
  
  $iconMappings = $data['iconMappings'] ?? [
    'dropdownIcon' => 'heroicon-s-chevron-down',
    'buttonLinkIcon' => 'heroicon-s-shopping-cart',
    'secondaryIcon' => 'heroicon-s-chevron-right',
  ];
@endphp

<div class="{{ $containerClasses }}">
    <div class="{{ $columnClasses }}">
        <!-- Image Column with Slideshow -->
        @if(!empty($imagePaths) && is_array($imagePaths))
            <div class="{{ $imageColumnClasses }}">
                <div class="relative">
                    @foreach($imagePaths as $index => $path)
                        <img src="{{ $path }}" 
                             alt="Product Image {{ $index + 1 }}" 
                             class="max-w-full h-auto p-4" 
                             style="mix-blend-mode: darken;"
                             x-show="currentIndex === {{ $index }}" />
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Text Column -->
        <div class="{{ $textColumnClasses }}">
            @if($product)
                <div class="{{ $productTagClasses }}">
                    {{ $product }} 
                    @if($dropdownIcon)
                        <x-dynamic-component :component="$iconMappings['dropdownIcon']" class="{{ $productIconClasses }}" />
                    @endif
                </div>
            @endif

            @if($title)
                <h1 class="{{ $titleClass }}" style="line-height: normal;">
                    {!! $title !!}
                </h1>
            @endif

            @if($subtitle)
                <p class="font-bold my-4">{{ $subtitle }}</p>
            @endif

            @if($description)
                <p class="text-gray-500 mb-4">{{ $description }}</p>
            @endif

            <div class="flex flex-col items-start">
                @if($buttonText && $buttonLink)
                    <a href="{{ $buttonLink }}" class="{{ $buttonClasses }} mb-2 inline-flex items-center justify-center">
                        {{ $buttonText }}
                        @if($buttonLinkIcon)
                            <x-dynamic-component :component="$iconMappings['buttonLinkIcon']" class="{{ $buttonIconClasses }}" />
                        @endif
                    </a>
                @endif

                @if($secondaryText && $secondaryLink)
                    <a href="{{ $secondaryLink }}" class="{{ $secondaryClasses }} inline-flex items-center justify-center">
                        {{ $secondaryText }}
                        @if($secondaryIcon)
                            <x-dynamic-component :component="$iconMappings['secondaryIcon']" class="{{ $secondaryIconClasses }}" />
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
