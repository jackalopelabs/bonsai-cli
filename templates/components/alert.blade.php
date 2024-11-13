@props([
    'type' => null,
    'message' => null,
])

@php($class = match ($type) {
    'success' => 'bg-indigo-50',
    'caution' => 'bg-indigo-50',
    'warning' => 'bg-indigo-50',
    default => 'bg-indigo-50',
})

<div {{ $attributes->merge(['class' => "px-2 py-1 {$class}"]) }}>
    {!! $message ?? $slot !!}
</div>
