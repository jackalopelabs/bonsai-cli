{{-- bonsai-cli/templates/components/hero.blade.php --}}
@props([
    'product', 
    'titleClass', 
    'title', 
    'subtitle', 
    'description', 
    'dropdownIcon', 
    'buttonLinkIcon', 
    'secondaryIcon', 
    'buttonText', 
    'buttonLink', 
    'secondaryText', 
    'secondaryLink', 
    'imagePaths', // Image paths as an array of URLs
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
            <!-- Create a hidden element to store JSON-encoded image paths -->
            <script type="application/json" id="imagePathsJson">
                {!! json_encode($imagePaths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
            </script>

            <div x-data="slideshow()" class="w-full md:w-1/2 px-4 flex justify-center items-center mt-12 md:mt-0 md:order-last">
                <!-- Inside the relative div in your slideshow component -->
                <div class="relative">
                    <!-- Template for image slideshow -->
                    <template x-for="(image, index) in images" :key="index">
                        <img :src="image" x-show="currentIndex === index" alt="Product Image" class="max-w-full h-auto p-4" style="mix-blend-mode: darken;" />
                    </template>

                    <!-- Navigation arrows -->
                    <div class="absolute top-1/2 left-0 transform -translate-y-1/2">
                        <x-heroicon-o-chevron-left class="inline-block h-4 w-4 text-gray-300 cursor-pointer" @click="prevImage" />
                    </div>
                    <div class="absolute top-1/2 right-0 transform -translate-y-1/2">
                        <x-heroicon-o-chevron-right class="inline-block h-4 w-4 text-gray-500 cursor-pointer" @click="nextImage" />
                    </div>

                    <!-- Pagination dots -->
                    <div class="absolute bottom-0 left-0 right-0 flex justify-center mb-2">
                        <template x-for="(image, index) in images" :key="index">
                            <div
                                class="mx-1 h-2 w-2 rounded-full cursor-pointer"
                                :class="currentIndex === index ? 'bg-gray-500' : 'bg-gray-300'"
                                @click="goToImage(index)"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        @endif

        <!-- Text Column -->
        <div class="w-full md:w-1/2 px-4">
            @if(!empty($product))
                <div class="bg-white bg-opacity-50 px-3 py-1 text-sm inline-block">
                    {{ $product }} 
                    @if(!empty($dropdownIcon) && isset($iconMappings[$dropdownIcon]))
                        <x-dynamic-component :component="$iconMappings[$dropdownIcon]" class="w-4 h-4 ml-2 inline-block align-middle" />
                    @endif
                </div>
            @endif
            @if(!empty($title))
                <h1 class="{{ $titleClass }}" style="line-height: normal;">
                    {!! $title !!}
                </h1>
            @endif
            @if(!empty($subtitle))
                <p class="font-bold my-4">{{ $subtitle }}</p>
            @endif
            @if(!empty($description))
                <p class="text-gray-500 mb-4">{{ $description }}</p>
            @endif
            <div class="flex flex-col items-start">
                @if(!empty($buttonText) && !empty($buttonLink))
                    <a href="{{ $buttonLink }}" class="bg-gradient-to-r from-indigo-500 to-blue-600 text-white text-xl py-2 px-5 rounded-full mb-2 inline-flex items-center justify-center">
                        {{ $buttonText }} 
                        @if(!empty($buttonLinkIcon) && isset($iconMappings[$buttonLinkIcon]))
                            <x-dynamic-component :component="$iconMappings[$buttonLinkIcon]" class="text-white w-6 h-6 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif
                @if(!empty($secondaryText) && !empty($secondaryLink))
                    <a href="{{ $secondaryLink }}" class="text-sm bg-transparent px-2 py-1 backdrop-blur-md shadow-lg rounded-md inline-flex items-center justify-center border border-gray-100">
                        {{ $secondaryText }} 
                        @if(!empty($secondaryIcon) && isset($iconMappings[$secondaryIcon]))
                            <x-dynamic-component :component="$iconMappings[$secondaryIcon]" class="w-4 h-4 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>