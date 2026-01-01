<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExceptionOccurred extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The body of the message.
     *
     * @var string
     */
    public $html;


    /**
     * Create a new message instance.
     *
     * @param $html
     */
    public function __construct($html) {
        $this->html = $html;
    }

    /**
     * Get the message envelope.
     */
    public function envelope() : Envelope {
        return new Envelope(
            subject: '[stock-analyzer] Exception Occured',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content() : Content {
        return new Content(
            view: 'email.exception',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments() : array {
        return [];
    }
}
