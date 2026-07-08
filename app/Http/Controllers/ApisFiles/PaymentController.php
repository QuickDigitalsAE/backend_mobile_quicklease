<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Services\EtisalatPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Models\ProductTranslation;
use GuzzleHttp\Client;
use App\Mail\PaymentMail;

class PaymentController extends Controller
{
    public function executeEtisalatPayment(Request $request, $lang)
    {
        $ETISALAT_CUSTOMER = 'Demo Merchant';
        $ETISALAT_USER = 'Demo_fY9c';
        $ETISALAT_PASS = 'Comtrust@20182018';
        $ETISALAT_STORE = '0000';
        $ETISALAT_TERMINAL = '0000';
        $ETISALAT_API_URL = 'https://demo-ipg.ctdev.comtrust.ae:2443';
        $ETISALAT_RETURN_PATH = 'https://testqds.com/new_quicklease/epg-redirect';
        
        $caBundlePath = storage_path('certs/ipg-ca-bundle.pem');
        
        
        // $ETISALAT_CUSTOMER = config('app.etisalat_customer');
        // $ETISALAT_USER = config('app.etisalat_user');
        // $ETISALAT_PASS = config('app.etisalat_pass');
        // $ETISALAT_API_URL = config('app.etisalat_api_url');
        // $ETISALAT_STORE = 'eCommerce';
        // $ETISALAT_TERMINAL = 'eCommerce';
        // $ETISALAT_RETURN_PATH = 'https://api.quicklease.ae/epg-redirect';
        
        $amount = $request->input('amount');
        $order_number = $request->input('order_number');
        $booking_id = $request->input('booking_id');
        $encryptedBookingId = Crypt::encryptString($booking_id);

        $data = [
            "Registration" => [
                "Currency" => "AED",
                "ReturnPath" => $ETISALAT_RETURN_PATH,
                "TransactionHint" => "CPT:Y;VCC:Y;",
                "OrderID" => $order_number,
                "Channel" => "Web",
                "Amount" => $amount,
                "Customer" => $ETISALAT_CUSTOMER,
                "Store" => $ETISALAT_STORE,
                "Terminal" => $ETISALAT_TERMINAL,
                "Language" => $lang,
                "OrderName" => "Car Rental Paybill",
                "UserName" => $ETISALAT_USER,
                "Password" => $ETISALAT_PASS
            ]
        ];

        try {
            $client = new Client();
            
            // Concatenate in the format: username:password
            $credentials = $ETISALAT_USER . ':' . $ETISALAT_PASS;
            
            // Encode to Base64
            $base64Credentials = base64_encode($credentials);
            
            $response = $client->post($ETISALAT_API_URL, [
                'json' => $data,
                'verify' => $caBundlePath, // Disable SSL verification for testing (not recommended in production)
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic '.$base64Credentials
                ],
            ]);
            
            $result = json_decode($response->getBody(), true);
            // echo "<pre>";
            // print_r($result);
            // echo "</pre>";
            // die();
            if (!empty($booking_id) && isset($result['Transaction']) && !empty($result['Transaction']['TransactionID'])) {
                $transactionId = $result['Transaction']['TransactionID'] ?? null;
                
                $booking = Booking::find($booking_id);
                $booking->transaction_id = $transactionId;
                $booking->save();
                
                return response()->json([
                    'status' => true,
                    'data' => $result
                ],200);
            } else {
                // Fallback if TransactionID is missing
                return response()->json([
                    'status' => false,
                    'data' => []
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 400);
        }
    }
    
    public function paymentCallback($lang, $transaction_id)
    {   
        $booking = Booking::where('transaction_id', $transaction_id)->first();
        
        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 200);
        }
        
        $ETISALAT_CUSTOMER = 'Demo Merchant';
        $ETISALAT_USER = 'Demo_fY9c';
        $ETISALAT_PASS = 'Comtrust@20182018';
        $ETISALAT_STORE = '0000';
        $ETISALAT_TERMINAL = '0000';
        $ETISALAT_API_URL = 'https://demo-ipg.ctdev.comtrust.ae:2443';
        
        // $ETISALAT_CUSTOMER = config('app.etisalat_customer');
        // $ETISALAT_USER = config('app.etisalat_user');
        // $ETISALAT_PASS = config('app.etisalat_pass');
        // $ETISALAT_API_URL = config('app.etisalat_api_url');
        // $ETISALAT_RETURN_PATH = config('app.etisalat_return_path');
        
        // Concatenate in the format: username:password
        $credentials = $ETISALAT_USER . ':' . $ETISALAT_PASS;
        
        // Encode to Base64
        $base64Credentials = base64_encode($credentials);
        
