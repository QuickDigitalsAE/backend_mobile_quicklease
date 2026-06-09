<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $adminAddress = "luxe@quicklease.ae";
    public $isAdmin;
    public $fromAddress;
    public $fromName;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $isAdmin, $fromAddress = null, $fromName = null)
    {
        $this->data = $data;
        $this->isAdmin = $isAdmin;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    
    public function build()
    {
        return $this->from($this->fromAddress, $this->fromName)
                    ->subject('Landing Page - Enquire Form')
                    ->markdown('emails.LandingEnquire')
                    ->with([
                        'data' => $this->data,
                        'isAdmin' => $this->isAdmin
                    ]);            
    }
}
