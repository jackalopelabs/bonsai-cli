{{-- bonsai-cli/templates/components/card-featured.blade.php --}}
@props([
    'title',
    'subtitle',
    'items',
    'imagePath',
    'playIcon' => false
])

<section class="pb-12">
    <div class="max-w-4xl mx-auto px-6">
        <div class="bg-white bg-opacity-50 md:flex md:flex-row md:items-center gap-6 rounded-3xl p-3">
            <div class="flex items-center justify-center relative w-full md:w-1/2 mx-auto h-full">
                <img src="@asset(str_replace(['.jpg', '.png'], '.webp', $imagePath))" style="mix-blend-mode: darken;" alt="featured img" class="w-full object-contain rounded-xl">
                @if($playIcon)
                    <div class="absolute">
                        <x-heroicon-s-play class="h-12 w-12 text-white" />
                    </div>
                @endif
            </div>        
            <div class="w-full md:w-1/2 space-y-6">
                @foreach ($subtitle as $feature)
                    <div class="flex items-start space-x-4 bg-white rounded-xl p-3">
                        {{-- Always show the icon on the left if it exists --}}
                        @if (!empty($feature['icon']))
                            <div class="shrink-0">
                                <x-dynamic-component :component="$feature['icon']" class="h-6 w-6 text-indigo-500" />
                            </div>
                        @endif

                        {{-- Title and description container --}}
                        <div>
                            {{-- Always show the title --}}
                            <h3 class="text-lg font-semibold">{{ $feature['title'] }}</h3>

                            {{-- Show description only if it exists --}}
                            @if (!empty($feature['description']))
                                <p class="text-sm text-gray-500">{{ $feature['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>