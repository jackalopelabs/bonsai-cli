@props(['items' => []])

<div class="container mx-auto p-4 rounded-xl shadow-lg bg-white bg-opacity-30"
     x-data="{ activeAccordion: '{{ $items[0]['id'] ?? '' }}' }" 
     @accordion-toggled.window="activeAccordion = $event.detail.id">
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar -->
        <div class="md:w-1/3 mb-4 md:mb-0">
            @foreach ($items as $item)
                <x-accordion :item="$item" />
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="md:w-2/3">
            @foreach ($items as $item)
                <div x-show="activeAccordion === '{{ $item['id'] }}'" class="p-2">
                    <x-cta 
                        :title="$item['cta']['title']" 
                        :link="$item['cta']['link']" 
                        :image-path="$item['cta']['imagePath']" 
                    />

                    @if(isset($item['description']))
                        <p class="mt-6 text-gray-600 text-sm">
                            {!! $item['description'] !!}
                        </p>
                    @endif

                    @if(isset($item['listItems']) && is_array($item['listItems']))
                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            @foreach ($item['listItems'] as $listItem)
                                <x-list-item 
                                    :number="$listItem['number']" 
                                    :itemName="$listItem['itemName']" 
                                    :text="$listItem['text']" 
                                />
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