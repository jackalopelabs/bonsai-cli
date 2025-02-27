@props(['data' => []])

@php
  $items = $data['items'] ?? [];
  $globalStyles = $data['globalStyles'] ?? [];
  $listItemStyles = $data['listItemStyles'] ?? [];
  $ctaStyles = $data['ctaStyles'] ?? [];
@endphp

<div class="container mx-auto p-4 rounded-xl shadow-lg bg-white dark:bg-midnight-950 bg-opacity-30 dark:bg-opacity-10 mt-8"
     x-data="{ activeAccordion: '{{ $items[0]['id'] ?? '' }}' }" 
     @accordion-toggled.window="activeAccordion = $event.detail.id">
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar -->
        <div class="md:w-1/3 mb-4 md:mb-0">
            @foreach ($items as $item)
                <x-bonsai::accordion :data="[
                    'item' => $item,
                    'activeAccordionClasses' => 'bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-xl p-3',
                    'activeIconContainerClasses' => 'bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full',
                    'inactiveIconContainerClasses' => 'bg-white dark:bg-midnight-950',
                    'inactiveIconClasses' => 'text-gray-700 dark:text-gray-300',
                    'contentClasses' => 'text-gray-400 dark:text-gray-500'
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
                            'containerClasses' => $ctaStyles['containerClasses'] ?? 'cta bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 rounded-lg flex items-center space-x-8 mt-2',
                            'imageClasses' => $ctaStyles['imageClasses'] ?? 'w-44 h-44 object-cover rounded-xl',
                            'contentContainerClasses' => $ctaStyles['contentContainerClasses'] ?? 'flex items-center justify-between flex-1',
                            'titleClasses' => $ctaStyles['titleClasses'] ?? 'text-xl font-semibold text-gray-900 dark:text-gray-100',
                            'buttonClasses' => $ctaStyles['buttonClasses'] ?? 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-midnight-950 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-opacity-10',
                            'buttonIconClasses' => $ctaStyles['buttonIconClasses'] ?? 'w-4 h-4 ml-2'
                        ]" 
                    />

                    @if(isset($item['description']))
                        <p class="mt-6 text-gray-600 dark:text-gray-400 text-sm">
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
                                        'numberClasses' => $listItemStyles['numberClasses'] ?? 'flex-shrink-0 flex items-center justify-center text-white mr-4 bg-gray-600 dark:bg-gray-700 rounded-full w-8 h-8 text-sm',
                                        'titleClasses' => $listItemStyles['titleClasses'] ?? 'font-semibold dark:text-gray-200',
                                        'textClasses' => $listItemStyles['textClasses'] ?? 'text-sm text-gray-500 dark:text-gray-400'
                                    ]"
                                />
                            @endforeach
                        </div>
                    @endif

                    @if(isset($item['note']))
                        <p class="mt-6 text-gray-600 dark:text-gray-400 text-sm">
                            <span class="font-bold">Note:</span> {!! $item['note'] !!}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>      
</div> 