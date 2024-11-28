@props(['item'])

<div class="my-4">
    <div 
        @click="$dispatch('accordion-toggled', { id: '{{ $item['id'] }}' })" 
        :class="{ 'bg-white bg-opacity-50 rounded-xl p-3': activeAccordion === '{{ $item['id'] }}' }" 
        class="flex items-center space-x-2 cursor-pointer px-3"
    >
        {{-- Render icon only if it is provided and not empty --}}
        @if(!empty($item['icon']))
            <div 
                :class="{ 'bg-white rounded-full': activeAccordion !== '{{ $item['id'] }}', 'bg-gradient-to-r from-emerald-600 to-green-500 rounded-full': activeAccordion === '{{ $item['id'] }}' }" 
                class="h-10 w-10 flex items-center justify-center mr-2"
            >
                <x-dynamic-component 
                    :component="$item['icon']" 
                    :class="activeAccordion === '{{ $item['id'] }}' ? 'text-white' : 'text-gray-700'" 
                    class="inline-block h-4 w-4" 
                />
            </div>
        @endif

        <div class="flex-1">
            <div class="font-bold">{!! $item['title'] !!}</div>
            <div 
                x-show="activeAccordion === '{{ $item['id'] }}'" 
                x-collapse 
                style="display: none;"
            >
                <p class="text-gray-400">{{ $item['content'] }}</p>
            </div>
        </div>
    </div>
</div> 