        $client = new Client();
        $response = $client->post($ETISALAT_API_URL, [
            'json' => [
                "Finalization" => [
                    "TransactionID" => $transaction_id,
                    "Customer" => $ETISALAT_CUSTOMER
                ]
            ],
            'verify' => false, // only in demo
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic '.$base64Credentials
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        $ResponseDescription = $result['Transaction']['ResponseDescription'] ?? "";
        $transactionStatus = $result['Transaction']['ResponseClassDescription'] ?? "";
        
        if($transactionStatus == 'Success'){
            $booking->payment_status = 'paid';
            $booking->save();
            
            // Define messages for different languages
            $messages = [
                'en' => 'The payment transaction for the car rental booking has been completed.',
                'ar' => "تم إتمام عملية الدفع لحجز تأجير السيارة."
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];
            $status = true;
            
            $product_id = $booking->product_id;
            $orderNumber = $booking->order_number;
            $first_name = $booking->first_name;
            $last_name  = $booking->last_name;
            $phone_number = $booking->phone_number;
            $email = $booking->email;
            $pickup_city = $booking->pickup_city;
            $pickup_address = $booking->pickup_address;
            $pickup_date_time = $booking->pickup_date_time;
            $return_city = $booking->return_city;
            $return_address = $booking->return_address;
            $return_date_time = $booking->return_date_time;
            $total_days = $booking->total_days;
            $promo_code = $booking->promo_code;
            $summary_total_amount = $booking->summary_total_amount;
            $summary_total_vat = $booking->summary_total_vat;
            $total_discount_incl_vat = $booking->total_discount_incl_vat;
            $total_price = $booking->total_price;
            $coverages_extras = json_decode($booking->extras, true);
            $booking_page_slug = $booking->booking_page_slug;
            $payment_type = $booking->payment_type;
            $payment_status = $booking->payment_status;
            $booking_status = $booking->booking_status;
            $accept_terms = $booking->accept_terms;
            $valid_driving_license = $booking->valid_driving_license;
            $valid_passport = $booking->valid_passport;
            $driver_age_above = $booking->driver_age_above;
            $card_payment = $booking->card_payment;
            $partial_percentage = $booking->partial_percentage;
            $partial_amount = $booking->partial_amount;
            $transaction_id = $booking->transaction_id;

            // Get translation based on language or default 'en' based
            $translations = ProductTranslation::where('product_id', $product_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            if (isset($translations[$lang])) {
                $translatedData = json_decode($translations[$lang]->field_values, true);
            } elseif (isset($translations['en'])) {
                $translatedData = json_decode($translations['en']->field_values, true);
            } else {
                // Fallback if no translations exist
                $translatedData = [];
            }
            
            $product_title = $translatedData['product_title'] ?? "";
            
            // Combine order details, tracking data, and order items
            $bookingDetail = [
                    'product_title' => $product_title,
                    'order_number' => $orderNumber,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone_number' => $phone_number,
                    'email' => $email,
                    'pickup_city' => $pickup_city,
                    'pickup_address' => $pickup_address,
                    'pickup_date_time' => $pickup_date_time,
                    'return_city' => $return_city,
                    'return_address' => $return_address,
                    'return_date_time' => $return_date_time,
                    'total_days' => $total_days,
                    'summary_total_amount' => $summary_total_amount,
                    'summary_total_vat' => $summary_total_vat,
                    'total_discount_incl_vat' => $total_discount_incl_vat,
                    'total_price' => $total_price ? $total_price: "-",
                    'booking_page_slug' => $booking_page_slug,
                    'payment_type' => str_replace("_", " ", $payment_type),
                    'payment_status' => $payment_status,
                    'booking_status' => $booking_status,
                    'card_payment' => str_replace("_", " ", $card_payment),
                    'partial_percentage' => $partial_percentage,
                    'partial_amount' => $partial_amount,
                    'transaction_id' => $transaction_id,
                ];
            
            
            $template = 'emails.payment_templates.'.$lang.'_carpayment'; // Example for Arabic template
            
            $mailerConfig = config('mail.mailers.main');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            // Send email to client with form details
            Mail::mailer('main')
                    ->to($email)
                    ->send(new PaymentMail($bookingDetail, $template, $lang, false, $fromAddress, $fromName));    
                
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('main')
                ->to($adminAddress)
                ->send(new PaymentMail($bookingDetail, $template, $lang, true, $fromAddress, $fromName));
            
        }else{

            $orderNumber = $booking->order_number;
            $transaction_id = $booking->transaction_id;

            // Define messages for different languages
            $messages = [
                'en' => 'Transaction status : Failed, please try again',
                'ar' => 'حالة المعاملة : فشلت، يرجى المحاولة مرة أخرى'
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];
            $status = false;
        }
        
        return response()->json([
                    'status' => $status,
                    'message' => $message,
                    'data' => [
                        'order_number' => $orderNumber,
                        'transaction_id' => $transaction_id
                        ]
                ],200);
    }

    public function verifyPayment(Request $request)
    {
        $client = new Client();
        
        $ETISALAT_CUSTOMER = config('app.etisalat_customer');
        $ETISALAT_USER = config('app.etisalat_user');
        $ETISALAT_PASS = config('app.etisalat_pass');
        $ETISALAT_API_URL = config('app.etisalat_api_url');
        $ETISALAT_RETURN_PATH = config('app.etisalat_return_path');

        $response = $client->post($ETISALAT_API_URL. '/status', [
            'json' => [
                'merchantId' => env('ETISALAT_MID'),
                'userName' => $ETISALAT_USER,
                'password' => $ETISALAT_PASS,
                'transactionReference' => $request->transactionReference
            ]
        ]);
    
        $data = json_decode($response->getBody(), true);
    
        return response()->json($data);
    }

}
