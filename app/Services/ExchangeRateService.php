<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class ExchangeRateService
{
    public function syncLatestRates(): array
    {
        $baseCurrency = $this->baseCurrency();
        $apiKey = $this->apiKey();

        $endpoint = $this->baseUrl() . '/' . rawurlencode($apiKey) . '/latest/' . rawurlencode($baseCurrency);

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(2, 300)
            ->get($endpoint);

        $payload = $response->json();

        if (! $response->successful()) {
            $message = is_array($payload) && isset($payload['error-type'])
                ? 'ExchangeRate-API request failed: ' . $payload['error-type']
                : 'ExchangeRate-API request failed with HTTP status ' . $response->status();

            Log::warning($message, ['endpoint' => $endpoint, 'response' => $payload]);
            throw new RuntimeException($message);
        }

        if (! is_array($payload) || ($payload['result'] ?? null) !== 'success') {
            $errorType = is_array($payload) ? ($payload['error-type'] ?? 'unknown-error') : 'invalid-json';
            throw new RuntimeException('ExchangeRate-API returned an error: ' . $errorType);
        }

        $rates = $payload['conversion_rates'] ?? null;

        if (! is_array($rates)) {
            throw new RuntimeException('ExchangeRate-API response did not include conversion rates.');
        }

        $syncedAt = Carbon::now();
        $syncedCount = 0;

        foreach ($rates as $currency => $rate) {
            $currency = strtoupper(trim((string) $currency));

            if (! preg_match('/^[A-Z]{3}$/', $currency) || ! is_numeric($rate)) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                [
                    'base_currency' => $baseCurrency,
                    'currency' => $currency,
                ],
                [
                    'rate' => $this->formatRate($rate),
                    'rate_updated_at' => $syncedAt,
                ]
            );

            $syncedCount++;
        }

        return [
            'synced_count' => $syncedCount,
            'base_currency' => $baseCurrency,
            'base_url' => $this->baseUrl(),
            'synced_at' => $syncedAt->toDateTimeString(),
        ];
    }

    public function convert(string $fromCurrency, string $toCurrency, $amount): array
    {
        $fromCurrency = $this->normalizeCurrencyCode($fromCurrency);
        $toCurrency = $this->normalizeCurrencyCode($toCurrency);

        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Amount must be numeric.');
        }

        $amount = $this->formatAmount($amount);

        $fromRate = $this->resolveRate($fromCurrency);
        $toRate = $this->resolveRate($toCurrency);

        $convertedAmount = $this->bcDiv(
            $this->bcMul($amount, $toRate, 12),
            $fromRate,
            2
        );

        $crossRate = $this->bcDiv($toRate, $fromRate, 8);

        return [
            'original_amount' => (float) $amount,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'converted_amount' => (float) $convertedAmount,
            'rate' => (float) $crossRate,
            'last_updated' => $this->lastUpdatedForCurrencies($fromCurrency, $toCurrency),
        ];
    }

    private function resolveRate(string $currency): string
    {
        $baseCurrency = $this->baseCurrency();

        if ($currency === $baseCurrency) {
            return '1.00000000';
        }

        $rate = ExchangeRate::where('base_currency', $baseCurrency)
            ->where('currency', $currency)
            ->value('rate');

        if ($rate === null) {
            throw new InvalidArgumentException("Currency rate for {$currency} is not available.");
        }

        return $this->formatRate($rate);
    }

    private function lastUpdatedForCurrencies(string $fromCurrency, string $toCurrency): ?string
    {
        $baseCurrency = $this->baseCurrency();

        $dates = ExchangeRate::where('base_currency', $baseCurrency)
            ->whereIn('currency', [$fromCurrency, $toCurrency])
            ->pluck('rate_updated_at')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates->sortByDesc(fn ($date) => $date->timestamp)
            ->first()
            ->toDateTimeString();
    }

    private function baseUrl(): string
    {
        return rtrim(
            (string) config('services.exchange_rate_api.base_url', 'https://v6.exchangerate-api.com/v6'),
            '/'
        );
    }

    private function apiKey(): string
    {
        $apiKey = (string) config('services.exchange_rate_api.key');

        if ($apiKey === '') {
            throw new RuntimeException('ExchangeRate-API key is not configured.');
        }

        return $apiKey;
    }

    private function baseCurrency(): string
    {
        return $this->normalizeCurrencyCode(
            (string) config('services.exchange_rate_api.base_currency', 'AED')
        );
    }

    private function normalizeCurrencyCode(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency code must be a valid ISO 4217 three-letter code.');
        }

        return $currency;
    }

    private function formatRate($value): string
    {
        return number_format((float) $value, 8, '.', '');
    }

    private function formatAmount($value): string
    {
        return number_format((float) $value, 6, '.', '');
    }

    private function bcMul(string $left, string $right, int $scale): string
    {
        return function_exists('bcmul')
            ? bcmul($left, $right, $scale)
            : (string) round(((float) $left) * ((float) $right), $scale);
    }

    private function bcDiv(string $left, string $right, int $scale): string
    {
        if ((float) $right === 0.0) {
            throw new RuntimeException('Division by zero during currency conversion.');
        }

        return function_exists('bcdiv')
            ? bcdiv($left, $right, $scale)
            : (string) round(((float) $left) / ((float) $right), $scale);
    }
}