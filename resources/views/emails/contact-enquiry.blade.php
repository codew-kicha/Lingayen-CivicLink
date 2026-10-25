<x-mail::message>
# New message from the website

**From:** {{ $enquiry['name'] }} ({{ $enquiry['email'] }})
@if (filled($enquiry['organization'] ?? null))
**Organization:** {{ $enquiry['organization'] }}
@endif
**Sent:** {{ now()->timezone('Asia/Manila')->format('d M Y, g:i A') }}

<x-mail::panel>
{{ $enquiry['message'] }}
</x-mail::panel>

Reply to this email to answer {{ $enquiry['name'] }} directly.

The Contact form on the Lingayen CivicLink website
</x-mail::message>
