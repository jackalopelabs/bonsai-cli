@props(['data' => []])

@php
  $title = $data['title'] ?? '';
  $link = $data['link'] ?? '#';
  $imagePath = $data['imagePath'] ?? '';
@endphp

<a href="{{ $link }}" target="_blank" class="block">
    <div class="relative overflow-hidden rounded-xl bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 shadow-lg hover:shadow-xl transition-shadow duration-200">
        @if($imagePath)
            <img src="{{ $imagePath }}" alt="{{ $title }}" class="w-full h-auto object-cover" />
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent flex items-end p-4">
            <h3 class="text-white text-lg font-semibold">{{ $title }}</h3>
        </div>
    </div>
</a> 