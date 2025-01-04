@props(['data' => []])

@php
  $items = $data['items'] ?? [];
  
  // Get global styles from parent if available
  $globalStyles = $data['globalStyles'] ?? [];
  
  // Style classes from data with global fallback
  $containerClasses = $data['containerClasses'] ?? $globalStyles['containerClasses'] ?? '';
  $contentClasses = $data['contentClasses'] ?? $globalStyles['contentClasses'] ?? '';
  $descriptionClasses = $data['descriptionClasses'] ?? $globalStyles['descriptionClasses'] ?? '';
  $noteClasses = $data['noteClasses'] ?? $globalStyles['noteClasses'] ?? '';
  $noteLabelClasses = $data['noteLabelClasses'] ?? $globalStyles['noteLabelClasses'] ?? '';
@endphp

<div class="{{ $containerClasses }}"
     x-data="{ activeAccordion: '{{ $items[0]['id'] ?? '' }}' }" 
     @accordion-toggled.window="activeAccordion = $event.detail.id">
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar -->
        <div class="md:w-1/3 mb-4 md:mb-0">
            @foreach ($items as $item)
                <x-bonsai::accordion :data="['item' => $item]" />
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="md:w-2/3">
            @foreach ($items as $item)
                <div x-show="activeAccordion === '{{ $item['id'] }}'" class="{{ $contentClasses }}">
                    @php
                        $ctaData = $item['cta'];
                        $ctaData['globalStyles'] = $data['ctaStyles'] ?? [];
                    @endphp
                    <x-bonsai::cta :data="$ctaData" />

                    @if(isset($item['description']))
                        <p class="{{ $descriptionClasses }}">
                            {!! $item['description'] !!}
                        </p>
                    @endif

                    @if(isset($item['listItems']) && is_array($item['listItems']))
                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            @foreach ($item['listItems'] as $listItem)
                                @php
                                    $listItem['globalStyles'] = $data['listItemStyles'] ?? [];
                                @endphp
                                <x-bonsai::list-item :data="$listItem" />
                            @endforeach
                        </div>
                    @endif

                    @if(isset($item['note']))
                        <p class="{{ $noteClasses }}">
                            <span class="{{ $noteLabelClasses }}">Note:</span> {!! $item['note'] !!}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>      
</div> 