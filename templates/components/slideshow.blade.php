{{-- bonsai-cli/templates/components/slideshow.blade.php --}}
<section class="relative py-8">
    <div class="">
        <h2 class="text-center text-2xl font-semibold mb-6">Latest News & Announcements</h2>
        <div class="relative">
            <!-- Left Arrow for Manual Control -->
            <button class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white text-black p-2 rounded-full z-10" id="prevButton">
                <x-heroicon-s-chevron-left class="w-5 h-5" />
            </button>

            <!-- Announcement Cards -->
            <div class="flex space-x-4 overflow-x-auto no-scrollbar snap-x snap-mandatory" id="announcementContainer">
                @foreach($announcements as $announcement)
                    <div class="relative flex-shrink-0 snap-center w-96 h-64 bg-cover bg-center rounded-lg shadow-lg"
                        style="background-image: url('{{ $announcement['backgroundImage'] }}');">
                        
                        <!-- Overlay for text and category -->
                        <div class="absolute inset-0 bg-opacity-40 rounded-lg flex flex-col justify-between p-4">
                            <!-- Category Label -->
                            <span class="text-sm text-white uppercase font-bold">{{ $announcement['category'] ?? 'News' }}</span>

                            <!-- Title and Description at the bottom -->
                            <div>
                                <h3 class="text-lg text-white font-bold">{{ $announcement['title'] }}</h3>
                                <p class="text-sm text-gray-200">{{ $announcement['description'] }}</p>
                                <a href="{{ $announcement['link'] }}" class="text-indigo-400 font-semibold mt-4 inline-block">{{ $announcement['cta'] }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Right Arrow for Manual Control -->
            <button class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white text-black p-2 rounded-full z-10" id="nextButton">
                <x-heroicon-s-chevron-right class="w-5 h-5" />
            </button>
        </div>
    </div>
</section>

