<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>
    <body @php(body_class())>
        @php(wp_body_open())
        <div id="app" class="{{ $themeSettings['body']['class'] ?? 'bg-gray-100' }}">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>
            @include('bonsai.sections.site_header')
            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ $containerInnerClasses }}">
                    @yield('content')
                </div>
            </main>
            @includeIf('sections.footer')
        </div>
        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html> 