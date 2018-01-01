<?php

namespace App\Mail;

use App\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class InvitationEmail
 * @package App\Mail
 */
class InvitationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;

    /**
     * InvitationEmail constructor.
     * @param Invitation $invitation
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view.name');
    }
}
