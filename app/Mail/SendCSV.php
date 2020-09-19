<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCSV extends Mailable
{
    use Queueable, SerializesModels;

    protected $attachment;
    public    $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attachment, $subject)
    {
        $this->attachment = $attachment;
        $this->subject    = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.bets-csv')
                    ->subject($this->subject)
                    ->attach($this->attachment);
    }
}
