<?php

namespace App\Mail;

use App\Models\RestaurantSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public function __construct(
        public User $user,
        public string $token,
    ) {
        $this->resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    public function envelope(): Envelope
    {
        $restaurant = RestaurantSetting::current()->name;

        return new Envelope(subject: "Reset your password — {$restaurant}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-reset',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'expireMinutes' => (int) config('auth.passwords.users.expire', 60),
                'settings' => RestaurantSetting::current(),
            ],
        );
    }
}
