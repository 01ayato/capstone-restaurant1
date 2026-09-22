<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\RestaurantSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback,
        public string $subjectLine,
        public string $body,
    ) {
        $this->feedback->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback-reply',
            with: [
                'feedback' => $this->feedback,
                'body' => $this->body,
                'settings' => RestaurantSetting::current(),
            ],
        );
    }
}
