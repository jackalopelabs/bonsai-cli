{{-- bonsai-cli/templates/components/showcase.blade.php --}}
<div class="flex flex-col md:flex-row items-center justify-center md:space-x-4">
    <div class="md:w-2/5 px-4">
        <img src="{{ $imageLink }}" alt="{{ $title }} Project" class="object-cover mix-blend-darken ml-auto" style="height: 300px; width: 300px;" />
    </div>
    <div class="md:w-3/5 mt-4 md:mt-0">
        <h3 class="text-2xl {{ $titleClass }}">{{ $title }}</h3>
        <div class="flex flex-col sm:flex-row items-center sm:space-x-2 mt-2">
            <span class="text-sm text-gray-600">{!! $description !!}</span>
            {{-- <a href="{{ $projectLink }}" class="flex items-center justify-center px-4 py-2 mt-2 sm:mt-0 text-sm font-medium text-gray-700 bg-white rounded-md shadow-sm hover:bg-gray-100 whitespace-nowrap w-full sm:w-auto">
                Learn more <x-heroicon-o-arrow-right class="w-4 h-4 ml-2" />
            </a> --}}
        </div>
    </div>
</div>
