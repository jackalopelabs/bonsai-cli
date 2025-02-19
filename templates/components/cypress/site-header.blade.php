@php
  $menuItems = [
    [
      'title' => 'Documentation',
      'url' => '/docs',
    ],
    [
      'title' => 'Features',
      'url' => '#features',
    ],
    [
      'title' => 'Pricing',
      'url' => '#pricing',
    ],
  ];
@endphp

<header class="fixed top-0 left-0 right-0 z-50 py-4" x-data="{ mobileMenuOpen: false }">
  <div class="container mx-auto px-4">
    <nav class="flex items-center justify-between">
      <!-- Logo -->
      <a href="/" class="text-2xl font-bold text-gray-900 dark:text-white">
        Bonsai CLI
      </a>

      <!-- Desktop Menu -->
      <div class="hidden md:flex items-center space-x-8">
        @foreach($menuItems as $item)
          <a href="{{ $item['url'] }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            {{ $item['title'] }}
          </a>
        @endforeach

        <!-- Dark Mode Toggle -->
        <button 
          class="p-2 rounded-lg bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 hover:bg-opacity-70 dark:hover:bg-opacity-20"
          x-on:click="darkMode = !darkMode"
        >
          <svg 
            x-show="!darkMode"
            class="w-5 h-5 text-gray-600"
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <svg 
            x-show="darkMode"
            class="w-5 h-5 text-gray-300"
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>
      </div>

      <!-- Mobile Menu Button -->
      <button 
        class="md:hidden p-2 rounded-lg bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10"
        x-on:click="mobileMenuOpen = !mobileMenuOpen"
      >
        <svg 
          class="w-6 h-6 text-gray-600 dark:text-gray-300"
          xmlns="http://www.w3.org/2000/svg" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
        >
          <path 
            x-show="!mobileMenuOpen"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M4 6h16M4 12h16M4 18h16"
          />
          <path 
            x-show="mobileMenuOpen"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </nav>

    <!-- Mobile Menu -->
    <div 
      class="md:hidden"
      x-show="mobileMenuOpen"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
    >
      <div class="py-2 mt-2 bg-white dark:bg-midnight-950 bg-opacity-50 dark:bg-opacity-10 backdrop-blur-lg rounded-lg">
        @foreach($menuItems as $item)
          <a 
            href="{{ $item['url'] }}" 
            class="block px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
            x-on:click="mobileMenuOpen = false"
          >
            {{ $item['title'] }}
          </a>
        @endforeach

        <!-- Dark Mode Toggle (Mobile) -->
        <button 
          class="w-full text-left px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
          x-on:click="darkMode = !darkMode"
        >
          <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
        </button>
      </div>
    </div>
  </div>
</header> 