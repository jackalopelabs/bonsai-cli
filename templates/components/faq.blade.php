@props([
    'faqs' => []
])

<div class="container mx-auto p-6">
    <div x-data="{ selected: null }">
        @foreach($faqs as $index => $faq)
            <div class="mb-4">
                <button @click="selected !== {{ $index }} ? selected = {{ $index }} : selected = null" class="w-full bg-white p-4 rounded-lg shadow-md text-left flex justify-between items-center">
                    <span class="font-semibold text-lg">{{ $faq['question'] }}</span>
                    <span x-show="selected !== {{ $index }}" class="text-xl text-indigo-500 font-semibold">+</span>
                    <span x-show="selected === {{ $index }}" class="text-xl text-purple-500 font-semibold">-</span>
                </button>
                <div x-show="selected === {{ $index }}" class="bg-white p-4 rounded-lg shadow-md mt-2">
                    {!! $faq['answer'] !!}
                </div>
            </div>
        @endforeach
    </div>
</div>
