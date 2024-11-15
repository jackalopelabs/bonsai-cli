{{-- bonsai-cli/templates/components/cta.blade.php --}}
@props(['title', 'link', 'imagePath'])
<div class="cta bg-white bg-opacity-50 rounded-lg flex items-center space-x-8 mt-2">
    <!-- Placeholder square image -->
    <img src="{{ $imagePath }}" alt="CTA image" class="w-44 h-44 object-cover rounded-xl">

    <!-- Text content container -->
    <div class="flex items-center justify-between flex-1">
        <!-- Title -->
        <h2 class="text-xl font-semibold">{!! $title !!}</h2>
        <!-- Button -->
        <x-button href="{{ $link }}" variant="outline" size="sm" class="mr-5 text-xs">
          Learn more
          <x-heroicon-s-arrow-right class="w-4 h-4 ml-2"/>
        </x-button>      
    </div>
</div>