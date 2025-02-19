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
  $iconMappings = $data['iconMappings'] ?? [
    'dropdownIcon' => 'heroicon-s-chevron-down',
    'buttonLinkIcon' => 'heroicon-s-shopping-cart',
    'secondaryIcon' => 'heroicon-s-chevron-right',
  ];
@endphp

<div class="container mx-auto px-4 mb-12 mt-0 md:mt-24">
    <div class="flex flex-col md:flex-row items-center md:items-start -mx-4">
        <!-- Text Column -->
        <div class="w-full md:w-1/2 px-4 pt-12">
            @if($product)
                <div class="bg-white bg-opacity-50 px-3 py-1 text-sm inline-block">
                    {{ $product }} 
                    @if($dropdownIcon)
                        <x-dynamic-component :component="$iconMappings['dropdownIcon']" class="w-4 h-4 ml-2 inline-block align-middle" />
                    @endif
                </div>
            @endif

            @if($title)
                <h1 class="{{ $titleClass }} text-gray-900 dark:text-white" style="line-height: normal;">
                    {!! $title !!}
                </h1>
            @endif

            @if($subtitle)
                <p class="font-bold my-4 text-gray-800 dark:text-white">{{ $subtitle }}</p>
            @endif

            @if($description)
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $description }}</p>
            @endif

            <div class="flex flex-col sm:flex-row items-start gap-4">
                @if($buttonText && $buttonLink)
                    <a href="{{ $buttonLink }}" class="bg-gradient-to-r from-teal-500 to-indigo-500 text-white text-xl py-2 px-5 rounded-full inline-flex items-center justify-center">
                        {{ $buttonText }}
                        @if($buttonLinkIcon)
                            <x-dynamic-component :component="$iconMappings['buttonLinkIcon']" class="text-white w-6 h-6 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif

                @if($secondaryText && $secondaryLink)
                    <a href="{{ $secondaryLink }}" target="_blank" class="text-sm bg-transparent px-4 py-1 backdrop-blur-md shadow-lg rounded-lg inline-flex items-center justify-center border border-gray-100 text-gray-600 dark:text-white">
                        {{ $secondaryText }}
                        @if($secondaryIcon)
                            <x-bonsai::icons.github class="w-4 h-4 ml-2 inline-block align-middle" />
                        @endif
                    </a>
                @endif
            </div>
        </div>

        <!-- Image Column -->
        @if(!empty($imagePaths) && is_array($imagePaths))
            <div class="w-full md:w-1/2 px-4 flex justify-center items-center mt-12 md:mt-0">
                <div class="relative">
                    @foreach($imagePaths as $index => $path)
                        <img src="{{ $path }}" 
                             alt="Product Image {{ $index + 1 }}" 
                             class="max-w-full h-auto p-4"
                             style="filter: drop-shadow(0 20px 25px rgba(0, 0, 0, 0.3)) 
                                    drop-shadow(0 10px 10px rgba(0, 0, 0, 0.2)) 
                                    drop-shadow(0 5px 5px rgba(0, 0, 0, 0.15))"
                        />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
