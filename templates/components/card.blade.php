@props(['data' => []])

@php
  $sectionId = $data['sectionId'] ?? 'services';
  $sectionTitle = $data['sectionTitle'] ?? 'Our Services';
  $navLinks = $data['navLinks'] ?? [];
  $featureItems = $data['featureItems'] ?? [];
  $image = $data['image'] ?? null;
@endphp

<section id="{{ $sectionId }}" class="py-12">
    <div class="max-w-4xl mx-auto px-6">
        <!-- Navigation Links -->
        <div class="flex flex-col sm:flex-row flex-wrap items-start mb-6 justify-center md:justify-center" x-data="scrollHandler">
            <h2 class="text-lg text-gray-700 bg-white p-3 rounded-lg mr-4 mb-4 sm:mb-0">
                {!! $sectionTitle !!}
                <svg class="w-4 h-4 ml-2 inline-block align-middle" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </h2>
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="text-lg text-gray-700 p-3 mb-4 sm:mb-0 hidden sm:inline" x-on:click.prevent="scrollTo('{{ $link['url'] }}')">
                    {{ $link['label'] }}
                    <svg class="w-4 h-4 ml-2 inline-block align-middle" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            @endforeach
        </div>

        <!-- Card Content -->
        <div class="bg-white bg-opacity-50 grid md:grid-cols-2 gap-8 rounded-3xl p-3">
            <!-- Image or Icon Column -->
            <div class="md:w-1/2 mx-auto">
                @if ($image)
                    <!-- Option A: Using Blade Component -->
                    {{-- <x-dynamic-component :component="$image" class="w-full" /> --}}
                @else
                    @include('bonsai.components.icons.flowchart', ['attributes' => 'class="w-full"'])
                @endif
            </div>

            <!-- Features Column -->
            <div class="md:w-2/2 space-y-6">
                @foreach ($featureItems as $item)
                    <div class="flex items-start space-x-4 bg-white rounded-xl p-3">
                        <div class="shrink-0">
                            <!-- Simple icon fallback -->
                            <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">{{ $item['title'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>