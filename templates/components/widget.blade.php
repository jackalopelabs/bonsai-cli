@props(['data' => []])

@php
  $items = $data['items'] ?? [];
  $globalStyles = $data['globalStyles'] ?? [];
  $listItemStyles = $data['listItemStyles'] ?? [];
  $ctaStyles = $data['ctaStyles'] ?? [];
@endphp

<div class="container mx-auto p-4 rounded-xl shadow-lg mt-8"
     x-data="{ activeAccordion: '{{ $items[0]['id'] ?? '' }}' }" 
     @accordion-toggled.window="activeAccordion = $event.detail.id"
     x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.1);' : 'background-color: rgba(255, 255, 255, 0.3);'">
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar -->
        <div class="md:w-1/3 mb-4 md:mb-0">
            @foreach ($items as $item)
                <x-bonsai::accordion :data="[
                    'item' => $item,
                    'activeAccordionClasses' => 'rounded-xl p-3',
                    'activeIconContainerClasses' => 'bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full',
                    'inactiveIconContainerClasses' => '',
                    'inactiveIconClasses' => '',
                    'contentClasses' => ''
                ]" />
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="md:w-2/3">
            @foreach ($items as $item)
                <div x-show="activeAccordion === '{{ $item['id'] }}'" class="p-2">
                    <x-bonsai::cta 
                        :data="[
                            'title' => $item['cta']['title'],
                            'link' => $item['cta']['link'],
                            'imagePath' => $item['cta']['imagePath'],
                            'buttonText' => $item['cta']['buttonText'] ?? 'Learn More',
                            'containerClasses' => $ctaStyles['containerClasses'] ?? 'cta rounded-lg flex items-center space-x-8 mt-2',
                            'imageClasses' => $ctaStyles['imageClasses'] ?? 'w-44 h-44 object-cover rounded-xl',
                            'contentContainerClasses' => $ctaStyles['contentContainerClasses'] ?? 'flex items-center justify-between flex-1',
                            'titleClasses' => $ctaStyles['titleClasses'] ?? 'text-xl font-semibold',
                            'buttonClasses' => $ctaStyles['buttonClasses'] ?? 'inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md',
                            'buttonIconClasses' => $ctaStyles['buttonIconClasses'] ?? 'w-4 h-4 ml-2',
                            'useDarkMode' => true
                        ]" 
                    />

                    @if(isset($item['description']))
                        <p class="mt-6 text-sm" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #4b5563;'">
                            {!! $item['description'] !!}
                        </p>
                    @endif

                    @if(isset($item['listItems']) && is_array($item['listItems']))
                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                            @foreach ($item['listItems'] as $listItem)
                                <x-bonsai::list-item 
                                    :data="[
                                        'number' => $listItem['number'],
                                        'itemName' => $listItem['itemName'],
                                        'text' => $listItem['text'],
                                        'listItemClasses' => $listItemStyles['listItemClasses'] ?? 'flex items-start py-2',
                                        'numberClasses' => $listItemStyles['numberClasses'] ?? 'flex-shrink-0 flex items-center justify-center text-white mr-4 rounded-full w-8 h-8 text-sm',
                                        'titleClasses' => $listItemStyles['titleClasses'] ?? 'font-semibold',
                                        'textClasses' => $listItemStyles['textClasses'] ?? 'text-sm',
                                        'useDarkMode' => true
                                    ]"
                                />
                            @endforeach
                        </div>
                    @endif

                    @if(isset($item['note']))
                        <p class="mt-6 text-sm" x-bind:style="darkMode ? 'color: #9ca3af;' : 'color: #4b5563;'">
                            <span class="font-bold">Note:</span> {!! $item['note'] !!}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>      
</div> 