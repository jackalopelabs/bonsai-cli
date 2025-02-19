@props(['data' => []])

@php
    $icon = $data['icon'] ?? 'heroicon-o-command-line';
    $iconColor = $data['iconColor'] ?? '';
    $planType = $data['planType'] ?? 'Basic';
    $price = $data['price'] ?? 'Free';
    $features = $data['features'] ?? [];
    $ctaLink = $data['ctaLink'] ?? '#';
    $ctaText = $data['ctaText'] ?? 'Get Started';
    $ctaColor = $data['ctaColor'] ?? '';
    $iconBtn = $data['iconBtn'] ?? null;
    $iconBtnColor = $data['iconBtnColor'] ?? '';
    
    // Get global styles from parent if available
    $globalStyles = $data['globalStyles'] ?? [];
    
    // Style classes from data with global fallback
    $containerClasses = $data['containerClasses'] ?? $globalStyles['containerClasses'] ?? '';
    $iconClasses = $data['iconClasses'] ?? $globalStyles['iconClasses'] ?? '';
    $planTypeClasses = $data['planTypeClasses'] ?? $globalStyles['planTypeClasses'] ?? '';
    $priceClasses = $data['priceClasses'] ?? $globalStyles['priceClasses'] ?? '';
    $dividerClasses = $data['dividerClasses'] ?? $globalStyles['dividerClasses'] ?? '';
    $featureListClasses = $data['featureListClasses'] ?? $globalStyles['featureListClasses'] ?? '';
    $featureItemClasses = $data['featureItemClasses'] ?? $globalStyles['featureItemClasses'] ?? '';
    $featureIconClasses = $data['featureIconClasses'] ?? $globalStyles['featureIconClasses'] ?? '';
    $ctaButtonClasses = $data['ctaButtonClasses'] ?? $globalStyles['ctaButtonClasses'] ?? '';
    $ctaIconClasses = $data['ctaIconClasses'] ?? $globalStyles['ctaIconClasses'] ?? '';
@endphp

<div class="{{ $containerClasses }} {{ $planType == 'Pro' ? 'border border-emerald-500' : ($planType == 'Sensei' ? 'border border-yellow-500' : '') }}">
    <div class="p-6">
        <!-- Icon -->
        <x-dynamic-component 
            :component="$icon" 
            class="{{ $iconClasses }} {{ $iconColor }}"
        />

        <!-- Plan Type -->
        <h3 class="{{ $planTypeClasses }}">{{ $planType }}</h3>

        <!-- Price -->
        <p class="{{ $priceClasses }}">{!! $price !!}</p>
        <hr class="{{ $dividerClasses }}" />

        <!-- Features -->
        <ul class="{{ $featureListClasses }}">
            @foreach ($features as $feature)
                <li class="{{ $featureItemClasses }}">
                    <x-heroicon-o-check 
                        class="{{ $featureIconClasses }} {{ $planType == 'Sensei' ? 'text-yellow-500' : 'text-emerald-500' }}"
                    />
                    {{ $feature }}
                </li>
            @endforeach
        </ul>

        <hr class="{{ $dividerClasses }}" />

        <!-- CTA Button -->
        <a href="{{ $ctaLink }}" class="{{ $ctaButtonClasses }} {{ $ctaColor }}" target="_blank">
            {{ $ctaText }}
            @if($iconBtn)
                <x-dynamic-component 
                    :component="$iconBtn"
                    class="{{ $ctaIconClasses }} {{ $iconBtnColor }}"
                />
            @endif
        </a>
    </div>
</div>
