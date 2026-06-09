<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailEnquiryForm extends Mailable
{
    use Queueable, SerializesModels;

    public $enquiryDetail;
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
    public function __construct($enquiryDetail, $template, $language, $isAdmin, $fromAddress = null, $fromName = null)
    {
        $this->enquiryDetail = $enquiryDetail;
        $this->template = $template;
        $this->language = $language;
        $this->isAdmin = $isAdmin;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->subject = $this->getSubjectByLanguage($language, $enquiryDetail);
    }
    
    
    protected function getSubjectByLanguage($language, $enquiryDetail)
    {
         if($enquiryDetail['updated']){
            $subjects = [
                'en' => 'Client Enquiry Updated',
                'ar' => 'تم تحديث الاستفسار عن العميل'
            ];
         }else{
             $subjects = [
                'en' => 'New Client Enquiry Submitted',
                'ar' => 'تم تقديم استفسار العميل الجديد'
            ];
         }
        
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
                        'enquiryDetail' => $this->enquiryDetail,
                        'isAdmin' => $this->isAdmin
                    ]);            
    }
}
