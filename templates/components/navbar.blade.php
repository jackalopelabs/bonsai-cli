{{-- bonsai-cli/templates/components/navbar.blade.php --}}
<header class="{{ $containerClasses }} bg-opacity-70 bg-gradient-to-r from-indigo-500 to-blue-600 text-white backdrop-blur-lg shadow-lg border border-transparent rounded-full mx-auto p-1 my-4 sticky top-0 z-50" x-data="{ mobileMenuOpen: false }" x-cloak>
    <div class="{{ $containerInnerClasses }} py-1 flex justify-between items-center w-full">
        <div class="flex items-center">
            <a href="/" class="flex items-center">
                {{-- <div class="bg-indigo-600 p-3 mr-2 rounded-md"></div>
                <span class="font-semibold text-xl tracking-tight text-gray-800">Hydrofera Blue</span> --}}
                <x-icon-hydrofera-blue-white class="h-8" width="200" />
            </a>
        </div>
        
        <!-- Mobile Menu Button (Hamburger / X toggle) -->
        <div class="md:hidden flex items-center ml-auto">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-200 focus:outline-none">
                <template x-if="!mobileMenuOpen">
                    <x-heroicon-s-bars-2 class="w-6 h-6"/> <!-- Hamburger Icon -->
                </template>
                <template x-if="mobileMenuOpen">
                    <x-heroicon-s-x-mark class="w-6 h-6"/> <!-- X Icon -->
                </template>
            </button>
        </div>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex space-x-6 items-center" x-cloak>
            <ul class="flex space-x-6 items-center list-none">
                <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <a href="/hydrofera-blue" class="flex items-center text-gray-200 hover:text-white">
                        Products <x-heroicon-s-chevron-down class="w-4 h-4 inline-block bg-purple ml-1 pt-1"/>
                    </a>
                    <!-- Dropdown menu -->
                    <ul x-show="open" style="display: none;" @click.away="open = false" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-250" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="absolute left-0 top-full w-72 bg-white shadow-lg rounded-lg py-2 z-10" x-cloak>
                        
                        <!-- Hydrofera Blue CLASSIC with Submenu -->
                        <li class="relative" x-data="{ openSubMenu: false }" @mouseenter="openSubMenu = true" @mouseleave="openSubMenu = false">
                            <a href="/hydrofera-blue-classic/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">
                                Hydrofera Blue CLASSIC<span class="text-xs align-top">®</span>
                                <x-heroicon-s-chevron-right class="w-4 h-4 inline-block float-right pt-1"/>
                            </a>
                            <!-- Sub-menu for Hydrofera Blue CLASSIC -->
                            <ul x-show="openSubMenu" style="display: none;" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-250" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="absolute left-full top-0 w-72 bg-white shadow-lg rounded-lg py-2 z-10" x-cloak>
                                <li><a href="/hydrofera-blue-classic/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue CLASSIC<span class="text-xs align-top">®</span></a></li>
                                <li><a href="/hydrofera-blue-classic-heavy/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue CLASSIC<span class="text-xs align-top">®</span> Heavy Drainage</a></li>
                                <li><a href="/hydrofera-blue-classic-ostomy/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue CLASSIC<span class="text-xs align-top">®</span> Ostomy</a></li>
                                <li><a href="/hydrofera-blue-classic-tunnel/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue CLASSIC<span class="text-xs align-top">®</span> Tunnel</a></li>
                            </ul>
                        </li>

                        <!-- Hydrofera Blue COMFORTCEL -->
                        <li><a href="/hydrofera-blue-comfortcel/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue COMFORTCEL<span class="text-xs align-top">®</span></a></li>

                        <!-- Hydrofera Blue READY with Submenu -->
                        <li class="relative" x-data="{ openSubMenuReady: false }" @mouseenter="openSubMenuReady = true" @mouseleave="openSubMenuReady = false">
                            <a href="/hydrofera-blue-ready/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">
                                Hydrofera Blue READY<span class="text-xs align-top">®</span>
                                <x-heroicon-s-chevron-right class="w-4 h-4 inline-block float-right pt-1"/>
                            </a>
                            <!-- Sub-menu for Hydrofera Blue READY -->
                            <ul x-show="openSubMenuReady" style="display: none;" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-250" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="absolute left-full top-0 w-72 bg-white shadow-lg rounded-lg py-2 z-10" x-cloak>
                                <li><a href="/hydrofera-blue-ready/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue READY<span class="text-xs align-top">®</span></a></li>
                                <li><a href="/hydrofera-blue-ready-border/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue READY-BORDER<span class="text-xs align-top">®</span></a></li>
                                <li><a href="/hydrofera-blue-ready-tsd/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue READY<span class="text-xs align-top">®</span> Tube Site Dressing (TSD)</a></li>
                            </ul>
                        </li>

                        <!-- Hydrofera Blue TRANSFER -->
                        <li><a href="/hydrofera-blue-transfer/" class="block px-4 py-2 text-blue-600 hover:bg-gray-100">Hydrofera Blue TRANSFER<span class="text-xs align-top">®</span></a></li>
                    </ul>
                </li>
                <li><a href="/clinical-insights" class="text-gray-200 hover:text-gray-100">Clinical Insights</a></li>
                <li><a href="/about" class="text-gray-200 hover:text-gray-100">About</a></li>
            </ul>
        </nav>

        <!-- Desktop Action Buttons -->
        <div class="hidden md:flex space-x-4 items-center">
            <a href="/contact/" class="py-2 text-gray-200">Contact</a>
            <a href="/hydrofera-blue" class="btn bg-white text-indigo-500 py-2 px-4 rounded-full shadow-lg">
                Where to buy <x-heroicon-s-shopping-cart class="w-4 h-4 ml-2 inline-block"/>
            </a>              
        </div>

        <!-- Mobile Menu (Visible when toggled) -->
        <div class="md:hidden absolute top-full right-0 w-full bg-white shadow-lg rounded-lg z-50" x-show="mobileMenuOpen" style="display: none;" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-250" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" x-cloak>
            <ul class="flex flex-col space-y-2 p-4 list-none">
                <li><a href="/hydrofera-blue" class="text-gray-300 hover:text-gray-100">Products</a></li>
                <li><a href="/clinical-insights" class="text-gray-300 hover:text-gray-100">Clinical Insights</a></li>
                <li><a href="/about" class="text-gray-300 hover:text-gray-100">About</a></li>
                <li><a href="/contact/" class="text-gray-300 hover:text-gray-100">Contact</a></li>
                <li><a href="/hydrofera-blue" class="text-indigo-500 font-semibold">Where to buy</a></li>
            </ul>
        </div>
    </div>
</header>
