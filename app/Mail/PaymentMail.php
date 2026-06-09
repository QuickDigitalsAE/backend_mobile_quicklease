<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bookingDetail;
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
    public function __construct($bookingDetail, $template, $language, $isAdmin, $fromAddress = null, $fromName = null)
    {
        $this->bookingDetail = $bookingDetail;
        $this->template = $template;
        $this->language = $language;
        $this->isAdmin = $isAdmin;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->subject = $this->getSubjectByLanguage($language, $isAdmin);
    }
    
    
    protected function getSubjectByLanguage($language, $isAdmin)
    {
         if($isAdmin){
            $subjects = [
                'en' => 'Payment Received! Your Car Rental Booking is Confirmed.',
                'ar' => 'تم استلام الدفع! تم تأكيد حجز سيارتك.'
            ];
        }else{
            $subjects = [
                'en' => 'Thank You! Your Car Rental Booking is Complete.',
                'ar' => 'شكرًا لك! تم إكمال حجز تأجير السيارة.'
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
                        'bookingDetail' => $this->bookingDetail,
                        'isAdmin' => $this->isAdmin
                    ]);
    }
}
