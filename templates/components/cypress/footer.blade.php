@php
  $year = date('Y');
  $footerLinks = [
    [
      'title' => 'Documentation',
      'url' => '/docs',
    ],
    [
      'title' => 'GitHub',
      'url' => 'https://github.com/jackalopelabs/bonsai-cli',
    ],
    [
      'title' => 'Discord',
      'url' => 'https://discord.gg/bonsai',
    ],
  ];
@endphp

<footer class="py-12 mt-24">
  <div class="container mx-auto px-4">
    <div class="flex flex-col md:flex-row justify-between items-center">
      <div class="text-gray-500 dark:text-gray-400 text-sm">
        &copy; {{ $year }} Bonsai CLI. All rights reserved.
      </div>
      <div class="flex space-x-6 mt-4 md:mt-0">
        @foreach($footerLinks as $link)
          <a href="{{ $link['url'] }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm">
            {{ $link['title'] }}
          </a>
        @endforeach
      </div>
    </div>
  </div>
</footer> 