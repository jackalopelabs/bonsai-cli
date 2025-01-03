@props(['data' => []])

@php
  $sectionTitle = $data['sectionTitle'] ?? '';
  $subtitle = $data['subtitle'] ?? '';
  $features = $data['features'] ?? [];

  // Style classes
  $sectionClasses = $data['sectionClasses'] ?? 'py-24 bg-gray-50';
  $containerClasses = $data['containerClasses'] ?? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
  $headerContainerClasses = $data['headerContainerClasses'] ?? 'text-center mb-16';
  $titleClasses = $data['titleClasses'] ?? 'text-4xl font-bold text-gray-900 mb-4';
  $subtitleClasses = $data['subtitleClasses'] ?? 'text-xl text-gray-600';
  
  // Grid classes
  $gridContainerClasses = $data['gridContainerClasses'] ?? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8';
  $featureCardClasses = $data['featureCardClasses'] ?? 'bg-white rounded-lg shadow-sm p-8 hover:shadow-lg transition-shadow duration-300';
  
  // Feature icon classes
  $iconWrapperClasses = $data['iconWrapperClasses'] ?? 'flex items-center justify-center w-12 h-12 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 text-white mb-6';
  $iconClasses = $data['iconClasses'] ?? 'w-6 h-6';
  
  // Feature content classes
  $featureTitleClasses = $data['featureTitleClasses'] ?? 'text-xl font-semibold text-gray-900 mb-3';
  $featureDescriptionClasses = $data['featureDescriptionClasses'] ?? 'text-gray-600';
@endphp

<section class="{{ $sectionClasses }}">
    <div class="{{ $containerClasses }}">
        <div class="{{ $headerContainerClasses }}">
            <h2 class="{{ $titleClasses }}">{{ $sectionTitle }}</h2>
            <p class="{{ $subtitleClasses }}">{{ $subtitle }}</p>
        </div>

        <div class="{{ $gridContainerClasses }}">
            @foreach ($features as $feature)
                <div class="{{ $featureCardClasses }}">
                    <div class="{{ $iconWrapperClasses }}">
                        <x-{{ $feature['icon'] }} 
                            class="{{ $iconClasses }}"
                        />
                    </div>
                    <h3 class="{{ $featureTitleClasses }}">{{ $feature['title'] }}</h3>
                    <p class="{{ $featureDescriptionClasses }}">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section> 