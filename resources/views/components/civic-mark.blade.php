@props(['class' => 'h-9 w-9'])

{{-- Official seal of the Municipality of Lingayen, supplied by the client (original at
     storage/app/public/logos/lingayen_logo.png; this is a 256px copy). Municipal Ordinance No. 79,
     s-2019 governs use of the seal. Decorative here: it always sits beside the site name. --}}
<img src="{{ asset('images/brand/lingayen-seal.png') }}" alt="" width="256" height="256"
     {{ $attributes->merge(['class' => $class.' shrink-0 rounded-full object-contain']) }}>
