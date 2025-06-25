<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $funeralUser;
    public $client; // Optional: the client requesting the agent
    public $booking; // Optional: booking context

    /**
     * Create a new message instance.
     */
    public function __construct($url, $funeralUser, $client = null, $booking = null)
    {
        $this->url = $url;
        $this->funeralUser = $funeralUser;
        $this->client = $client;
        $this->booking = $booking;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('You are invited to become an Agent')
            ->view('funeral.agents.email-invitation');
    }
}
