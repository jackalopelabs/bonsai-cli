@props([
    'siteName' => '',
    'iconComponent' => '',
    'navLinks' => [],
    'primaryLink' => '',
    'containerClasses' => '',
    'containerInnerClasses' => '',
    'headerClass' => '',
    'iconClasses' => '',
    'chevronClasses' => '',
    'buttonText' => '',
    'buttonPrefix' => '',
    'showDarkModeToggle' => false,
    'darkModeToggleClass' => ''
])

<header class="{{ $containerClasses }}">
    <div class="{{ $containerInnerClasses }}">
        <nav class="{{ $headerClass }}">
            <div class="flex items-center justify-between">
                <!-- Logo/Site Name -->
                <a href="{{ home_url('/') }}" class="flex items-center">
                    @if($iconComponent)
                        <x-dynamic-component :component="$iconComponent" class="{{ $iconClasses }}" />
                    @endif
                    <span class="text-gray-900 dark:text-white">{{ $siteName }}</span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-4">
                    @foreach($navLinks as $link)
                        <a href="{{ $link['url'] }}" class="text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300">
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    @if($primaryLink)
                        <a href="{{ $primaryLink }}" class="text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300">
                            @if($buttonPrefix)
                                <span class="opacity-75">{{ $buttonPrefix }}</span>
                            @endif
                            {{ $buttonText }}
                        </a>
                    @endif

                    @if($showDarkModeToggle)
                        <x-dark-mode-toggle class="{{ $darkModeToggleClass }}" />
                    @endif
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden text-gray-900 dark:text-white">
                    <x-heroicon-o-menu class="w-6 h-6" />
                </button>
            </div>
        </nav>
    </div>
</header>
