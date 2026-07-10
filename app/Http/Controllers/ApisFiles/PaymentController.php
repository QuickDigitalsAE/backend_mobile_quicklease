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
        $request->validate([
            'booking_id'   => ['required', 'integer', 'exists:bookings,id'],
            'order_number' => ['required', 'string', 'max:100'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'currency'     => ['nullable', 'string', 'size:3'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Etisalat EPG Configuration
        |--------------------------------------------------------------------------
        |
        | Sandbox endpoint:
        | https://demo-ipg.ctdev.comtrust.ae:2443
        |
        | Production endpoint:
        | https://ipg.comtrust.ae:2443
        |
        */

        $merchantId = '800022000';
        $customer = 'Demo Merchant';
        $username = 'Demo_fY9c';
        $password = 'Comtrust@20182018';
        $store = '0000';
        $terminal = '0000';
        $apiUrl = 'https://demo-ipg.ctdev.comtrust.ae:2443';
        $returnPath = 'https://mobile-api.quicklease.ae/epg-redirect';
        $callBackUrl = 'https://mobile-api.quicklease.ae/api/callback/en/';

        $caBundlePath = storage_path('certs/ipg-ca-bundle.pem');

        $bookingId  = (int) $request->input('booking_id');
        $orderId    = trim((string) $request->input('order_number'));
        $currency   = strtoupper($request->input('currency', 'AED'));
        $amount     = number_format((float) $request->input('amount'), 2, '.', '');
        $booking    = Booking::findOrFail($bookingId);

        if (empty($username) || empty($password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Etisalat gateway credentials are not configured.',
                'data'    => [],
            ], 500);
        }

        $clientHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept'       => 'application/json',
            'Authorization'=> 'Basic ' . base64_encode($username . ':' . $password),
        ];

        /*
        |--------------------------------------------------------------------------
        | Shared transaction details
        |--------------------------------------------------------------------------
        */

        $transactionData = [
            'Currency'        => $currency,
            'ReturnPath'      => $returnPath,
            'TransactionHint' => 'CPT:Y;VCC:Y;',
            'OrderID'         => $orderId,
            'Channel'         => 'Web',
            'Amount'          => $amount,
            'Customer'        => $customer,
            'Store'           => $store,
            'Terminal'        => $terminal,
            'Language'        => in_array($lang, ['en', 'ar'], true) ? $lang : 'en',
            'OrderName'       => 'Car Rental Mobile Paybill',
            "UserName"        => $username,
            "Password"        => $password
        ];

        $clientOptions = [
            'headers' => $clientHeaders,
            'connect_timeout' => 30,
            'timeout'         => 60,
            'http_errors'     => false,
        ];

        /*
        |--------------------------------------------------------------------------
        | SSL Verification
        |--------------------------------------------------------------------------
        */

        if (file_exists($caBundlePath)) {
            $clientOptions['verify'] = $caBundlePath;
        } else {
            // Sandbox only. Production mein certificate verification disable na karein.
            $clientOptions['verify'] = app()->environment('production');
        }

        try {
            $client = new Client($clientOptions);

            /*
            |--------------------------------------------------------------------------
            | Step 1: Generate Token
            |--------------------------------------------------------------------------
            */

            $generateTokenPayload = [
                'GenerateToken' => array_merge($transactionData, [
                    'UserName'  => $username,
                    'Password'  => $password,
                    'MerchantID' => $merchantId,
                ]),
            ];

            $tokenResponse = $client->post($apiUrl, [
                'json' => $generateTokenPayload,
            ]);

            $tokenResult = json_decode(
                (string) $tokenResponse->getBody(),
                true
            ) ?: [];

            $tokenHttpStatus = $tokenResponse->getStatusCode();

            $authenticationToken =
                data_get($tokenResult, 'Transaction.AuthenticationToken')
                ?? data_get($tokenResult, 'AuthenticationToken')
                ?? data_get($tokenResult, 'GenerateToken.AuthenticationToken');

            $tokenTransactionId =
                data_get($tokenResult, 'Transaction.TransactionID')
                ?? data_get($tokenResult, 'TransactionID');

            $tokenResponseCode =
                data_get($tokenResult, 'Transaction.ResponseCode')
                ?? data_get($tokenResult, 'ResponseCode');

            if (
                $tokenHttpStatus < 200 ||
                $tokenHttpStatus >= 300 ||
                empty($authenticationToken)
            ) {
                return response()->json([
                    'status'  => false,
                    'message' => $this->extractGatewayMessage($tokenResult)
                        ?: 'Failed to generate Etisalat authentication token.',
                    'data' => [
                        'http_status'   => $tokenHttpStatus,
                        'response_code' => $tokenResponseCode,
                        'gateway'       => $tokenResult,
                    ],
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Step 2: Registration
            |--------------------------------------------------------------------------
            */

            $registrationData = array_merge($transactionData, [
                'AuthenticationToken' => $authenticationToken,
            ]);

            /*
            * GenerateToken response mein TransactionID mila ho to Registration
            * request mein forward kar diya jayega.
            */
            if (!empty($tokenTransactionId)) {
                $registrationData['TransactionID'] = $tokenTransactionId;
            }

            $registrationPayload = [
                'Registration' => $registrationData,
            ];

            $registrationResponse = $client->post($apiUrl, [
                'json' => $registrationPayload,
            ]);

            $registrationResult = json_decode(
                (string) $registrationResponse->getBody(),
                true
            ) ?: [];

            $registrationHttpStatus = $registrationResponse->getStatusCode();

            $registrationResponseCode =
                data_get($registrationResult, 'Transaction.ResponseCode')
                ?? data_get($registrationResult, 'ResponseCode');

            $transactionId =
                data_get($registrationResult, 'Transaction.TransactionID')
                ?? data_get($registrationResult, 'TransactionID')
                ?? $tokenTransactionId;

            $paymentPortal =
                data_get($registrationResult, 'Transaction.PaymentPortal')
                ?? data_get($registrationResult, 'Transaction.PaymentPage')
                ?? data_get($registrationResult, 'PaymentPortal')
                ?? data_get($registrationResult, 'PaymentPage');

            if (
                $registrationHttpStatus < 200 ||
                $registrationHttpStatus >= 300 ||
                (string) $registrationResponseCode !== '0' ||
                empty($transactionId) ||
                empty($paymentPortal)
            ) {
                return response()->json([
                    'status'  => false,
                    'message' => $this->extractGatewayMessage($registrationResult)
                        ?: 'Etisalat payment registration failed.',
                    'data' => [
                        'http_status'   => $registrationHttpStatus,
                        'response_code' => $registrationResponseCode,
                        'gateway'       => $registrationResult,
                    ],
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Store Transaction ID
            |--------------------------------------------------------------------------
            */

            $booking->transaction_id = $transactionId;
            $booking->save();

            /*
            |--------------------------------------------------------------------------
            | Frontend redirects customer to PaymentPortal
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status'  => true,
                'message' => 'Payment initiated successfully.',
                'data' => [
                    'transaction_id'      => $transactionId,
                    'authentication_token'=> $authenticationToken,
                    'payment_url'         => $paymentPortal,
                    'payment_portal'      => $paymentPortal,
                    'callback_url'        => $callBackUrl.base64_encode($transactionId),
                ],
            ], 200);
        } catch (\GuzzleHttp\Exception\ConnectException $exception) {
            report($exception);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to connect with Etisalat Payment Gateway.',
                'data' => [
                    'error' => $exception->getMessage(),
                ],
            ], 503);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status'  => false,
                'message' => 'Payment initialization failed.',
                'data' => [
                    'error' => $exception->getMessage(),
                ],
            ], 500);
        }
    }
    
    public function paymentCallback($lang, $transaction_id)
    {
        
        // Decode
        $decoded_transaction_id = base64_decode($transaction_id);
        
        $booking = Booking::where('transaction_id', $decoded_transaction_id)->first();
        
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
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($ETISALAT_USER . ':' . $ETISALAT_PASS)
        ];

        $client = new Client();
        $response = $client->post($ETISALAT_API_URL, [
            'json' => [
                "Finalization" => [
                    "TransactionID" => $decoded_transaction_id,
                    "Customer" => $ETISALAT_CUSTOMER
                ]
            ],
            'verify' => false, // only in demo
            'headers' => $headers,
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

    private function extractGatewayMessage(array $payload): ?string
    {
        return $payload['message']
            ?? $payload['Message']
            ?? $payload['error']
            ?? $payload['ErrorMessage']
            ?? $payload['ErrorDescription']
            ?? data_get($payload, 'Transaction.ResponseDescription')
            ?? data_get($payload, 'Transaction.ResponseMessage')
            ?? data_get($payload, 'Transaction.ErrorMessage')
            ?? data_get($payload, 'Transaction.ErrorDescription');
    }

}