<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\RestaurantSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public const EVENT_CREATED = 'created';

    public const EVENT_STATUS = 'status';

    public const EVENT_RESEND = 'resend';

    public function __construct(
        public Reservation $reservation,
        public string $event = self::EVENT_STATUS,
        public ?string $previousStatus = null,
    ) {
        $this->reservation->loadMissing(['diningTable.tableGroup', 'user']);
    }

    public function envelope(): Envelope
    {
        $restaurant = RestaurantSetting::current()->name;
        $status = $this->reservation->statusLabel();

        $subject = match ($this->event) {
            self::EVENT_CREATED => "Reservation received — {$restaurant}",
            default => "Reservation {$status} — {$restaurant}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reservation-status',
            with: [
                'reservation' => $this->reservation,
                'event' => $this->event,
                'previousStatus' => $this->previousStatus,
                'settings' => RestaurantSetting::current(),
                'headline' => $this->headline(),
                'intro' => $this->intro(),
            ],
        );
    }

    private function headline(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'We received your reservation',
            self::EVENT_RESEND => 'Your reservation details',
            default => 'Reservation '.$this->reservation->statusLabel(),
        };
    }

    private function intro(): string
    {
        $name = $this->reservation->customer_name;

        return match ($this->event) {
            self::EVENT_CREATED => "Hi {$name}, thanks for booking with us. Here are your reservation details. We'll follow up once it's confirmed.",
            self::EVENT_RESEND => "Hi {$name}, here is a copy of your current reservation details.",
            default => match ($this->reservation->status) {
                Reservation::STATUS_CONFIRMED => "Hi {$name}, your reservation is confirmed. We look forward to seeing you!",
                Reservation::STATUS_CANCELLED => "Hi {$name}, your reservation has been cancelled. If this was a mistake, please contact us.",
                Reservation::STATUS_COMPLETED => "Hi {$name}, thanks for dining with us. We hope to see you again soon.",
                default => "Hi {$name}, your reservation status is now {$this->reservation->statusLabel()}.",
            },
        };
    }
}
