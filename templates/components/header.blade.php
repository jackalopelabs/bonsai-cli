@props(['data' => []])

@php
    $siteName = $data['siteName'] ?? '';
    $iconSvg = $data['iconSvg'] ?? '';
    $navLinks = $data['navLinks'] ?? [];
    $primaryLink = $data['primaryLink'] ?? '';
    $containerClasses = $data['containerClasses'] ?? 'px-6 flex justify-between items-center w-full';
    $headerClass = $data['headerClass'] ?? 'mx-auto sticky top-0 backdrop-blur-md shadow-lg border border-transparent rounded-full mx-auto p-1 my-4';
    $buttonText = $data['buttonText'] ?? '';
    $buttonPrefix = $data['buttonPrefix'] ?? '';
    $showDarkModeToggle = $data['showDarkModeToggle'] ?? true;
    $darkModeToggleClass = $data['darkModeToggleClass'] ?? 'p-2 rounded-lg transition-colors duration-200';
    $chevronSvg = $data['chevronSvg'] ?? '';
@endphp

<header class="{{ $headerClass }}" x-bind:style="darkMode ? 'background-color: rgba(6, 6, 20, 0.2);' : 'background-color: rgba(255, 255, 255, 0.1);'">
    <div class="{{ $containerClasses }}">
        <a class="py-3 font-bold text-lg block" href="https://bonsai.so/" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">
            <div class="flex items-center">
                {!! $iconSvg !!}
                {{ $siteName }}
            </div>
        </a>

        <ul class="hidden sm:flex items-center justify-center flex-1 mx-6 space-x-8">
            @foreach($navLinks as $link)
            <a href="{{ $link['url'] }}" x-bind:style="darkMode ? 'color: white;' : 'color: #111827;'">
                    <li>{{ $link['label'] }}</li>
                </a>
            @endforeach
        </ul>

        <div class="flex space-x-4 items-center">
            <a href="{{ $primaryLink }}" class="btn bg-indigo-500 py-2 px-4 border border-transparent rounded-full backdrop-blur-md shadow-lg text-white">
                <span class="hidden sm:inline">{{ $buttonPrefix }}</span> {{ $buttonText }} {!! $chevronSvg !!}
            </a>
            @if($showDarkModeToggle)
            <button
                type="button"
                @click="$store.darkMode.toggle()"
                class="{{ $darkModeToggleClass }}"
                x-bind:style="darkMode ? 'color: white; background-color: rgba(55, 65, 81, 0.3);' : 'color: #111827; background-color: rgba(243, 244, 246, 0.3);'"
                aria-label="{{ __('Toggle dark mode', 'radicle') }}"
            >
                <svg x-show="!darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <!-- Sun icon -->
                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <!-- Moon icon -->
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>
            </button> 
            @endif
        </div>
    </div>
</header> 