<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Postzy test email');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test');
    }
}
