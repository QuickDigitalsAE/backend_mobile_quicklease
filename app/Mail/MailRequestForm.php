<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailRequestForm extends Mailable
{
    use Queueable, SerializesModels;

    public $requestDetail;
    public $subject;
    public $isAdmin;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($requestDetail, $isAdmin)
    {
        $this->requestDetail = $requestDetail;
        $this->isAdmin = $isAdmin;
        $this->subject = $this->getSubjectByLanguage('en');
    }
    
    
    protected function getSubjectByLanguage($language)
    {
        $subjects = [
            'en' => 'New request form submitted',
            'ar' => ''
        ];
        
        return $subjects[$language] ?? $subjects['en']; // Default to English if language not found
    }
    
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->markdown('emails.request_form')
                    ->with([
                        'requestDetail' => $this->requestDetail,
                        'isAdmin' => $this->isAdmin
                    ]);
    }
}
