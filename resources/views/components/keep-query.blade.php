@props(['only' => ['per_page']])

{{--
    Hidden fields that carry chosen query values through a filter form (page size, a scoped
    organization). `page` is never kept, so changing a filter always returns to page 1.
--}}
@foreach ($only as $key)
    @if (filled(request()->query($key)) && $key !== 'page')
        <input type="hidden" name="{{ $key }}" value="{{ request()->query($key) }}">
    @endif
@endforeach
