@props(['item', 'open' => false])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="my-4">
    <div @click="open = !open; $dispatch('accordion-toggled', { id: '{{ $item['id'] }}' })" 
         :class="{ 'bg-white bg-opacity-50 rounded-xl p-3': open }" class="flex items-center space-x-2 cursor-pointer px-3">
         <div :class="{ 'bg-white rounded-full': !open, 'bonsai-gradient rounded-full': open }" class="h-10 w-10 flex items-center justify-center mr-2">
            @isset($item['icon'])
                <x-dynamic-component :component="$item['icon']" :class="open ? 'text-white' : 'text-gray-700'" class="inline-block h-4 w-4" />
            @endisset
        </div>
        <div class="flex-1">
            <div class="font-bold">{{ $item['title'] }}</div>
            <div x-show="open" x-collapse style="display: none;">
                <p class="text-gray-400">{{ $item['content'] }}</p>
            </div>
        </div>
    </div>
</div>
