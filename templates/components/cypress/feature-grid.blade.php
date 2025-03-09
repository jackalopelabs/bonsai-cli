@props(['data' => []])

@php
  $sectionTitle = $data['sectionTitle'] ?? '';
  $subtitle = $data['subtitle'] ?? '';
  $features = $data['features'] ?? [];

  // Style classes
  $sectionClasses = $data['sectionClasses'] ?? 'py-24';
  $containerClasses = $data['containerClasses'] ?? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
  $headerContainerClasses = $data['headerContainerClasses'] ?? 'text-center mb-16';
  $titleClasses = $data['titleClasses'] ?? 'text-4xl font-bold mb-4';
  $subtitleClasses = $data['subtitleClasses'] ?? 'text-xl';
  
  // Grid classes
  $gridContainerClasses = $data['gridContainerClasses'] ?? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8';
  $featureCardClasses = $data['featureCardClasses'] ?? 'rounded-lg shadow-sm p-8 hover:shadow-lg transition-shadow duration-300';
  
  // Feature icon classes
  $iconWrapperClasses = $data['iconWrapperClasses'] ?? 'flex items-center justify-center w-12 h-12 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 text-white mb-6';
  $iconClasses = $data['iconClasses'] ?? 'w-6 h-6';
  
  // Feature content classes
  $featureTitleClasses = $data['featureTitleClasses'] ?? 'text-xl font-semibold mb-3';
  $featureDescriptionClasses = $data['featureDescriptionClasses'] ?? '';
@endphp

<section class="{{ $sectionClasses }}" x-bind:style="darkMode ? 'background-color: #111827;' : 'background-color: #f9fafb;'">
    <div class="{{ $containerClasses }}">
        <div class="{{ $headerContainerClasses }}">
            <h2 class="{{ $titleClasses }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $sectionTitle }}</h2>
            <p class="{{ $subtitleClasses }}" x-bind:style="darkMode ? 'color: #d1d5db;' : 'color: #4b5563;'">{{ $subtitle }}</p>
        </div>

        <div class="{{ $gridContainerClasses }}">
            @foreach ($features as $feature)
                <div class="{{ $featureCardClasses }}" x-bind:style="darkMode ? 'background-color: #1f2937;' : 'background-color: white;'">
                    <div class="{{ $iconWrapperClasses }}">
                        <x-{{ $feature['icon'] }} 
                            class="{{ $iconClasses }}"
                        />
                    </div>
                    <h3 class="{{ $featureTitleClasses }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">{{ $feature['title'] }}</h3>
                    <p class="{{ $featureDescriptionClasses }}" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #4b5563;'">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section> 