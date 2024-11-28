@props(['number', 'itemName', 'text'])

<li class="flex items-start py-2">
  <span class="flex-shrink-0 flex items-center justify-center text-white mr-4 bg-gray-600 rounded-full w-8 h-8 text-sm">{{ $number }}</span>
  <div>
    <p class="font-semibold">{{ $itemName }}</p>
    @if($text)
      <p class="text-sm text-gray-500">{{ $text }}</p>
    @endif
  </div>
</li> 