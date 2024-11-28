@props(['title', 'link', 'imagePath'])

<div class="cta bg-white bg-opacity-50 rounded-lg flex items-center space-x-8 mt-2">
    <!-- Image -->
    <img src="{{ $imagePath }}" alt="Feature illustration" class="w-44 h-44 object-cover rounded-xl">

    <!-- Text content container -->
    <div class="flex items-center justify-between flex-1">
        <!-- Title -->
        <h2 class="text-xl font-semibold">{!! $title !!}</h2>
        <!-- Button -->
        <a href="{{ $link }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            Learn more
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div> 