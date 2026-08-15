<x-mail::message>
# {{ $headline }}

{{ $intro }}

<x-mail::panel>
**{{ $settings->name }}**

Guest: {{ $reservation->customer_name }}  
Contact: {{ $reservation->contact_number }}  
Date & time: {{ $reservation->reserved_at->format('M d, Y · h:i A') }}  
Table: {{ $reservation->diningTable?->label() ?? 'To be assigned' }}@if($reservation->diningTable?->tableGroup) ({{ $reservation->diningTable->tableGroup->name }})@endif  
Party size: {{ $reservation->party_size }}@if(($reservation->additional_chairs ?? 0) > 0) (+{{ $reservation->additional_chairs }} extra chairs)@endif  
Status: **{{ $reservation->statusLabel() }}**
</x-mail::panel>

@if ($reservation->notes)
**Notes / pre-order**

{{ $reservation->notes }}
@endif

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
