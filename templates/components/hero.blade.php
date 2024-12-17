{{-- bonsai-cli/templates/components/hero.blade.php --}}
@props([
    'product' => null, 
    'titleClass' => 'font-semibold text-6xl',
    'title' => null,
    'subtitle' => null,
    'description' => null,
    'dropdownIcon' => null,
    'buttonLinkIcon' => null,
    'secondaryIcon' => null,
    'buttonText' => null,
    'buttonLink' => null,
    'secondaryText' => null,
    'secondaryLink' => null,
    'imagePaths' => [], // Image paths as an array of URLs
    'iconMappings' => [
        'dropdownIcon' => 'heroicon-s-chevron-down',
        'buttonLinkIcon' => 'heroicon-s-shopping-cart',
        'secondaryIcon' => 'heroicon-s-chevron-right',
    ]
])

<div class="container mx-auto px-4 mb-12 mt-0 md:mt-24">
    <div class="flex flex-col md:flex-row items-center md:items-start -mx-4">
        <!-- Image Column with Slideshow -->
        @if(!empty($imagePaths) && is_array($imagePaths))
            <div class="w-full md:w-1/2 px-4 flex justify-center items-center mt-12 md:mt-0 md:order-last">
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
        <div class="w-full md:w-1/2 px-4">
            @if($product)
                <div class="bg-white bg-opacity-50 px-3 py-1 text-sm inline-block">
                    {{ $product }} 
                    @if($dropdownIcon)
                        <x-bonsai::icons.chevron-down class="w-4 h-4 ml-2 inline-block align-middle" />
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
                    <a href="{{ $buttonLink }}" class="bg-gradient-to-r from-indigo-500 to-blue-600 text-white text-xl py-2 px-5 rounded-full mb-2 inline-flex items-center justify-center">
                        {{ $buttonText }}
                        @if($buttonLinkIcon)
                            <x-dynamic-component :component="$iconMappings['buttonLinkIcon']" class="text-white w-6 h-6 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif

                @if($secondaryText && $secondaryLink)
                    <a href="{{ $secondaryLink }}" class="text-sm bg-transparent px-2 py-1 backdrop-blur-md shadow-lg rounded-md inline-flex items-center justify-center border border-gray-100">
                        {{ $secondaryText }}
                        @if($secondaryIcon)
                            <x-dynamic-component :component="$iconMappings['secondaryIcon']" class="w-4 h-4 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>