@props([
    'containerClasses' => 'max-w-5xl mx-auto',
    'containerInnerClasses' => 'px-6',
    'siteName' => '',
    'iconComponent' => 'heroicon-o-cube',
    'navLinks' => [],
    'primaryLink' => '#pricing',
])

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scrollHandler', () => ({
        scrollTo(anchor) {
            const element = document.querySelector(anchor);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }));
});
</script>

<header class="{{ $containerClasses }} bg-opacity-60 backdrop-blur-md shadow-lg border border-transparent rounded-full mx-auto p-1 my-4" x-data="scrollHandler">
    <div class="{{ $containerInnerClasses }} flex justify-between items-center w-full">
        <div class="flex">
            <a class="py-3 font-bold text-lg block" href="{{ home_url('/') }}">
                <div class="flex items-center">
                    @if(str_starts_with($iconComponent, 'heroicon'))
                        <x-dynamic-component :component="$iconComponent" class="h-8 w-8 mr-2 p-1" />
                    @else
                        <div class="h-8 w-8 mr-2 p-1">
                            <x-heroicon-o-cube class="h-full w-full" />
                        </div>
                    @endif
                    {!! $siteName !!}
                </div>
            </a>
        
            <ul class="flex space-x-4 items-center ml-6 hidden sm:flex">
                @foreach ($navLinks as $link)
                    @if (!empty($link['url']) && !empty($link['label']))
                        <a href="{{ $link['url'] }}" x-on:click.prevent="scrollTo('{{ $link['url'] }}')">
                            <li>{{ $link['label'] }}</li>
                        </a>
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="flex space-x-4 items-center">
            <a href="{{ $primaryLink }}" class="btn bg-white py-2 px-4 border border-transparent rounded-full bg-opacity-60 backdrop-blur-md shadow-lg" x-on:click.prevent="scrollTo('{{ $primaryLink }}')">
                <span class="hidden sm:inline">See</span> Plans <x-heroicon-s-chevron-down class="w-4 h-4 ml-2 inline-block"/>
            </a>              
        </div>
    </div>
</header> 