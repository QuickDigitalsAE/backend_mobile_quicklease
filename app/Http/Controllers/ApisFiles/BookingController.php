<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\PaymentController;
use App\Http\Controllers\ApisFiles\PromotionController;
use App\Models\Booking;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Catalog;
use App\Models\UserActivityLog;
use App\Models\PeopleVisit;
use App\Models\ProductCoverage;
use App\Models\ProductCoverageTranslation;
use App\Models\ProductRelatedCoverage;
use App\Models\ProductTranslation;
use App\Models\PropertyTranslation;
use App\Models\CatalogTranslation;
use App\Models\BookingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingMail;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Booking View', ['only' => ['bookingList','searchBookingList']]);
        // $this->middleware('permission:Booking Add', ['only' => ['bookingStore']]);
        $this->middleware('permission:Booking Edit', ['only' => ['bookingStatus']]);
        // $this->middleware('permission:Booking Delete', ['only' => ['deleteBooking']]);
    }
    
     /**
     * Display the specified resource.
     */
    public function bookingList(Request $request, $lang, $per_page=12)
    {
        try {
            
            // Define validation rules
            $rules = [
                'from_month'      => 'nullable|string',
                'to_month'        => 'nullable|string',
                'booking_status'  => 'nullable|string',
                'email'           => 'nullable|email',
                'phone_number'        => 'nullable|string',
                'booking_id'      => 'nullable|integer',
            ];
            
            // Validate request
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $status      = $request->input('booking_status');
            $from_month  = $request->input('from_month');
            $to_month    = $request->input('to_month');
            $email       = $request->input('email');
            $phone_number    = $request->input('phone_number');
            $booking_id   = $request->input('booking_id');

            // -------------------------------
            // Main Query with conditional booking_id
            // -------------------------------
            $bookingQuery = Booking::query()
                ->when(!empty($booking_id), function ($query) use ($booking_id) {
                    return $query->where('id', $booking_id);
                })
                ->when(empty($booking_id) && !empty($email), function ($query) use ($email) {
                    return $query->where('email', $email);
                })
                ->when(empty($booking_id) && !empty($phone_number), function ($query) use ($phone_number) {
                    return $query->where('phone_number', $phone_number);
                })
                ->when(empty($booking_id) && !empty($from_month) && !empty($to_month), function ($query) use ($from_month, $to_month) {
                    return $query->whereRaw("DATE_FORMAT(pickup_date_time, '%Y-%m-%d') BETWEEN ? AND ?", [$from_month, $to_month]);
                })
                ->when(empty($booking_id) && !empty($status), function ($query) use ($status) {
                    return $query->where('booking_status', $status);
                })
                ->orderBy('updated_at', 'DESC');
    
            // Fetch single booking if booking_id, otherwise paginate
            if (!empty($booking_id)) {
                $bookings = $bookingQuery->get(); // collection for single booking
            } else {
                $bookings = $bookingQuery->paginate($per_page);
            }
    
            if ($bookings->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Booking not found'], 400);
            }
            
            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $product_id = (int)  $list->product_id;
                $order_number = $list->order_number;
                $transaction_id = $list->transaction_id;
                $first_name = $list->first_name;
                $last_name = $list->last_name;
                $email = $list->email;
		        $phone_number = $list->phone_number;
                $pickup_city = $list->pickup_city;
                $pickup_address = $list->pickup_address;
                $pickup_date_time = $list->pickup_date_time;
                $return_city = $list->return_city;
                $return_address = $list->return_address;
                $return_date_time = $list->return_date_time;
                $car_month  = $list->car_month;
                $car_monthly_price = $list->car_monthly_price;
                $deposit_type = $list->deposit_type;
                $deposit_selected_tab = $list->deposit_selected_tab;
                $deposit_price = $list->deposit_price;
                $total_days = $list->total_days;
                $summary_total_amount = $list->summary_total_amount;
                $summary_total_vat = $list->summary_total_vat;
                $total_discount_incl_vat = $list->total_discount_incl_vat;
                $total_price = $list->total_price;
                $payment_type = $list->payment_type;
                $payment_status = $list->payment_status;
                $booking_status = $list->booking_status;
                $promo_code = $list->promo_code;
                $promo_discount = $list->promo_discount;
                $pay_now_discount = $list->pay_now_discount;
                $booking_page_slug = $list->booking_page_slug;
                $payment_type = str_replace("_", " ", $list->payment_type);
                $accept_terms = $list->accept_terms == 1 ? "Yes" : "No";
                $valid_driving_license = $list->valid_driving_license == 1 ? "Yes" : "No";
                $driver_age_above = $list->driver_age_above == 1 ? "Yes" : "No";
                $card_payment = str_replace("_", " ", $list->card_payment);
                $partial_percentage = $list->partial_percentage;
                $partial_amount = $list->partial_amount;
                $extras = json_decode($list->extras, true);
                
                $translation = ProductTranslation::where('product_id', $product_id)
                                ->whereIn('language', [$lang, 'en'])
                                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                ->first();
                                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                $product_title = $translatedData['product_title'];
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                return [
                        'id' => $id,
                        'product_title' => $product_title,
                        'order_number' => $order_number,
                        'transaction_id' => $transaction_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone_number' => $phone_number,
                        'pickup_city' => $pickup_city,
                        'pickup_address' => $pickup_address,
                        'pickup_date_time' => $pickup_date_time,
                        'return_city' => $return_city,
                        'return_address' => $return_address,
                        'return_date_time' => $return_date_time,
                        'car_month' => str_replace("_"," ",$car_month),
                        'car_monthly_price' => $car_monthly_price,
                        'deposit_type' => $deposit_type,
                        'deposit_selected_tab' => $deposit_selected_tab,
                        'deposit_price' => $deposit_price,
                        'total_days' => $total_days,
                        'summary_total_amount' => $summary_total_amount,
                        'summary_total_vat' => $summary_total_vat,
                        'total_discount_incl_vat' => $total_discount_incl_vat,
                        'grand_total' => $total_price,
                        'payment_type' => str_replace("_"," ",$payment_type),
                        'payment_status' => $payment_status,
                        'booking_status' => $booking_status,
                        'promo_code' => $promo_code,
                        'promo_discount' => $promo_discount,
                        'pay_now_discount' => $pay_now_discount,
                        'booking_page_slug' => $booking_page_slug,
                        'accept_terms' => $accept_terms,
                        'valid_driving_license' => $valid_driving_license,
                        'driver_age_above' => $driver_age_above,
                        'card_payment' => str_replace("_"," ",$card_payment),
                        'partial_percentage' => $partial_percentage,
                        'partial_amount' => $partial_amount,
                        'coverages_extras' => $extras,
                        'notification_detail' => $notification_details
                    ];
                
            });
            
            // -------------------------------
            // Return response
            // -------------------------------
            $response = [
                'status' => true,
                'message' => 'Booking record found.',
                'data' => $bookingList
            ];
    
            // Add pagination if not single booking
            if (empty($booking_id)) {
                $response['pagination'] = [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                ];
            }
    
            return response()->json($response, Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function searchBookingList(Request $request, $lang, $per_page=12)
    {   
        try {
            
            // Define validation rules
            $rules = [
                'search_value'  => 'nullable|string',
                'booking_status'  => 'nullable|string',
                'from_month'  => 'nullable|string',
                'to_month'  => 'nullable|string'
            ];
        
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
        
            // Check if validation fails
            if ($validator->fails()) {
                // Collect all error messages into a single string with line breaks
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422
            }
            
            // Retrieve the search value from the request
            $search_value = $request->input('search_value');
            $from_month = $request->input('from_month');
            $to_month = $request->input('to_month');
            $status = "";
            
            if($request->has('booking_status')){
                $status = $request->input('booking_status');
            }
            
            // Perform the search query
            $bookings = Booking::where('language', $lang)
                            ->where(function ($query) use ($search_value) {
                                $query->where('first_name', 'LIKE', "%{$search_value}%")
                                      ->orWhere('last_name', 'LIKE', "%{$search_value}%")
                                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$search_value}%")
                                      ->orWhere('email', 'LIKE', "%{$search_value}%")
                                      ->orWhere('order_number', 'LIKE', "%{$search_value}%")
                                      ->orWhere('transaction_id', 'LIKE', "%{$search_value}%")
                                      ->orWhere(function ($q) use ($search_value) {
                                          if (in_array(strtolower($search_value), ['paid', 'unpaid'])) {
                                              $q->where('payment_status', strtolower($search_value));
                                          }
                                      });
                            })
                            ->when(!empty($from_month) && !empty($to_month), function ($query) use ($from_month, $to_month) {
                                return $query->whereRaw("DATE_FORMAT(pickup_date_time, '%Y-%m-%d') BETWEEN ? AND ?", [$from_month, $to_month]);
                            })
                            ->when(!empty($status), function ($query) use ($status) {
                                $query->where('booking_status', $status);
                            })
                            ->orderBy('updated_at','DESC')
                            ->paginate($per_page);
                          
            // Check if any results are found
            if ($bookings->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No bookings found'], Response::HTTP_NOT_FOUND);
            }
            
            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $product_id = (int)  $list->product_id;
                $order_number = $list->order_number;
                $transaction_id = $list->transaction_id;
                $first_name = $list->first_name;
                $last_name = $list->last_name;
                $email = $list->email;
		        $phone_number = $list->phone_number;
                $pickup_city = $list->pickup_city;
                $pickup_address = $list->pickup_address;
                $pickup_date_time = $list->pickup_date_time;
                $return_city = $list->return_city;
                $return_address = $list->return_address;
                $return_date_time = $list->return_date_time;
                $car_month  = str_replace("_", " ", $list->car_month);
                $car_monthly_price = $list->car_monthly_price;
                $deposit_type = $list->deposit_type;
                $deposit_selected_tab = $list->deposit_selected_tab;
                $deposit_price = $list->deposit_price;
                $total_days = $list->total_days;
                $summary_total_amount = $list->summary_total_amount;
                $summary_total_vat = $list->summary_total_vat;
                $total_discount_incl_vat = $list->total_discount_incl_vat;
                $total_price = $list->total_price;
                $payment_status = $list->payment_status;
                $booking_status = $list->booking_status;
                $promo_code = $list->promo_code;
                $promo_discount = $list->promo_discount;
                $pay_now_discount = $list->pay_now_discount;
                $booking_page_slug = $list->booking_page_slug;
                $payment_type = str_replace("_", " ", $list->payment_type);
                $accept_terms = $list->accept_terms == 1 ? "Yes" : "No";
                $valid_driving_license = $list->valid_driving_license == 1 ? "Yes" : "No";
                $driver_age_above = $list->driver_age_above == 1 ? "Yes" : "No";
                $card_payment = str_replace("_", " ", $list->card_payment);
                $partial_percentage = $list->partial_percentage;
                $partial_amount = $list->partial_amount;
                $extras = json_decode($list->extras, true);
                
                $translation = ProductTranslation::where('product_id', $product_id)
                                ->whereIn('language', [$lang, 'en'])
                                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                ->first();
                                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                $product_title = $translatedData['product_title'];
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                return [
                        'id' => $id,
                        'product_title' => $product_title,
                        'transaction_id' => $transaction_id,
                        'order_number' => $order_number,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone_number' => $phone_number,
                        'pickup_city' => $pickup_city,
                        'pickup_address' => $pickup_address,
                        'pickup_date_time' => $pickup_date_time,
                        'return_city' => $return_city,
                        'return_address' => $return_address,
                        'return_date_time' => $return_date_time,
                        'car_month' => $car_month,
                        'car_monthly_price' => $car_monthly_price,
                        'deposit_type' => $deposit_type,
                        'deposit_selected_tab' => $deposit_selected_tab,
                        'deposit_price' => $deposit_price,
                        'total_days' => $total_days,
                        'summary_total_amount' => $summary_total_amount,
                        'summary_total_vat' => $summary_total_vat,
                        'total_discount_incl_vat' => $total_discount_incl_vat,
                        'total_price' => $total_price,
                        'payment_type' => $payment_type,
                        'payment_status' => $payment_status,
                        'booking_status' => $booking_status,
                        'promo_code' => $promo_code,
                        'promo_discount' => $promo_discount,
                        'pay_now_discount' => $pay_now_discount,
                        'booking_page_slug' => $booking_page_slug,
                        'payment_type' => $payment_type,
                        'accept_terms' => $accept_terms,
                        'valid_driving_license' => $valid_driving_license,
                        'driver_age_above' => $driver_age_above,
                        'card_payment' => $card_payment,
                        'partial_percentage' => $partial_percentage,
                        'partial_amount' => $partial_amount,
                        'coverages_extras' => $extras,
                        'notification_detail' => $notification_details
                    ];
                
            });
    
            return response()->json([
                'status' => 'true',
                'message' => 'Booking record found.',
                'data' => $bookingList,
                'pagination' => [
                        'current_page' => $bookings->currentPage(),
                        'last_page' => $bookings->lastPage(),
                        'per_page' => $bookings->perPage(),
                        'total' => $bookings->total(),
                    ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function timeGreaterThanOrEqualTo($pickup_time, $dropoff_time){

        // Define range times
        $night_start_time = '20:30';
        $night_end_time = '08:30';
        $startTime = strtotime($night_start_time);
        $endTime   = strtotime($night_end_time);
        
        // Convert to timestamps (only time part)
        $pickupTime = strtotime($pickup_time);
        $dropoffTime = strtotime($dropoff_time);
        
        $isNightTime = 0;

        if (($pickupTime >= $startTime || $pickupTime <= $endTime) || ($dropoffTime >= $startTime || $dropoffTime <= $endTime)) {
            $isNightTime = 1;
        }
        
        return $isNightTime;
    }
    
    // Get all coverages list for booking page
    public function coveragesListForBooking(Request $request, $product_id, $lang)
    {
        try {
            
            // Define validation rules
            $rules = [
                    'from_date' => 'nullable|date|date_format:Y-m-d',
                    'to_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:from_date',
                    'pickup_time' => 'nullable|date_format:H:i',
                    'dropoff_time' => 'nullable|date_format:H:i',
                ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 422,
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{
                
                $from_date = $request->input('from_date');
                $to_date = $request->input('to_date');
                $pickup_time = $request->input('pickup_time');
                $dropoff_time = $request->input('dropoff_time');
                $promo_code = $request->input('promo_code');
                $promo_type = $request->input('promo_type');
                $selected_coverages_ids = $request->input('selected_coverages_ids');
                $coverages_locations = $request->input('coverages_locations');
                $countable_values_qty = $request->input('countable_values_qty');
                $button_status = $request->input('button_status');
                $button_type = $request->input('button_type');
                $card_payment = $request->input('card_payment');
                $deposit_type = $request->input('deposit_type');
                $deposit_selected_tab = $request->input('deposit_selected_tab');
                $deposit_price = $request->input('deposit_price');
                $page_type = $request->input('page_type');
                $promotion_id = $request->input('promotion_id');
                $car_monthly_price = $request->input('car_monthly_price');
                
                // Get status of the Out of hours
                $ooh_status = $this->timeGreaterThanOrEqualTo($pickup_time, $dropoff_time);
                
                $percentage = 5;
                if($page_type == 'promotion'){
                    
                    // Create a request instance with the required values
                    $customRequest = new Request([
                        'date_from' => $from_date,
                        'date_to' => $to_date,
                        'pickup_time' => $pickup_time,
                        'dropoff_time' => $dropoff_time,
                        'product_id' => $product_id
                    ]);
                    
                    // Promotion Controller
                    $promotionController = new PromotionController();
                    $productInnerDetails =  $promotionController->promotionsFormCalculator($customRequest, $promotion_id, $lang);
                }else{
                    
                     // Create a request instance with the required values
                    $customRequest = new Request([
                        'date_from' => $from_date,
                        'date_to' => $to_date,
                        'pickup_time' => $pickup_time,
                        'dropoff_time' => $dropoff_time,
                        'promo_code' => $promo_code,
                        'promo_type' => $promo_type,
                        'percentage' => $percentage,
                        'page_type' => $page_type,
                        'car_monthly_price' => $car_monthly_price,
                        'button_type' => $button_type
                    ]);
                    
                    // Product Controller
                    $productsController = new ProductsController();
                    $productInnerDetails =  $productsController->productsFormCalculator($customRequest, $product_id, $lang);
                }
                
                $status = true;
                $promo_code_value = "";
                $car_price = $daysCount = $car_sum_price = $car_vat_price = $total_discount = $total_discount_vat = 0;
                $productInnerResponse = $this->extractResponsePayload($productInnerDetails);
                if(!empty($productInnerResponse)){
                    $productInnerData = $productInnerResponse['data'] ?? [];
                    $promo_message = $productInnerResponse['message'] ?? '';
                    $status = $productInnerResponse['status'] ?? $status;
                    $daysCount = $productInnerData['days_count'] ?? 0;
                    $promo_code_value = $productInnerData['promo_code_value'] ?? "";
                    $car_sum_price = $productInnerData['sum_price'] ?? 0;
                    $car_vat_price = $productInnerData['vat'] ?? 0;
                    $total_discount = $productInnerData['total_discount'] ?? 0;
                    $total_discount_vat = $productInnerData['total_discount_vat'] ?? 0;
                    $car_price = $productInnerData['total'] ?? 0;
                }
                
                $product = Product::find($product_id);
                
                $security_deposit = $security_deposit_waiver_daily = $security_deposit_waiver_monthly = 0;
                if($product){
                    $security_deposit = $product->security_deposit ?? 0;
                    $security_deposit_waiver_daily = $product->security_deposit_waiver_daily ?? 0;
                    $security_deposit_waiver_monthly = $product->security_deposit_waiver_monthly ?? 0;
                }

                // Set hide IDs
                if ($daysCount <= 6) {    
                    $hide_coverages_ids = [24,25,26,27,28];
                } elseif ($daysCount >= 7 && $daysCount < 30) {
                    $hide_coverages_ids = [24,25,26,27,32];
                } elseif ($daysCount >= 30) {
                    $hide_coverages_ids = [28,32];
                } else {
                    $hide_coverages_ids = [];
                }
                
                $coveragesQuery = ProductCoverage::where('coverage_status', 1);

                if (!empty($hide_coverages_ids)) {
                    $coveragesQuery->whereNotIn('id', $hide_coverages_ids);
                }
                
                $coverages = $coveragesQuery
                            ->orderBy('created_at', 'ASC')
                            ->get();
                
                if($coverages->isEmpty()){
                    return response()->json(['status' => 'false', 'message' => 'Coverages not found'], 200);
                }
                
                $coverages_amount = $coverages_total_sumprice = $coverages_total_vat = 0;
                // Get all coverages list
                $coverages_translations = $coverages->map(function($coverage, $key) use (
                                            $product_id, $lang, $daysCount,
                                            &$coverages_amount, &$coverages_total_sumprice, &$coverages_total_vat, $selected_coverages_ids,
                                            $coverages_locations, $countable_values_qty, $button_status,$percentage,$ooh_status
                                            ){
                    
                    // Get coverage id                            
                    $coverage_id = $coverage->id;
                    $radio_group_ids = json_decode($coverage->radio_group_ids,true);
                    $field_required = (int) $coverage->field_required;
                    $checked_by_default = (int) $coverage->checked_by_default;
                    $coverage_status = (int) $coverage->coverage_status;
                    $countable_value = (int) $coverage->countable_value;
                    $per_day_price = (int) $coverage->per_day_price;
                    $address_is_required = (int) $coverage->address_is_required;
                    $vat_is_applicable = (int) $coverage->vat_is_applicable;
                    $recommended = (int) $coverage->recommended;
                    $type = $coverage->type;
                    $less_30_days_price = $coverage->less_30_days_price;
                    $more_30_days_price = $coverage->more_30_days_price;
                    $by_locations_prices = $coverage->prices_by_locations;
                    
                    $translatedData = [];
                    $coverage_title = "";
                    $translation = ProductCoverageTranslation::where('coverage_id', $coverage_id)
                                ->whereIn('language', [$lang, 'en'])
                                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                ->first(); 
                                
                    if($coverage_id == 21 && $ooh_status == 0){
                        return $translatedData;
                    }

                    if(!empty($translation)){
                        $translatedData = json_decode($translation->field_values, true);
                        $coverage_title = $translatedData['title'] ?? "";
                    }
                    
                    // Fetch related to the products coverage
                    $relatedCoveragesQuery = ProductRelatedCoverage::where('coverage_id', '=', $coverage_id)
                                                ->where('product_id', '=', $product_id)
                                                ->first();
                    
                    // Handle image URLs for primary fields
                    $translatedData['id'] = $coverage_id;
                    $translatedData['radio_group_ids'] = $radio_group_ids;
                    $translatedData['field_required'] = $field_required;
                    $translatedData['checked_by_default'] = $checked_by_default;
                    $translatedData['coverage_status'] = $coverage_status;
                    $translatedData['countable_value'] = $countable_value;
                    $translatedData['per_day_price'] = $per_day_price;
                    $translatedData['address_is_required'] = $address_is_required;
                    $translatedData['vat_is_applicable'] = $vat_is_applicable;
                    $translatedData['recommended'] = $recommended;
                    $translatedData['type'] = $type;
                    
                    if($relatedCoveragesQuery){
                        $p_less_30 = $relatedCoveragesQuery->less_30_days_price;
                        $p_more_30 = $relatedCoveragesQuery->more_30_days_price;
                        $less_30_days_price = ($p_less_30 !== null && $p_less_30 != 0) ? $p_less_30 : $less_30_days_price;
                        $more_30_days_price = ($p_more_30 !== null && $p_more_30 != 0) ? $p_more_30 : $more_30_days_price;
                    }
                    
                    if ($daysCount >= 30) {
                        $daysPrice = $more_30_days_price;
                    } else {
                        $daysPrice = $less_30_days_price;
                    }
                    
                    $price = $daysPrice;
                    $translatedData['price'] = $price;
                    
                    $prices_by_locations = $location_detail = [];
                    if(!empty($by_locations_prices) && $by_locations_prices != null){
                        $prices_by_locations = json_decode($by_locations_prices, true);

                        $updated_prices_by_locations = [];
                        if (is_array($prices_by_locations)) {
                            
                            foreach ($prices_by_locations as $location) {
                                
                                if ($daysCount >= 30) {
                                    $city_price = $location['more_30_days_price'];
                                } else {
                                    $city_price = $location['less_30_days_price'];
                                }
                                
                                $updated_prices_by_locations[] = [
                                    'location' => $location['location'],
                                    'location_price' => $city_price
                                ];
                            }
                        
                            $translatedData['prices_by_locations'] = $updated_prices_by_locations;
                        }
                        
                        if(isset($coverages_locations[$coverage_id]) && in_array($coverage_id, $selected_coverages_ids)){
                            $selected_location = $coverages_locations[$coverage_id]['selected_location'];
                            
                            // Convert to associative array: location name => location price
                            $location_prices = array_column($updated_prices_by_locations, 'location_price', 'location');
                            
                            // Get price directly without loop
                            $location_price = $location_prices[$selected_location] ?? null;
                            
                            $selected_location_addresss = $coverages_locations[$coverage_id]['selected_location_addresss'];
                            $location_vat = $location_vat_price = 0;
                            
                            // Get VAT of the city price
                            if($vat_is_applicable == 1){
                                $location_vat = ($percentage / 100) * $location_price;
                                $location_vat_price = $location_price + $location_vat;
                            }
                            
                            $delivery_free = false;
                            if ($daysCount >= 30 && $selected_location == 'Dubai' && $coverage_title == 'Delivery') {
                                $location_price = 0;
                                $location_vat   = 0;
                                $location_vat_price = 0;
                                $delivery_free = true; 
                            }
                            
                            $location_detail = [
                                'title' => $selected_location,
                                'delivery_free' => $delivery_free,
                                'custom_address' => $selected_location_addresss,
                                'price' => number_format($location_price, 2, '.', ''),
                                'sum_price' => number_format($location_price, 2, '.', ''),
                                'vat' => number_format($location_vat, 2, '.', ''),
                                'vat_price' => number_format($location_vat_price, 2, '.', ''),
                                'total' => number_format($location_vat_price, 2, '.', '')
                            ];
                            
                            
                            
                            $coverages_total_sumprice += $location_price;
                            $coverages_total_vat += $location_vat;
                            $coverages_amount += $location_vat_price;
                        }
                    }else{
                        $translatedData['prices_by_locations'] = null;
                    }
                    
                    $countable_qty = 0;
                    $sum_price = $vat = 0.00;
                    if($per_day_price == 1){
                        $daysPrice = $daysPrice * $daysCount;
                    }else if(isset($countable_values_qty[$coverage_id]) && $countable_value == 1){
                        $countable_qty = $countable_values_qty[$coverage_id];
                        $daysPrice =  $daysPrice * $countable_qty;
                    }
                    $sum_price = $daysPrice;
                    
                    if($vat_is_applicable == 1){
                        $vat = ($percentage / 100) * $daysPrice;
                        $daysPrice += $vat;
                    }
                    
                    $button_added = 0;
                    if((empty($selected_coverages_ids) || in_array($coverage_id, $selected_coverages_ids)) && $checked_by_default == 1){
                        $coverages_total_sumprice += $sum_price;
                        $coverages_total_vat += $vat;
                        $coverages_amount += $daysPrice;
                        $button_added = 1;
                    }else if((in_array($coverage_id, $selected_coverages_ids) && $button_status == 1)){
                        $coverages_total_sumprice += $sum_price;
                        $coverages_total_vat += $vat;
                        $coverages_amount += $daysPrice;
                        $button_added = 1;
                    }
                    
                    $translatedData['button_added'] = $button_added;
                    
                    $translatedData['coverages_extras'] = [
                        'title' => $coverage_title,
                        'price_is_per_day' => $per_day_price == 1 ? "Yes" : "No",
                        'countable_value' => $countable_value == 1 ? "Yes" : "No",
                        'countable_qty' => $countable_qty,
                        'price' => number_format($price, 2, '.', ''),
                        'sum_price' => number_format($sum_price, 2, '.', ''),
                        'vat' => number_format($vat, 2, '.', ''),
                        'vat_price' => number_format($daysPrice, 2, '.', ''),
                        'selected_locations' => $location_detail,
                        'total' => number_format($daysPrice, 2, '.', '')
                    ];
                    
                    return $translatedData;
                });
                $summary_total_amount = $car_sum_price + $coverages_total_sumprice;
                $summary_total_amount -= $total_discount;
                $summary_total_vat = $car_vat_price + $coverages_total_vat;
                $summary_total_vat -= $total_discount_vat;
                $total_amount = $summary_total_amount + $summary_total_vat;
                
                $deposit_price = $unit_deposit_price = 0;
                $unit_deposit_price = $deposit_vat = $waiver_days = "-";
                if($deposit_type == "deposit"){
                    $deposit_price = $security_deposit;
                    $deposit_vat_price = $deposit_price;
                    if($deposit_selected_tab != 'deposit_pay_later'){
                        $summary_total_amount += $deposit_price;
                        $total_amount += $deposit_price;
                    }
                }else if($deposit_type == "waiver"){
                    
                    if ($daysCount >= 30) {
                        $months = $daysCount / 30; // Rounds up to nearest month
                        $deposit_price = $months * $security_deposit_waiver_monthly;
                    } else {
                        $deposit_price = $daysCount *  $security_deposit_waiver_daily;
                    }
                    $summary_total_amount += $deposit_price;
                    $waiver_unit = ($deposit_price / $daysCount);
                    $unit_deposit_price = number_format($waiver_unit, 2, '.', '');
                    $waiver_days = $daysCount;
                    $deposit_vat = ($percentage / 100) * $deposit_price;
                    $summary_total_vat += $deposit_vat;
                    $deposit_vat = number_format($deposit_vat, 2, '.', '');
                    $deposit_vat_price = $deposit_price + $deposit_vat;
                    $total_amount += $deposit_vat_price;
                }
                
                $car_summary['rantal_charges_detail'] = [
                        'title' => 'Rental Charges',
                        'price' => $productInnerData['price'],
                        'sum_price' => $productInnerData['sum_price'],
                        'vat' => $productInnerData['vat'],
                        'vat_price' => $productInnerData['vat_price'],
                        'days_count' => $productInnerData['days_count'],
                        'total' => $productInnerData['vat_price']
                    ];
                 
                if(isset($productInnerData['promo_title_value']) && !empty($productInnerData['promo_title_value']) && $productInnerData['sum_price'] > 0){
                    $car_summary['promo_code_detail'] = [
                            'title' => $productInnerData['promo_title_value'],
                            'price' => $productInnerData['sum_price'],
                            'sum_price' => "-".$productInnerData['promo_discount_amount'],
                            'vat' => "-".$productInnerData['promo_vat_amount'],
                            'vat_price' => "-".$productInnerData['promo_total_amount'],
                            'days_count' => '-',
                            'total' => "-".$productInnerData['promo_total_amount']
                        ];
                }
                
                if(isset($productInnerData['pay_now_title']) && !empty($productInnerData['pay_now_title']) && $productInnerData['sum_price'] > 0){
                    $car_summary['pay_now_detail'] = [
                            'title' => $productInnerData['pay_now_title'],
                            'price' => $productInnerData['pay_now_unitprice'],
                            'sum_price' => "-".$productInnerData['pay_now_discount'],
                            'vat' => "-".$productInnerData['pay_now_vat_amount'],
                            'vat_price' => "-".$productInnerData['pay_now_total_amount'],
                            'days_count' => '-',
                            'total' => "-".$productInnerData['pay_now_total_amount']
                        ];
                }
                
                $car_summary['deposit_detail'] = [
                        'title' => $deposit_type,
                        'deposit_selected_tab' => $deposit_selected_tab,
                        'price' => $unit_deposit_price,
                        'sum_price' => number_format($deposit_price, 2, '.', ''),
                        'vat' => $deposit_vat,
                        'vat_price' => number_format($deposit_vat_price, 2, '.', ''),
                        'days_count' => $waiver_days,
                        'total' => number_format($deposit_vat_price, 2, '.', '')
                    ];

                $partial_amount = $partial_percentage = "";
                if($card_payment == 'partial_payment'){
                    $partial_percentage = 20;
                    $partial_amount = ($total_amount * $partial_percentage) / 100;
                    
                    $partial_amount = number_format($partial_amount, 2, '.', ''); 
                }    
                
                $order_amount_summary = [
                        'promo_code_value' => $promo_code_value,
                        'partial_percentage' => $partial_percentage,
                        'partial_amount' => $partial_amount,
                        'summary_total_amount' => number_format($summary_total_amount, 2, '.', ''),
                        'summary_total_vat' => number_format($summary_total_vat, 2, '.', ''),
                        'total_discount_incl_vat' => (isset($productInnerData['total_discount_incl_vat']) && $productInnerData['total_discount_incl_vat'] != 0) ? "-".$productInnerData['total_discount_incl_vat'] : "",
                        'grand_total' => number_format($total_amount, 2, '.', ''),
                    ];
            
                // Use filter() to remove any null values, then use values() to re-index the collection as an array
                $coverages_summary = $coverages_translations->filter()->values();
                
                return response()->json([
                    'status' => $status,
                    'message' => $promo_message,
                    'data' => [
                        'all_coverages' => $coverages_summary,
                        'order_summary' => $car_summary,
                        'order_amount_summary' => $order_amount_summary
                        ]
                ], Response::HTTP_OK);
            }
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Car Booking Store Process 
    public function bookingStore(Request $request, $lang)
    {   
        DB::beginTransaction(); // Start transaction

        try {
            
            // Define validation rules
            $rules = [
                'product_id' => 'required|numeric',
                'first_name'  => 'required|string',
                'last_name'  => 'required|string',
                'phone_number' => 'required|string',
                'email' => 'required|email',
                'pickup_date_time' => 'required|date_format:Y-m-d H:i',
                'return_date_time' => 'required|date_format:Y-m-d H:i',
                'coverages_extras' => 'required|array',
            ];
        
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
        
            // Check if validation fails
            if ($validator->fails()) {
                // Collect all error messages into a single string with line breaks
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages,
                    'data' => null
                ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422
            }
            
            $product_id = $request->input('product_id');
            $first_name = $request->input('first_name');
            $last_name  = $request->input('last_name');
            $phone_number = $request->input('phone_number');
            $email = $request->input('email');
            $pickup_city = $request->input('pickup_city');
            $pickup_address = $request->input('pickup_address');
            $pickup_date_time = $request->input('pickup_date_time');
            $return_city = $request->input('return_city');
            $return_address = $request->input('return_address');
            $return_date_time = $request->input('return_date_time');
            $total_days = $request->input('total_days');
            $promo_code = $request->input('promo_code');
            $summary_total_amount = $request->input('summary_total_amount');
            $summary_total_vat = $request->input('summary_total_vat');
            $total_discount_incl_vat = $request->input('total_discount_incl_vat');
            $total_price = $request->input('total_price');
            $coverages_extras = $request->input('coverages_extras');
            $booking_page_slug = $request->input('booking_page_slug');
            $payment_type = $request->input('payment_type');
            $payment_status = $request->input('payment_status');
            $accept_terms = $request->input('accept_terms');
            $valid_driving_license = $request->input('valid_driving_license');
            $driver_age_above = $request->input('driver_age_above');
            $card_payment = $request->input('card_payment');
            $car_month = $request->input('car_month');
            $car_monthly_price = $request->input('car_monthly_price');

            $gclid = $request->input('gclid');
            $source = $request->input('source');
            $keyword = $request->input('keyword');
            $device = $request->input('device');
            $matchtype = $request->input('matchtype');
            $grant_total = $total_price;
            
            $partial_percentage = $partial_amount = 0;
            if($card_payment == 'partial_payment'){
                $partial_percentage = 20;
                $partial_amount = ($total_price * $partial_percentage) / 100;
                
                $partial_amount = number_format($partial_amount, 2, '.', ''); 
            }
            
            $filtered_coverages_extras = [];
            if(is_array($coverages_extras) && $coverages_extras != null){
                $filtered_coverages_extras = array_filter($coverages_extras);
            }
            
            // Insert into booking table
            $bookingDetails = Booking::create([
                    'product_id' => $product_id,
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
                    'car_month' => $car_month,
                    'car_monthly_price' => $car_monthly_price,
                    'total_days' => $total_days,
                    'promo_code' => $promo_code,
                    'summary_total_amount' => $summary_total_amount,
                    'summary_total_vat' => $summary_total_vat,
                    'total_discount_incl_vat' => $total_discount_incl_vat,
                    'total_price' => $grant_total,
                    'extras' => json_encode($filtered_coverages_extras),
                    'booking_page_slug' => $booking_page_slug,
                    'payment_type' => $payment_type,
                    'payment_status' => $payment_status,
                    'booking_status' => "Pending",
                    'accept_terms' => $accept_terms,
                    'valid_driving_license' => $valid_driving_license,
                    'driver_age_above' => $driver_age_above,
                    'card_payment' => $card_payment,
                    'partial_percentage' => $partial_percentage,
                    'partial_amount' => $partial_amount,
                    'language' => $lang
                ]);
                
            $booking_id = $bookingDetails->id;    
            $orderNumber = str_pad($booking_id, 6, '0', STR_PAD_LEFT);
            $bookingDetails->order_number = $orderNumber;
            $bookingDetails->save();
            
            DB::commit(); // Commit transaction
            
            $translation = ProductTranslation::where('product_id', $product_id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
                            
            $product_title = $payment_url= $transaction_id = "";                
            if(!empty($translation)){
                $translatedData =  json_decode($translation->field_values, true);
                $product_title = $translatedData['product_title'] ?? "";
            }
                
            
            if($payment_type == 'pay_now'){
                
                if(!empty($partial_amount) && $partial_amount != 0){
                    $total_price = $partial_amount;
                }
                
                // Create a request instance with the required values
                $customRequest = new Request([
                    'amount' => $total_price,
                    'booking_id' => $booking_id,
                    'order_number' => $orderNumber
                ]);
                
                // Payment Controller
                $paymentController = new PaymentController();
                $paymentInnerDetails =  $paymentController->executeEtisalatPayment($customRequest, $lang);
                
                $paymentInnerResponse = $this->extractResponsePayload($paymentInnerDetails);
                if(!empty($paymentInnerResponse['data'])){
                    $paymentInnerData = $paymentInnerResponse['data'];
                    $transactionData = $paymentInnerData['Transaction'] ?? [];
                    $payment_url = !empty($transactionData['PaymentPortal']) ? $transactionData['PaymentPortal'] : "";
                    $transaction_id = !empty($transactionData['TransactionID']) ? $transactionData['TransactionID'] : "";
                }
            }
            
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
                    'car_month' => str_replace("_", " ", $car_month),
                    'car_monthly_price' => !empty($car_monthly_price) ? $car_monthly_price : "",
                    'total_days' => $total_days,
                    'summary_total_amount' => !empty($summary_total_amount) ? $summary_total_amount : "",
                    'summary_total_vat' => !empty($summary_total_vat) ? $summary_total_vat : "-",
                    'total_discount_incl_vat' => !empty($total_discount_incl_vat) ? $total_discount_incl_vat : "",
                    'total_price' => $grant_total,
                    'extras' => $filtered_coverages_extras,
                    'booking_page_slug' => $booking_page_slug,
                    'payment_type' => ucwords(str_replace("_", " ", $payment_type)),
                    'payment_status' => $payment_status,
                    'booking_status' => 'Pending',
                    'accept_terms' => $accept_terms == 1 ? "Yes" : "No",
                    'valid_driving_license' => $valid_driving_license == 1 ? "Yes" : "No",
                    'driver_age_above' => $driver_age_above == 1 ? "Yes" : "No",
                    'card_payment' => ucwords(str_replace("_", " ", $card_payment)),
                    'partial_percentage' => !empty($partial_percentage)? $partial_percentage : "",
                    'partial_amount' => !empty($partial_amount) ? $partial_amount : "",
                    'change_status' => false
                ];
            
            // Define messages for different languages
            $messages = [
                'en' => 'Car rental booked successfully!',
                'ar' => "تم حجز تأجير السيارة بنجاح!"
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];
            
            
            $template = 'emails.booking_templates.'.$lang.'_bookacar'; // Example for Arabic template
     
            $mailerConfig = config('mail.mailers.main');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            // Send email to client with form details
            Mail::mailer('main')
                    ->to($email)
                    ->send(new BookingMail($bookingDetail, $template, $lang, false, $fromAddress, $fromName));    
                
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('main')
                ->to($adminAddress)
                ->send(new BookingMail($bookingDetail, $template, $lang, true, $fromAddress, $fromName));
            
            return response()->json([
                        'status' => 'true',
                        'message' => $message,
                        'data' => [
                            'booking_id' => $booking_id,
                            'order_number' => $orderNumber,
                            'payment_url' => $payment_url,
                            'transaction_id' => $transaction_id
                        ]
                    ], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage(), 'data' => null], 500);
        }
    }
    
    public function bookingStatus(Request $request, $id, $lang)
    {
        DB::beginTransaction(); // Start transaction
        
        try {
            
            // Define validation rules
            $rules = [
                'booking_status' => 'nullable|string',
                'payment_status' => 'nullable|string',
                'description' => 'nullable|string'
            ];
    
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
    
            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'false',
                    'message' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $user = Auth::user();
            $userId = $user->id;
            $changes = [];
            
            // Retrieve booking
            $booking = Booking::where('id', $id)
                              ->where('language', $lang)
                              ->first();
            if (!$booking) {
                return response()->json(['status' => 'false', 'message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
            }
            $old_booking_status = $booking->booking_status;
            $old_payment_status = $booking->payment_status;
            $order_number = $booking->order_number;
            $first_name = $booking->first_name;
            $last_name = $booking->last_name;
            $booking_status = $request->input('booking_status');
            $payment_status = $request->input('payment_status');
            $description = $request->input('description');
            
            $changes['id'] = $id;
            $changes['order_number'] = $order_number;
            $changes['name'] = $first_name.' '.$last_name;
            
            // Update booking details
            if ($request->has('booking_status')) {
                $changes['booking_status'] = ["old"=> $old_booking_status, "new"=> $booking_status];
                $booking->booking_status = $booking_status;
            }
            
            if ($request->has('payment_status')) {
                $changes['payment_status'] = ["old"=> $old_payment_status, "new"=> $payment_status];
                $booking->payment_status = $payment_status;
            }
            $booking->updated_by = $userId;
            $booking->save();
            
            $changes['comment'] = $description;
            
            UserActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'model_type' => Booking::class,
                'table_name'=> 'bookings',
                'action' => 'updated',
                'changes' => $changes,
                'language' => $lang ?? 'en',
            ]);
            
            // Insert into BookingNotification table
            BookingNotification::create([
                    'booking_id' => $id,
                    'description' => $description,
                    'booking_status' => $booking_status
                ]);
                
            DB::commit(); // Commit transaction
            
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
            $total_price = $booking->total_price;
            $coverages_extras = json_decode($booking->extras, true);
            $booking_page_slug = $booking->booking_page_slug;
            $payment_type = $booking->payment_type;
            $payment_status = $booking->payment_status;
            $accept_terms = $booking->accept_terms;
            $valid_driving_license = $booking->valid_driving_license;
            $driver_age_above = $booking->driver_age_above;
            $card_payment = $booking->card_payment;
            $partial_percentage = $booking->partial_percentage;
            $partial_amount = $booking->partial_amount;
            
            $filtered_coverages_extras = [];
            if(is_array($coverages_extras) && $coverages_extras != null){
                $filtered_coverages_extras = array_filter($coverages_extras);
            }
            
            $translation = ProductTranslation::where('product_id', $product_id)
                                ->whereIn('language', [$lang, 'en'])
                                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                ->first(); 
                                
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
            $product_title = $translatedData['product_title'];
            
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
                    'promo_code' => !empty($promo_code) ? $promo_code : " - ",
                    'total_price' => $total_price,
                    'extras' => $filtered_coverages_extras,
                    'booking_page_slug' => $booking_page_slug,
                    'payment_type' => str_replace("_", " ", $payment_type),
                    'payment_status' => $payment_status,
                    'booking_status' => $booking_status,
                    'accept_terms' => $accept_terms == 1 ? "Yes" : "No",
                    'valid_driving_license' => $valid_driving_license == 1 ? "Yes" : "No",
                    'driver_age_above' => $driver_age_above == 1 ? "Yes" : "No",
                    'card_payment' => str_replace("_", " ", $card_payment),
                    'partial_percentage' => !empty($partial_percentage)? $partial_percentage : "-",
                    'partial_amount' => !empty($partial_amount) ? $partial_amount : "-",
                    'description' => $description,
                    'change_status' => true
                ];
            
            $template = 'emails.booking_templates.'.$lang.'_bookacar'; // Example for Arabic template
            
            $mailerConfig = config('mail.mailers.main');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            // Send email to client with form details
            Mail::mailer('main')
                    ->to($email)
                    ->send(new BookingMail($bookingDetail, $template, $lang, false, $fromAddress, $fromName));    
                
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('main')
                ->to($adminAddress)
                ->send(new BookingMail($bookingDetail, $template, $lang, true, $fromAddress, $fromName));
            
            
            return response()->json(['status' => 'true', 'message' => 'Booking status updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    
    // Product Search Engine Process
    public function productSearchEngine($lang, $per_page = 6)
    {
        $pickup_date_time = trim(request()->query('pickup_date_time'));
        $return_date_time = trim(request()->query('return_date_time'));
        
        $daysCount = $total = 0;
        if (!empty($pickup_date_time) && !empty($return_date_time)) {

            $fromDateTime = Carbon::createFromFormat('Y-m-d H:i', $pickup_date_time);
            $toDateTime = Carbon::createFromFormat('Y-m-d H:i', $return_date_time);
                
            // Date difference only
            $daysCount = $fromDateTime->copy()->startOfDay()->diffInDays(
                $toDateTime->copy()->startOfDay()
            );

            // Pickup time + 1 hour grace
            $graceTime = $fromDateTime->copy()
                ->addDays($daysCount)
                ->addHour();

            // If the grace period is exceeded, add one more day.
            if ($toDateTime->gt($graceTime)) {
                $daysCount++;
            }
        }
        
        $price_type = request()->query('price_type');
        $car_types  = request()->query('car_types');
        $featured   = request()->query('featured');
        $year       = request()->query('year');
        $min        = request()->query('min');
        $max        = request()->query('max');
        $specs      = request()->query('specs');
        $brands     = request()->query('brands');
        $slug       = request()->query('slug');
        
        $productQuery = Product::where('product_status','=',1);
        
        // --- Catalog Filter ---
        if (!empty($slug)) {
        
            $catalog = Catalog::where('slug', $slug)->first();
        
            if ($catalog && !empty($catalog->car_ids)) {
                $carIds = json_decode($catalog->car_ids, true);
                $productQuery->whereIn('id', $carIds);
            }
        }
    
        // Filtering options    
        if ($brands !== null && is_array($brands)) {
            $brandsIds = $brands;
            
            $productQuery->where(function ($q) use ($brandsIds) {
                foreach ($brandsIds as $brandId) {
                    $brandsId = (string) $brandId;
                    $q->orWhereJsonContains('products.additional_catalog_ids', (string) $brandsId)
                        ->orWhereRaw("JSON_SEARCH(products.additional_catalog_ids, 'one', ? ) IS NOT NULL", [$brandsId]);
                }
            });
        }
        
        if ($car_types !== null && is_array($car_types)) {
            $productQuery->whereIn('vehicle_type', $car_types);
        }
        
        if ($featured !== null && $featured !== 0) {
            $productQuery->where('featured', $featured);
        }

        if (request()->filled('year') && $year !== null ) {
            $productQuery->where('year', $year);
        }
        
        if ($specs !== null && is_array($specs)) {
            $productQuery->whereIn('specification_auto', $specs);
        }
        
        if ($daysCount <= 6) {
            // Daily price
            $priceColumn = 'daily_price';
        } elseif ($daysCount >= 7 && $daysCount < 30) {
            // Weekly price
            $priceColumn = 'weekly_price';
        } elseif ($daysCount >= 30) {
            // Monthly price
            $priceColumn = 'monthly_price';
        }

        // Sorting by price
        if ($price_type != null && $price_type !== null) {
            $priceOrder = $price_type;
            if ($priceOrder === 'high-to-low') {
                $productQuery->orderBy('products.'.$priceColumn, 'desc');
            } elseif ($priceOrder === 'low-to-high') {
                $productQuery->orderBy('products.'.$priceColumn, 'asc');
            }
        }
        
        if ($min !== null && $max !== null) {
            $productQuery->where(function ($q) use ($priceColumn, $min, $max) {
                $q->whereRaw("CAST(COALESCE($priceColumn, 0) AS UNSIGNED) BETWEEN ? AND ?", [$min, $max]);
            });
        }else{
            $productQuery->whereNotNull($priceColumn)
             ->where($priceColumn, '>', 0);
        }
        
        $productQuery->where('stock_status','=',1)->orderBy('created_at', 'DESC');                 
        
        $perPage = request()->input('per_page', $per_page);
        
        // Check if full blog list is requested (per_page = 0)
        if ($perPage == 0) {
            // Retrieve all blogs excluding the recent one
            $products = $productQuery->get();
            
            // No pagination meta for full blog list
            $pagination = [
                'current_page' => 0,
                'last_page' => 1,
                'per_page' => $products->count(), // All items in one "page"
                'total' => $products->count(),
            ];
        } else {
            // Paginate the remaining
            $products = $productQuery->paginate($perPage);
            
            // Add pagination meta for paginated list
            $pagination = [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ];
        }
        
        $products_list = $products->map(function($product) use ($lang, $daysCount) {
            
            // Handle image URLs for primary fields
            $id = $product->id;
            $daily_price = round($product->daily_price);
            $weekly_price = round($product->weekly_price);
            $monthly_price = round($product->monthly_price);
            $created_by = $product->created_by;
            $updated_by = $product->updated_by;
            $created_at = $product->created_at;
            $updated_at = $product->updated_at;
            $pay_now_percentage = $product->pay_now_discount;
            
            $price = 0;
            $percentage = 5;
            if ($daysCount <= 6) {
                // Daily price
                $price = $daysCount * $daily_price;
            } elseif ($daysCount >= 7 && $daysCount < 30) {
                // Weekly price
                $weeks = $daysCount / 7;
                $price = $weeks * $weekly_price;
            } else {
                // Monthly price
                $months = $daysCount / 30; // Rounds up to nearest month
                $price = $months * $monthly_price;
            }
            
            $vat = ($percentage / 100) * $price;
            $total_price = $price + $vat;
            $total_price = round($total_price); 
            
            $pay_now_discount = $pay_now_amount = 0;
            if(!empty($pay_now_percentage) && $pay_now_percentage != 0){
                $pay_now_discount = ($price * $pay_now_percentage) / 100;
                $pay_now_discount = round($pay_now_discount);
                $pay_now_amount = $price - $pay_now_discount;
                
                $pay_now_vat = ($percentage / 100) * $pay_now_amount;
                $pay_now_amount += $pay_now_vat;
                $pay_now_amount = round($pay_now_amount); 
            }else{
                $pay_now_amount = $total_price;
            }
            
            $translation = ProductTranslation::where('product_id', $id)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();                
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
            $product_title = $translatedData['product_title'];
            
            $productListData = [];
            
            $catalog_slug = $catalog_title = $parent_slug = $page_full_slug = "";
            if(!empty($product->catalog_id) && $product->catalog_id != null){
                $catalog_id = $product->catalog_id;
                $catalog = Catalog::find($catalog_id);

                if($catalog){
                    $catalogQuery = CatalogTranslation::where('catalog_id', $catalog_id)
                        ->whereIn('language', [$lang, 'en']) // Check both requested and default language
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang]) // Prioritize requested language first
                        ->first();
                    
                    $catalogTranslation = !empty($catalogQuery) ? json_decode($catalogQuery->field_values, true) : [];
                    
                    if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                        $parent_id = $catalog->parent_id;
                        $parent_catalog = Catalog::find($parent_id);
        
                        if($parent_catalog){
                            $parent_slug = $parent_catalog->slug;
                            $page_full_slug .= $parent_slug.'/';
                        }
                    }
        
        
                    $catalog_title = $catalogTranslation['catalog_title'];
                    $catalog_slug = $catalog->slug;
                    $page_full_slug .= $catalog_slug.'/';
                }
            }
            
            $product_slug = $product->slug;
            $page_full_slug .= $product_slug.'/';
            
            $created_by_name = $this->getUserName($created_by);
            $updated_by_name = $this->getUserName($updated_by);
            
            $specification_auto = $product->specification_auto;
            if($specification_auto == 1){
                $productListData['specification'] = 'Full Option';
            } elseif($specification_auto == 2) {
                $productListData['specification'] = 'Medium Option';
            } elseif($specification_auto == 3) {
                $productListData['specification'] = 'Basic Option';
            }

            $PeopleVisitdCount = PeopleVisit::getVisitCount($product_slug);
            // Handle image URLs for primary fields
            $productListData['id'] = $id;
            $productListData['created_by'] = $created_by_name;
            $productListData['updated_by'] = $updated_by_name;
            $productListData['created_at'] = $created_at;
            $productListData['updated_at'] = $updated_at;
            $productListData['product_title'] = $product_title;
            $productListData['parent_slug'] = $parent_slug;
            $productListData['catalog_title'] = $catalog_title;
            $productListData['catalog_slug'] = $catalog_slug;
            $productListData['vehicle_type'] = $product->vehicle_type;
            $productListData['product_status'] = (int) $product->product_status;
            $productListData['featured'] = (int) $product->featured;
            $productListData['promo_status'] = (int) $product->promo_status;
            $productListData['stock_status'] = (int) $product->stock_status;
            $productListData['show_documents'] = (int) $product->show_documents;
            $productListData['book_now_button'] = (int) $product->book_now_button;
            $productListData['specification_auto'] = (int) $product->specification_auto;
            $productListData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;
            $productListData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
            $productListData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
            $productListData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
            $productListData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
            $productListData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
            $productListData['monthly_installment_24_months'] = $product->monthly_installment_24_months;
            $productListData['monthly_installment_36_months'] = $product->monthly_installment_36_months;
            $productListData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
            $productListData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
            $productListData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
            $productListData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
            $productListData['year'] = $product->year;
            $productListData['model'] = $product->model;
            $productListData['car_locations'] = json_decode($product->car_locations, true);
            $productListData['page_full_slug'] = $page_full_slug;
            $productListData['slug'] = $product_slug;
            $productListData['total_days'] = $daysCount;
            $productListData['pay_now_discount'] = $pay_now_percentage;
            $productListData['pay_now_amount'] = $pay_now_amount;
            $productListData['total_price'] = $total_price;
            $productListData['people_visited'] = $PeopleVisitdCount;
            $productListData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
            
            $car_images = json_decode($product->car_images) ?? null;
            // Process car images
            if (!empty($car_images)) {
                foreach ($car_images as $image_path) {
                    $productListData['car_images'][] = $image_path ? $this->getImageUrl($image_path) : null;
                }
            }else{
                $productListData['car_images'] = [];
            }
            
            // Fetch product inner page content
            $webContentController = new WebContentController();
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
            
            $productInnerResponse = $this->extractResponsePayload($productInnerDetails);
            if(!empty($productInnerResponse['data'])){
                $productInnerData = $productInnerResponse['data'];
                $productListData['services'] = $productInnerData['services'] ?? "";
                $productListData['telephone_number'] = $productInnerData['telephone_number'] ?? "";
                $productListData['whatsapp'] = $productInnerData['whatsapp'] ?? "";
            }else{
                $productListData['services'] = "";
                $productListData['telephone_number'] = "";
                $productListData['whatsapp'] = "";
            }
            
            return $productListData;
        });
            
        // Fetch content
        $webContentController = new WebContentController();
        $VehicleMeta =  $webContentController->getWebMetaDeta('vehicle-listing',$lang);
        
        $vehicleMetaResponse = $this->extractResponsePayload($VehicleMeta);
        if(!empty($vehicleMetaResponse['data'])){
            $metaData = $vehicleMetaResponse['data'];
        }else{
            $metaData = [];
        }    
           
        $webContentController = new WebContentController();

        // Fetch product inner page content
        $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
        
        $locations = [];
        $productInnerResponse = $this->extractResponsePayload($productInnerDetails);
        if(!empty($productInnerResponse['data'])){
            $productInnerData = $productInnerResponse['data'];
            $locations = $productInnerData['locations'];
        }else{
            $locations = [];
        }
        
        // Fetch Function
        $CatalogController = new CatalogController();

        $car_brands = $CatalogController->catalogsMenuList($lang, 'car_brands', "", 0, null);
        $brandsList = [];
        $brandsResponse = $this->extractResponsePayload($car_brands);
        if(!empty($brandsResponse['data'])){
            $brandsList = $brandsResponse['data'];
        }
                            
        return response()->json([
            'status' => 'true',
            'data' => [
                    'all_cars' => $products_list,
                    'locations' => $locations,
                    'meta' => $metaData,
                    'brands_list' => $brandsList
                ],
            'pagination' => $pagination
        ],200);
    }

    public function customerBookingList(Request $request, $lang, $per_page=12)
    {
        try {
            
            // Define validation rules
            $rules = [
                'from_month'      => 'nullable|string',
                'to_month'        => 'nullable|string',
                'booking_status'  => 'nullable|string',
                'booking_id'      => 'nullable|integer',
            ];
            
            // Validate request
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages,
                    'data' => null
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // Get the authenticated customer
            $customer = $request->user('customer'); 
            
            $status      = $request->input('booking_status');
            $from_month  = $request->input('from_month');
            $to_month    = $request->input('to_month');
            $email       = $customer->email;
            $phone_number    = $customer->phone_number;
            $booking_id   = $request->input('booking_id');

            // -------------------------------
            // Main Query with conditional booking_id
            // -------------------------------
            $bookingQuery = Booking::query()
                ->when(!empty($booking_id) && !empty($email), function ($query) use ($booking_id,$email) {
                    return $query->where('id', $booking_id)->where('email', $email);
                })
                ->when(empty($booking_id) && !empty($email), function ($query) use ($email) {
                    return $query->where('email', $email);
                })
                ->when(empty($booking_id) && !empty($phone_number), function ($query) use ($phone_number) {
                    return $query->where('phone_number', $phone_number);
                })
                ->when(empty($booking_id) && !empty($from_month) && !empty($to_month), function ($query) use ($from_month, $to_month) {
                    return $query->whereRaw("DATE_FORMAT(pickup_date_time, '%Y-%m-%d') BETWEEN ? AND ?", [$from_month, $to_month]);
                })
                ->when(empty($booking_id) && !empty($status), function ($query) use ($status) {
                    return $query->where('booking_status', $status);
                })
                ->orderBy('updated_at', 'DESC');
    
            // Fetch single booking if booking_id, otherwise paginate
            if (!empty($booking_id)) {
                $bookings = $bookingQuery->get(); // collection for single booking
            } else {
                $bookings = $bookingQuery->paginate($per_page);
            }
    
            if ($bookings->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Booking not found','data' => null], 400);
            }
            
            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $product_id = (int)  $list->product_id;
                $order_number = $list->order_number;
                $transaction_id = $list->transaction_id;
                $first_name = $list->first_name;
                $last_name = $list->last_name;
                $email = $list->email;
		        $phone_number = $list->phone_number;
                $pickup_city = $list->pickup_city;
                $pickup_address = $list->pickup_address;
                $pickup_date_time = $list->pickup_date_time;
                $return_city = $list->return_city;
                $return_address = $list->return_address;
                $return_date_time = $list->return_date_time;
                $car_month  = $list->car_month;
                $car_monthly_price = $list->car_monthly_price;
                $deposit_type = $list->deposit_type;
                $deposit_selected_tab = $list->deposit_selected_tab;
                $deposit_price = $list->deposit_price;
                $total_days = $list->total_days;
                $summary_total_amount = $list->summary_total_amount;
                $summary_total_vat = $list->summary_total_vat;
                $total_discount_incl_vat = $list->total_discount_incl_vat;
                $total_price = $list->total_price;
                $payment_type = $list->payment_type;
                $payment_status = $list->payment_status;
                $booking_status = $list->booking_status;
                $promo_code = $list->promo_code;
                $promo_discount = $list->promo_discount;
                $pay_now_discount = $list->pay_now_discount;
                $booking_page_slug = $list->booking_page_slug;
                $payment_type = str_replace("_", " ", $list->payment_type);
                $accept_terms = $list->accept_terms == 1 ? "Yes" : "No";
                $valid_driving_license = $list->valid_driving_license == 1 ? "Yes" : "No";
                $driver_age_above = $list->driver_age_above == 1 ? "Yes" : "No";
                $card_payment = str_replace("_", " ", $list->card_payment);
                $partial_percentage = $list->partial_percentage;
                $partial_amount = $list->partial_amount;
                $extras = json_decode($list->extras, true);
                
                $translation = ProductTranslation::where('product_id', $product_id)
                                ->whereIn('language', [$lang, 'en'])
                                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                ->first();
                                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                $product_title = $translatedData['product_title'] ?? "";
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                return [
                        'id' => $id,
                        'product_title' => $product_title,
                        'order_number' => $order_number,
                        'transaction_id' => $transaction_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone_number' => $phone_number,
                        'pickup_city' => $pickup_city,
                        'pickup_address' => $pickup_address,
                        'pickup_date_time' => $pickup_date_time,
                        'return_city' => $return_city,
                        'return_address' => $return_address,
                        'return_date_time' => $return_date_time,
                        'car_month' => str_replace("_"," ",$car_month),
                        'car_monthly_price' => $car_monthly_price,
                        'deposit_type' => $deposit_type,
                        'deposit_selected_tab' => $deposit_selected_tab,
                        'deposit_price' => $deposit_price,
                        'total_days' => $total_days,
                        'summary_total_amount' => $summary_total_amount,
                        'summary_total_vat' => $summary_total_vat,
                        'total_discount_incl_vat' => $total_discount_incl_vat,
                        'grand_total' => $total_price,
                        'payment_type' => str_replace("_"," ",$payment_type),
                        'payment_status' => $payment_status,
                        'booking_status' => $booking_status,
                        'promo_code' => $promo_code,
                        'promo_discount' => $promo_discount,
                        'pay_now_discount' => $pay_now_discount,
                        'booking_page_slug' => $booking_page_slug,
                        'accept_terms' => $accept_terms,
                        'valid_driving_license' => $valid_driving_license,
                        'driver_age_above' => $driver_age_above,
                        'card_payment' => str_replace("_"," ",$card_payment),
                        'partial_percentage' => $partial_percentage,
                        'partial_amount' => $partial_amount,
                        'coverages_extras' => $extras,
                        'notification_detail' => $notification_details
                    ];
                
            });
            
            $customerBookingList = [];

            // Add pagination if not single booking
            if (empty($booking_id)) {
                $customerBookingList['list'] = $bookingList;
                $customerBookingList['pagination'] = [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                ];
            }else{
                $customerBookingList['list'] = $bookingList;
                $customerBookingList['pagination'] = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 1,
                    'total' => 1,
                ];
            }

            // -------------------------------
            // Return response
            // -------------------------------
            $response = [
                'status' => true,
                'message' => 'Booking record found.',
                'data' => $customerBookingList
            ];
    
            return response()->json($response, Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage(),'data' => null], 500);
        }
    }

    public function getMobileSearch($per_page = 6)
    {
        try {
            
            $searchValue = trim((string) request()->query('search_value', ''));
            $lang = 'en';
                
            $productQuery = Product::query()
                ->join('product_translations as pt', function ($join) use ($lang) {
                    $join->on('products.id', '=', 'pt.product_id')
                         ->where('pt.language', '=', $lang);
                })
                ->select('products.*', 'pt.field_values');
            
            if ($searchValue !== '') {
                $searchValue = strtolower($searchValue);
                
                // $productQuery->where(function ($query) use ($searchValue) {
                //     $query->where('products.slug', 'LIKE', "%{$searchValue}%")
                //           ->orWhere('products.year', 'LIKE', "%{$searchValue}%")
                //           ->orWhereRaw("LOWER(products.model) LIKE ?", ["%{$searchValue}%"])
                //           ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(pt.field_values, '$.product_title'))) LIKE ?", ["%{$searchValue}%"])
                //           ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(pt.field_values, '$.heading_one'))) LIKE ?", ["%{$searchValue}%"]);
                // });
                
                $productQuery->where(function ($query) use ($searchValue) {
                    $query->whereRaw('LOWER(products.slug) LIKE ?', ["%{$searchValue}%"])
                          ->orWhereRaw('CAST(products.year AS CHAR) LIKE ?', ["%{$searchValue}%"])
                          ->orWhereRaw('LOWER(products.model) LIKE ?', ["%{$searchValue}%"])
                          ->orWhereRaw(
                              "LOWER(JSON_UNQUOTE(JSON_EXTRACT(pt.field_values, '$.product_title'))) LIKE ?",
                              ["%{$searchValue}%"]
                          )
                          ->orWhereRaw(
                              "LOWER(JSON_UNQUOTE(JSON_EXTRACT(pt.field_values, '$.heading_one'))) LIKE ?",
                              ["%{$searchValue}%"]
                          );
                });
            }

            $product_filter = request()->query('product_filter');
            
            if(!empty($product_filter) && $product_filter == 1){
                
                $priceType = request()->query('price_type');
                $carTypes = request()->query('car_types', []);
                $featured = request()->query('featured');
                $year = request()->query('year');
                $availability = request()->query('availability');
                $priceCategory = request()->query('price_category');
                $min = (int) request()->query('min', 0);
                $max = (int) request()->query('max', 9999999);
                $specs = request()->query('specs', []);
                $brandsIds = request()->query('brands', []);
                
                // Only allow expected price categories
                $allowedPriceCategories = ['daily', 'weekly', 'monthly'];
                
                $productIdsFromBrands = [];
                
                // Normalize possible comma-separated input
                if (!is_array($carTypes)) {
                    $carTypes = array_filter(explode(',', (string) $carTypes));
                }
            
                if (!is_array($specs)) {
                    $specs = array_filter(explode(',', (string) $specs));
                }
            
                if (!is_array($brandsIds)) {
                    $brandsIds = array_filter(explode(',', (string) $brandsIds));
                }

                // Filtering options    
                if (!empty($brandsIds)) {
                    $catalogs = DB::table('catalogs')
                        ->select('car_ids')
                        ->whereIn('id', $brandsIds)
                        ->get();
            
                    foreach ($catalogs as $catalog) {
                        $decodedIds = json_decode($catalog->car_ids, true);
            
                        if (is_array($decodedIds) && !empty($decodedIds)) {
                            $productIdsFromBrands = array_merge($productIdsFromBrands, $decodedIds);
                        }
                    }
            
                    $productIdsFromBrands = array_values(array_unique(array_filter($productIdsFromBrands)));
            
                    // Important: if brands were selected but no products found, return no rows
                    if (empty($productIdsFromBrands)) {
                        $productQuery->whereRaw('1 = 0');
                    } else {
                        $productQuery->whereIn('products.id', $productIdsFromBrands);
                    }
                }
            
                if (!empty($carTypes)) {
                    $productQuery->whereIn('products.vehicle_type', $carTypes);
                }
            
                if (!empty($specs)) {
                    $productQuery->whereIn('products.specification_auto', $specs);
                }
                
                // Only apply featured filter when explicitly sent and not empty
                if ($featured !== null && $featured !== '') {
                    $productQuery->where('products.featured', (int) $featured);
                }

                if ($year !== null && $year !== '') {
                    $productQuery->where('products.year', (int) $year);
                }
            
                if ($availability !== null && $availability !== '') {
                    $productQuery->where('products.stock_status', $availability);
                }
                
                if (
                    $priceCategory !== null &&
                    $priceCategory !== '' &&
                    in_array($priceCategory, $allowedPriceCategories, true)
                ) {
                    $priceColumn = "products.{$priceCategory}_price";
            
                    $productQuery->whereRaw(
                        "CAST(COALESCE({$priceColumn}, 0) AS UNSIGNED) BETWEEN ? AND ?",
                        [$min, $max]
                    );
            
                    if ($priceType != null && $priceType === 'high-to-low') {
                        $productQuery->orderByRaw("CAST(COALESCE({$priceColumn}, 0) AS UNSIGNED) DESC");
                    } elseif ($priceType != null && $priceType === 'low-to-high') {
                        $productQuery->orderByRaw("CAST(COALESCE({$priceColumn}, 0) AS UNSIGNED) ASC");
                    }
                }
                
            }
            
            $productQuery->where('products.product_status', 1)
                         ->orderBy('products.created_at', 'DESC');
                        
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $products = $productQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $products->count(), // All items in one "page"
                    'total' => $products->count(),
                ];
            } else {
                // Paginate the remaining
                $products = $productQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ];
            }
            
            $products_list = $products->map(function($product){
                
                // Handle image URLs for primary fields
                $id = $product->id;
                $monthly_price = $product->monthly_price;
                
                $vat = (5 / 100) * $monthly_price;
                $total_price = $monthly_price + $vat;
                $total_price = round($total_price); 
                
                $translation = ProductTranslation::where('product_id', $id)
                            ->where('language', 'en')
                            ->first();
                            
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                $product_title = $translatedData['product_title'];
                
                $productListData = [];
                
                $catalog_slug = $catalog_title = $parent_slug = "";
                if(!empty($product->catalog_id) && $product->catalog_id != null){
                    $catalog_id = $product->catalog_id;
                    $catalog = Catalog::find($catalog_id);
    
                    if($catalog){
                        $catalogQuery = CatalogTranslation::where('catalog_id', $catalog_id)
                            ->where('language', 'en')
                            ->first();
                            
                        $catalogTranslation = !empty($catalogQuery) ? json_decode($catalogQuery->field_values, true) : [];
                        
                        if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                            $parent_id = $catalog->parent_id;
                            $parent_catalog = Catalog::find($parent_id);
            
                            if($parent_catalog){
                                $parent_slug = $parent_catalog->slug;
                            }
                        }
            
            
                        $catalog_title = $catalogTranslation['catalog_title'];
                        $catalog_slug = $catalog->slug;
                    }
                }
                
                $product_slug = $product->slug;
                
                // Handle image URLs for primary fields
                $productListData['id'] = $id;
                $productListData['product_title'] = $product_title;
                $productListData['parent_slug'] = $parent_slug;
                $productListData['catalog_title'] = $catalog_title;
                $productListData['catalog_slug'] = $catalog_slug;
                $productListData['vehicle_type'] = $product->vehicle_type;
                $productListData['product_status'] = (int) $product->product_status;
                $productListData['featured'] = (int) $product->featured;
                $productListData['promo_status'] = (int) $product->promo_status;
                $productListData['stock_status'] = (int) $product->stock_status;
                $productListData['show_documents'] = (int) $product->show_documents;
                $productListData['book_now_button'] = (int) $product->book_now_button;
                $productListData['daily_price'] = $product->daily_price;
                $productListData['old_daily_price'] = $product->old_daily_price;
                $productListData['weekly_price'] = $product->weekly_price;
                $productListData['old_weekly_price'] = $product->old_weekly_price;
                $productListData['monthly_price'] = $product->monthly_price;
                $productListData['old_monthly_price'] = $product->old_monthly_price;
                $productListData['monthly_installment_24_months'] = $product->monthly_installment_24_months;
                $productListData['monthly_installment_36_months'] = $product->monthly_installment_36_months;
                $productListData['installment_per_month'] = $product->installment_per_month;
                $productListData['down_payment'] = $product->down_payment;
                $productListData['year'] = $product->year;
                $productListData['model'] = $product->model;
                $productListData['car_locations'] = json_decode($product->car_locations, true);
                $productListData['slug'] = $product_slug;
                $productListData['total_price'] = $total_price;
                $productListData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
                
                $car_images = json_decode($product->car_images) ?? null;
                // Process car images
                if (!empty($car_images)) {
                    foreach ($car_images as $image_path) {
                        $productListData['car_images'][] = $image_path ? $this->getImageUrl($image_path) : null;
                    }
                }else{
                    $productListData['car_images'] = [];
                }
                
                return $productListData;
            });
                
                                
            return response()->json([
                'status' => true,
                'message' => "Cars fetched successfully.",
                'data' => $products_list,
                'pagination' => $pagination
            ],200);
            
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], 200);
        }
    }
    
    public function getUserName($userId)
    {
        $userName = \App\Models\User::where('id', $userId)->pluck('name')->first();
        
        return $userName;
    }

    /**
     * Safely normalize a controller response or response-like object into an array.
     */
    private function extractResponsePayload($response): array
    {
        if (is_array($response)) {
            return $response;
        }

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return is_array($data) ? $data : [];
        }

        if (is_object($response) && property_exists($response, 'original')) {
            $data = $response->original;

            if ($data instanceof \Illuminate\Http\JsonResponse) {
                $data = $data->getData(true);
            }

            if (is_array($data)) {
                return $data;
            }

            if (is_object($data)) {
                return (array) $data;
            }
        }

        return [];
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
