<?php

namespace App\Services;

use App\Mail\ReservationStatusMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReservationMailService
{
    public function recipientFor(Reservation $reservation): ?string
    {
        $reservation->loadMissing('user');

        $email = trim((string) ($reservation->email ?: $reservation->user?->email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function sendCreated(Reservation $reservation): bool
    {
        return $this->send($reservation, ReservationStatusMail::EVENT_CREATED);
    }

    public function sendStatusUpdate(Reservation $reservation, ?string $previousStatus = null): bool
    {
        return $this->send(
            $reservation,
            ReservationStatusMail::EVENT_STATUS,
            $previousStatus,
        );
    }

    public function resend(Reservation $reservation): bool
    {
        return $this->send($reservation, ReservationStatusMail::EVENT_RESEND);
    }

    public function send(
        Reservation $reservation,
        string $event = ReservationStatusMail::EVENT_STATUS,
        ?string $previousStatus = null,
    ): bool {
        $to = $this->recipientFor($reservation);

        if ($to === null) {
            return false;
        }

        try {
            Mail::to($to)->send(new ReservationStatusMail($reservation, $event, $previousStatus));

            return true;
        } catch (Throwable $e) {
            Log::warning('Reservation email failed', [
                'reservation_id' => $reservation->id,
                'to' => $to,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
