@php
    $variants = [
        'status' => 'border-success-600 bg-success-100 text-success-600',
        'error' => 'border-danger-600 bg-danger-100 text-danger-600',
    ];
@endphp

@foreach ($variants as $key => $classes)
    @if (session($key))
        <div role="status"
             class="mb-4 rounded-sm border px-4 py-3 text-sm font-medium {{ $classes }}">
            {{ session($key) }}
        </div>
    @endif
@endforeach
