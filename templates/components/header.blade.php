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

<header class="{{ $headerClass }}">
    <div class="{{ $containerClasses }}">
        <div class="{{ $containerInnerClasses }}">
            <nav class="flex items-center justify-between">
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
                        <button x-data @click="darkMode = !darkMode" class="{{ $darkModeToggleClass }} p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <span class="sr-only">Toggle dark mode</span>
                            <svg class="w-6 h-6 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg class="w-6 h-6 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>
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
