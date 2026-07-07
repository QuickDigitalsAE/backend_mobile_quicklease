<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class CurrencyController extends Controller
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'nullable|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        try {
            $data = $this->exchangeRateService->convert(
                $request->input('from', 'AED'),
                $request->input('to'),
                $request->input('amount')
            );

            return response()->json([
                'status' => true,
                'message' => 'Currency converted successfully.',
                'data' => $data,
            ], 200);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sync()
    {
        try {
            $data = $this->exchangeRateService->syncLatestRates();

            return response()->json([
                'status' => true,
                'message' => 'Exchange rates synced successfully.',
                'data' => $data,
            ], 200);

        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}