<!doctype html>
<html @php(language_attributes()) class="dark relative h-screen" x-data="{ darkMode: localStorage.getItem("darkMode") === null ? true : localStorage.getItem("darkMode") === "true" }" x-init="$watch("darkMode", val => localStorage.setItem("darkMode", val))" :class="{ "dark": darkMode }">
    <!-- Hero Background Images -->
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/bonsai_hero_03.png') }}" 
             alt="Background Light" 
             class="w-full h-full object-cover object-top opacity-100 block dark:hidden"
        />
        <img src="{{ asset('images/bonsai_hero_01.png') }}" 
             alt="Background Dark" 
             class="w-full h-full object-cover object-top opacity-100 hidden dark:block"
        />
    </div>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        {{-- @include('bonsai.components.analytics') --}}
        @include('utils.styles')
    </head>

    <body @php(body_class('transition-colors duration-200 p-0 m-0 bg-transparent'))>
        @php(wp_body_open())
        <div id="app" class="relative z-10">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @includeIf('bonsai.sections.site_header')

            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ $containerInnerClasses ?? 'px-6' }}">
                    @yield('content')
                </div>
            </main>

            @includeIf('bonsai.sections.footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>