@props(['data' => []])

@php
  $sectionId = $data['sectionId'] ?? 'services';
  $sectionTitle = $data['sectionTitle'] ?? 'Our Services';
  $navLinks = $data['navLinks'] ?? [];
  $featureItems = $data['featureItems'] ?? [];
  $image = $data['image'] ?? null;

  // Style classes
  $sectionClasses = $data['sectionClasses'] ?? 'py-12';
  $containerClasses = $data['containerClasses'] ?? 'max-w-4xl mx-auto px-6';
  $navContainerClasses = $data['navContainerClasses'] ?? 'flex flex-col sm:flex-row flex-wrap items-start mb-6 justify-center md:justify-center';
  $titleClasses = $data['titleClasses'] ?? 'text-lg text-gray-700 bg-white p-3 rounded-lg mr-4 mb-4 sm:mb-0';
  $titleIconClasses = $data['titleIconClasses'] ?? 'w-4 h-4 ml-2 inline-block align-middle';
  $navLinkClasses = $data['navLinkClasses'] ?? 'text-lg text-gray-700 p-3 mb-4 sm:mb-0 hidden sm:inline';
  $navLinkIconClasses = $data['navLinkIconClasses'] ?? 'w-4 h-4 ml-2 inline-block align-middle';
  
  // Card styles
  $cardContainerClasses = $data['cardContainerClasses'] ?? 'bg-white bg-opacity-50 grid md:grid-cols-2 gap-8 rounded-3xl p-3';
  $imageColumnClasses = $data['imageColumnClasses'] ?? 'md:w-1/2 mx-auto';
  $featuresColumnClasses = $data['featuresColumnClasses'] ?? 'md:w-2/2 space-y-6';
  $featureItemClasses = $data['featureItemClasses'] ?? 'flex items-start space-x-4 bg-white rounded-xl p-3';
  $featureIconClasses = $data['featureIconClasses'] ?? 'h-6 w-6 text-indigo-500';
  $featureTitleClasses = $data['featureTitleClasses'] ?? 'text-lg font-semibold';
  $featureDescriptionClasses = $data['featureDescriptionClasses'] ?? 'text-sm text-gray-500';
@endphp

<section id="{{ $sectionId }}" class="{{ $sectionClasses }}">
    <div class="{{ $containerClasses }}">
        <!-- Navigation Links -->
        <div class="{{ $navContainerClasses }}" x-data="scrollHandler">
            <h2 class="{{ $titleClasses }}">
                {!! $sectionTitle !!}
                <svg class="{{ $titleIconClasses }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </h2>
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $navLinkClasses }}" x-on:click.prevent="scrollTo('{{ $link['url'] }}')">
                    {{ $link['label'] }}
                    <svg class="{{ $navLinkIconClasses }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            @endforeach
        </div>

        <!-- Card Content -->
        <div class="{{ $cardContainerClasses }}">
            <!-- Image or Icon Column -->
            <div class="{{ $imageColumnClasses }}">
                @if ($image)
                    <!-- Option A: Using Blade Component -->
                    {{-- <x-dynamic-component :component="$image" class="w-full" /> --}}
                @else
                    @include('bonsai.components.icons.flowchart', ['attributes' => 'class="w-full"'])
                @endif
            </div>

            <!-- Features Column -->
            <div class="{{ $featuresColumnClasses }}">
                @foreach ($featureItems as $item)
                    <div class="{{ $featureItemClasses }}">
                        <div class="shrink-0">
                            <!-- Simple icon fallback -->
                            <svg class="{{ $featureIconClasses }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="{{ $featureTitleClasses }}">{{ $item['title'] }}</h3>
                            <p class="{{ $featureDescriptionClasses }}">{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>