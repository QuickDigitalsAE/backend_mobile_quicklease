<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingMail extends Mailable
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
        $this->subject = $this->getSubjectByLanguage($language, $bookingDetail);
    }
    
    
    protected function getSubjectByLanguage($language, $bookingDetail)
    {
         if($bookingDetail['change_status']){
            $subjects = [
                'en' => "Car Rental Booking Status Successfully Updated",
                'ar' => "تم تحديث حالة حجز تأجير السيارات بنجاح"
            ];    
        }else{
            $subjects = [
                'en' => 'Car Rental Booking Details.',
                'ar' => 'تفاصيل حجز تأجير السيارات.'
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
