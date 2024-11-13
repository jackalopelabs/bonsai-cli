@php
$element = $attributes->get('element', 'a');
if ($element === 'a' && !$attributes->has('href')) {
    $element = 'button';
}

$defaultClasses = 'inline-flex items-center border border-transparent active:bg-black active:text-white ';

$variants = [
    'primary' => 'text-white bg-black hover:bg-white hover:text-black',
    'outline' => 'text-black bg-transparent hover:bg-black hover:text-white',
    'gradient' => 'text-white bg-gradient-to-r from-blue-500 to-green-500 hover:from-green-500 hover:to-blue-500', // New gradient variant
];

$sizes = [
    'xs' => 'px-4 py-2 text-xs',
    'sm' => 'px-4 py-2 text-sm',
    'base' => 'px-6 py-3 text-base',
    'lg' => 'px-8 py-4 text-lg',
];
@endphp

<{{ $element }}
    class="{{ $defaultClasses }} {{ $variants[$attributes->get('variant', 'primary')] }} {{ $sizes[$attributes->get('size', 'base')] }} {{ $attributes->get('class') }} rounded-full" // Added rounded-full here if you want it by default on all buttons

    @foreach ($attributes->except(['class', 'variant', 'size', 'element']) as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
>
    {{ $slot }}
</{{ $element }}>
