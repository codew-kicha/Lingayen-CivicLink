@props(['title', 'lede' => null, 'photo' => 'shoreline-dusk.jpg', 'alt' => ''])

{{--
    Inner-page banner (pcnc.com.ph): photo under a maroon wash, breadcrumb, serif title. `photo` is a
    file in public/images/stock, or a path under public/images such as "events/cso3.jpg". The wash is
    even on phones and opens to the right from lg, so real event photos stay visible.
--}}
@php $src = str_contains($photo, '/') ? 'images/'.$photo : 'images/stock/'.$photo; @endphp
<section class="photo-wash photo-wash-banner text-paper">
    <img src="{{ asset($src) }}" alt="{{ $alt }}" width="1600" height="1000" class="banner-photo">
    <div class="relative z-10 mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-section">
        <div class="banner-enter">
            <nav aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-brand-100">
                    <li><a href="{{ route('home') }}" class="hover:text-paper hover:underline">Home</a></li>
                    <li aria-hidden="true"><x-phosphor-caret-right class="h-3.5 w-3.5" /></li>
                    <li aria-current="page" class="text-paper">{{ $title }}</li>
                </ol>
            </nav>
            <h1 class="mt-4 max-w-[28ch] font-serif text-[clamp(1.9rem,3.4vw,2.75rem)] font-bold leading-tight">{{ $title }}</h1>
            @if ($lede)
                <p class="mt-4 max-w-[60ch] text-lg leading-relaxed text-brand-100">{{ $lede }}</p>
            @endif
        </div>
    </div>
</section>
