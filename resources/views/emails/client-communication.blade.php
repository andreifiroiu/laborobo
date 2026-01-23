@component('mail::message')
{{-- Greeting Section --}}
# Hello {{ $contactName }},

{{-- Main Content Section --}}
{{ $content }}

{{-- Context Section --}}
@if($workItemTitle)
---

**Regarding:** {{ $workItemTitle }}
@if($workItemType)
*{{ $workItemType }}*
@endif
@endif

{{-- Action Button (if applicable) --}}
@if($actionUrl)
@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
View Details
@endcomponent
@endif

{{-- Footer Section --}}
---

Best regards,<br>
The {{ $teamName }} Team

{{-- Unsubscribe / Contact Info (optional) --}}
@slot('subcopy')
This is an automated communication regarding your project with {{ $teamName }}. If you have any questions, please contact your project manager directly.
@endslot
@endcomponent
