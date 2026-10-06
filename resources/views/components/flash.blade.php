@php
    $variants = [
        'status' => 'border-success bg-success-subtle text-success',
        'error' => 'border-danger bg-danger-subtle text-danger',
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
