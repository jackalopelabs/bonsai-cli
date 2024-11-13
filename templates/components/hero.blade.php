{{-- bonsai-cli/templates/components/hero.blade.php --}}
<div class="container mx-auto px-4 py-36">
    <div class="flex flex-wrap -mx-4">
        <!-- Image Column -->
        <div class="w-full md:w-1/2 flex justify-center items-center order-1 md:order-2">
            <div class="relative w-full">
                <!-- Circle with Drop Shadow -->
                <button @click="openModal = true" class="absolute z-10 left-4 top-1/2 transform -translate-y-1/2 flex justify-center items-center">
                    <div class="bg-gray-800 rounded-full shadow-2xl flex items-center justify-center" style="width: 72px; height: 72px; margin-left: 18px;">
                        <!-- Heroicon in the middle of the circle -->
                        <x-heroicon-o-play class="w-8 h-8 p-1 text-white" />
                    </div>
                </button>

                <!-- Image -->
                <img src="@asset($imagePath)" style="mix-blend-mode: darken;" alt="Hero image" class="max-w-full h-auto">
            </div>
        </div>

        <!-- Text Column -->
        <div class="w-full md:w-1/2 px-4 order-2 md:order-1 mt-0 md:mt-12" x-data="scrollHandler">
            <h1 class="text-black font-inter font-semibold text-6xl" style="letter-spacing: -2px;">
                {{ $title }}
            </h1>
            <p class="my-4 text-xl"><strong>{{ $subtitle }}</strong></p>
            <ul class="mb-4">
                <li><x-heroicon-s-check class="w-4 h-4 mr-2 inline-block align-middle" /> {{ $l1 }}</li>
                <li><x-heroicon-s-check class="w-4 h-4 mr-2 inline-block align-middle" /> {{ $l2 }}</li>
                <li><x-heroicon-s-check class="w-4 h-4 mr-2 inline-block align-middle" /> {{ $l3 }}</li>
                <li><x-heroicon-s-check class="w-4 h-4 mr-2 inline-block align-middle" /> {{ $l4 }}</li>
            </ul>
            <div class="flex flex-col items-start">
                <x-button x-on:click.prevent="scrollTo('{{ $primaryLink }}')" variant="gradient">
                    {{ $primaryText }}
                    <x-heroicon-s-chevron-down class="w-4 h-4 ml-2" />
                </x-button>                

                <!-- Secondary Link Button -->
                <div>
                    <a @click="openModal = true" class="bg-white px-5 py-1 mt-3 backdrop-blur-md shadow-lg rounded-xl inline-flex items-center justify-center border border-gray-100 cursor-pointer">
                        <x-heroicon-s-play-circle class="w-4 h-4 mr-2 inline-block align-middle" /> {{ $secondaryText }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
