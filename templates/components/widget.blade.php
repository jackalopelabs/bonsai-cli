@props(['data' => []])

@php
  $items = $data['items'] ?? [];
@endphp

<div class="container mx-auto p-4 rounded-xl shadow-lg bg-white bg-opacity-30"
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
                <div x-show="activeAccordion === '{{ $item['id'] }}'" class="p-2">
                    @php
                        // Add global CTA styles to the CTA data
                        $ctaData = $item['cta'];
                        $ctaData['globalStyles'] = $data['ctaStyles'] ?? [];
                    @endphp
                    <x-bonsai::cta :data="$ctaData" />

                    @if(isset($item['description']))
                        <p class="mt-6 text-gray-600 text-sm">
                            {!! $item['description'] !!}
                        </p>
                    @endif

                    @if(isset($item['listItems']) && is_array($item['listItems']))
                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            @foreach ($item['listItems'] as $listItem)
                                @php
                                    // Merge global styles with list item data
                                    $listItem['globalStyles'] = $data['listItemStyles'] ?? [];
                                @endphp
                                <x-bonsai::list-item :data="$listItem" />
                            @endforeach
                        </div>
                    @endif

                    @if(isset($item['note']))
                        <p class="mt-6 text-gray-600 text-sm">
                            <span class="font-bold">Note:</span> {!! $item['note'] !!}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>      
</div> 