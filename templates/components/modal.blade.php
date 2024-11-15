{{-- bonsai-cli/templates/components/modal.blade.php --}}
@props([
    'title',
    'class' => null,
])

<div
    x-data="{ open: false }"
    class="relative {{ $class }}"
    @toggle.window="open = !open"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    :aria-expanded="open"
    x-on:keydown.escape="open = false"
>
    @if (isset($button))
        <x-button
            class="{{ $button->attributes->get('class') }}"
            @click="open = !open"
        >
            {{ $button }}
        </x-button>
    @endif

    <div
        x-cloak
        x-data=""
        x-show="open"
        class="fixed top-0 right-0 z-50 w-full h-screen bg-black/50"
    >
        <div
            class="absolute z-30 max-w-4xl p-3 mx-auto bg-white top-4 left-4 right-4"
            x-on:click.away="open = false"
        >
            <h4 class="relative flex items-center mb-4">
                <span class="text-lg font-bold">{{ $title }}</span>

                <div class="z-40 ml-auto">
                    <button
                        class="ml-3"
                        @click="open = !open"
                        x-on:click.stop
                    >
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </h4>
            {{ $slot }}
        </div>
    </div>
</div>
