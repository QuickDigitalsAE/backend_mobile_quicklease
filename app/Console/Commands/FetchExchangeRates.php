<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ExchangeRate;

class FetchExchangeRates extends Command
{
    protected $signature = 'exchange-rates:fetch';
    protected $description = 'Fetch latest exchange rates from ExchangeRate-API';

    public function handle()
    {
        $baseUrl = env('EXCHANGE_RATE_API_BASE_URL');
        $apiKey = env('EXCHANGE_RATE_API_KEY');
        $base = env('EXCHANGE_RATE_BASE', 'AED');

        $response = Http::get("{$baseUrl}/{$apiKey}/latest/{$base}");

        if (!$response->successful() || $response->json('result') !== 'success') {
            $this->error('Exchange rate API failed');
            return Command::FAILURE;
        }

        foreach ($response->json('conversion_rates') as $currency => $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'base_currency' => $base,
                    'currency' => $currency,
                ],
                [
                    'rate' => $rate,
                    'rate_updated_at' => now(),
                ]
            );
        }

        $this->info('Exchange rates updated successfully');
        return Command::SUCCESS;
    }
}