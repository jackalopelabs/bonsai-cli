@props([
    'sectionTitle',
    'subtitle',
    'features' => [],
])

<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $sectionTitle }}</h2>
            <p class="text-xl text-gray-600">{{ $subtitle }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($features as $feature)
                <div class="bg-white rounded-lg shadow-sm p-8 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-center w-12 h-12 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 text-white mb-6">
                        <x-dynamic-component 
                            :component="$feature['icon']"
                            class="w-6 h-6"
                        />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ $feature['title'] }}</h3>
                    <p class="text-gray-600">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section> 