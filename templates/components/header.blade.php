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
    'buttonPrefix' => ''
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
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden text-gray-900 dark:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </nav>
    </div>
</header>
