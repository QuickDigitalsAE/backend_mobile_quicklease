<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestimonialMail extends Mailable
{
    use Queueable, SerializesModels;

    public $testimonialDetail;
    public $subject;
    public $template;
    public $language;
    public $isAdmin;
    public $fromAddress;
    public $fromName;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($testimonialDetail, $template, $language, $isAdmin, $fromAddress = null, $fromName = null)
    {
        $this->testimonialDetail = $testimonialDetail;
        $this->template = $template;
        $this->language = $language;
        $this->isAdmin = $isAdmin;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->subject = $this->getSubjectByLanguage($language);
    }
    
    
    protected function getSubjectByLanguage($language)
    {
        $subjects = [
            'en' => 'New Customer Review',
            'ar' => 'تقييم جديد من عميل',
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
        return $this->from($this->fromAddress, $this->fromName)
                    ->subject($this->subject)
                    ->markdown($this->template)
                    ->with([
                        'testimonialDetail' => $this->testimonialDetail,
                        'isAdmin' => $this->isAdmin
                    ]);
    }
}
