<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteMemberMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    private $member;
 
    private $workenv;
    private $token;
    private $idwork;
    public function __construct($member,  $workenv, $token, $idwork)
    {   
        //
        $this->member = $member;
      
        $this->workenv = $workenv;
        $this->token = $token;
        $this->idwork = $idwork;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a un espacio de trabajo',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacionmiembro',
            with:[
                'name' => $this->member,
                'workenv' => $this->workenv,
                'token' => $this->token,
                'idwork' => $this->idwork
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
