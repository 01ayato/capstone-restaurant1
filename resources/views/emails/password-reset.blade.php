<x-mail::message>
# Reset your password

Hi {{ $user->first_name ?? $user->name }},

We received a request to reset the password for your **{{ $settings->name }}** account. Click the button below to choose a new password.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes. If you didn't request a password reset, no further action is needed — your password will remain unchanged.

If the button above doesn't work, copy and paste this URL into your browser:

{{ $resetUrl }}

Thanks,<br>
{{ $settings->name }}
</x-mail::message>
