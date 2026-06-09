<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EtisalatPaymentService
{
    protected $baseUrl;
    protected $merchantId;
    protected $accessKey;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.etisalat.base_url');
        $this->merchantId = config('services.etisalat.merchant_id');
        $this->accessKey = config('services.etisalat.access_key');
        $this->secretKey = config('services.etisalat.secret_key');
    }

    public function createPayment($amount, $currency, $orderId, $returnUrl)
    {
        $response = Http::post($this->baseUrl . '/payment/initiate', [
            'merchantId' => $this->merchantId,
            'amount' => $amount,
            'currency' => $currency,
            'orderId' => $orderId,
            'returnUrl' => $returnUrl,
            'accessKey' => $this->accessKey,
        ]);

        return $response->json();
    }
}
