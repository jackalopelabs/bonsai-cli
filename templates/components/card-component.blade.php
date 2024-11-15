{{-- bonsai-cli/templates/components/card-component.blade.php --}}
<div class="rounded-xl overflow-hidden shadow-lg bg-white bg-opacity-50 {{ $layoutClass }} transition-transform transform hover:scale-110">
    @if(isset($image))
        <img src="@asset($image)" alt="{{ $title }} image" class="h-64 p-3 mix-blend-darken object-cover rounded-3xl" />
    @endif

    <div class="p-6">
        <div class="flex justify-between">
            <div class="">
                @if(isset($icon) && !empty(trim($icon)))
                    <x-dynamic-component :component="$icon" class="h-6 w-6 text-gray-500"/>
                @endif
                @if(isset($subtitle) && !empty(trim($subtitle)))
                    <span>{!! $subtitle !!}</span>
                @endif
                
                @if(isset($title) && !empty(trim($title)))
                    <h3 class="text-lg font-semibold mb-2">{!! $title !!}</h3>
                @endif
            
            </div>
            {{-- <div>
                <x-heroicon-o-arrow-left class="inline-block h-4 w-4 text-gray-300" />
                <x-heroicon-o-arrow-right class="inline-block h-4 w-4 text-gray-500" />
            </div> --}}
        </div>

        @if(isset($text))
            <p class="text-gray-500 mb-4">{{ $text }}</p>
        @endif

        @if(isset($previewLink) || isset($buyLink))
            <div class="flex space-x-2 md:flex-row">
                @if(isset($previewLink))
                    <a href="{{ $previewLink }}" class="text-sm bg-white hover:bg-gray-200 text-gray-800 py-1 px-3 rounded-md inline-flex items-center">
                        {{ $cta1 }}
                        <x-heroicon-o-chevron-right class="text-gray-300 inline-block h-4 w-4 ml-2" />
                    </a>
                @endif
                {{-- Uncomment this block if `buyLink` is provided in the future
                @if(isset($buyLink))
                    <a href="{{ $buyLink }}" class="text-sm bg-white hover:bg-blue-600 text-gray-400 py-1 px-3 rounded-md inline-flex items-center">
                        {{ $cta2 }}
                        <x-heroicon-o-chevron-right class="inline-block h-4 w-4 ml-2" />
                    </a>
                @endif
                --}}
            </div>
        @endif
    </div>
</div>
