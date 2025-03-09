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
        <div class="flex flex-col sm:flex-row flex-wrap items-start mb-6 justify-center md:justify-center">
            <h2 class="text-lg text-gray-800 bg-white/50 backdrop-blur-md shadow-lg p-3 rounded-lg mr-4 mb-4 sm:mb-0"
                x-bind:style="darkMode ? 'color: #e5e7eb; background-color: rgba(6, 6, 20, 0.2);' : 'color: #1f2937; background-color: rgba(255, 255, 255, 0.5);'">
                {!! $sectionTitle !!}
                <svg class="w-4 h-4 ml-2 inline-block align-middle" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </h2>
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="text-lg text-gray-100 p-3 mb-4 sm:mb-0 hidden sm:inline" x-on:click.prevent="scrollTo('{{ $link['url'] }}')">
                    {{ $link['label'] }}
                    <svg class="w-4 h-4 ml-2 inline-block align-middle" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            @endforeach
        </div>

        <!-- Card Content -->
        <div class="backdrop-blur-md shadow-lg grid md:grid-cols-2 gap-8 rounded-3xl p-3"
             x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.2);' : 'background-color: rgba(255, 255, 255, 0.1);'">
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
                    <div class="flex items-start space-x-4 rounded-xl p-3"
                         x-bind:style="darkMode ? 'background-color: #060614;' : 'background-color: white;'">
                        <div class="shrink-0">
                            @if(isset($item['icon']))
                                @php
                                    // Get icon name from the full heroicon name (e.g., 'heroicon-o-template' -> 'template')
                                    $iconName = str_replace(['heroicon-o-', 'heroicon-s-'], '', $item['icon']);
                                    $isOutline = str_starts_with($item['icon'], 'heroicon-o-');
                                    
                                    // SVG paths for different icons
                                    $iconPaths = [
                                        'template' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
                                        'command-line' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                        'puzzle-piece' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'
                                    ];
                                @endphp
                                <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$iconName] ?? 'M4 6h16M4 12h16M4 18h16' }}"/>
                                </svg>
                            @else
                                <!-- Fallback icon -->
                                <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold" x-bind:style="darkMode ? 'color: white;' : 'color: #1f2937;'">{{ $item['title'] }}</h3>
                            <p class="text-sm" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #6b7280;'">{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>