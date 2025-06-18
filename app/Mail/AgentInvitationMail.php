<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $funeralUser;

    public function __construct($url, $funeralUser)
    {
        $this->url = $url;
        $this->funeralUser = $funeralUser;
    }

    public function build()
    {
        return $this->subject('You are invited to become an Agent')
            ->view('funeral.agents.email-invitation');
    }
}
