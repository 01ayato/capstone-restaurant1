<x-mail::message>
# A message about your review

{{ $body }}

<x-mail::panel>
Your rating: {{ str_repeat('★', $feedback->rating) }}{{ str_repeat('☆', 5 - $feedback->rating) }}
@if ($feedback->comment)

"{{ $feedback->comment }}"
@endif
</x-mail::panel>

@if ($settings->phone || $settings->email || $settings->address)
@if ($settings->address)
{{ $settings->address }}
@endif
@if ($settings->phone)
Phone: {{ $settings->phone }}
@endif
@if ($settings->email)
Email: {{ $settings->email }}
@endif
@endif

Thanks,<br>
{{ $settings->name }}
</x-mail::message>
