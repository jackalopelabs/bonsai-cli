{{-- bonsai-cli/templates/components/modal.blade.php --}}
<div
    x-data="{ open: @entangle($attributes->wire('open')) }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-75"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-90"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-90"
        @click.away="open = false"
        @click.stop
        class="bg-white p-8 rounded shadow-lg relative"
    >
        <!-- Close Button -->
        <button @click="open = false" class="absolute top-0 right-0 m-4 text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-2xl mb-4">{{ $title }}</h2>
        <div class="mb-4">
            {{ $slot }}
        </div>
        <button @click="open = false" class="bg-red-500 text-white px-4 py-2 rounded">Close Modal</button>
    </div>
</div>
