<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\ProductsController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Promotion;
use App\Models\PromotionTranslation;
use App\Models\Enquiry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PromotionController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Promotion View', ['only' => ['promotionsList', 'promotionSingleDetail', 'searchPromotionsList']]);
        $this->middleware('permission:Promotion Add', ['only' => ['storePromotion']]);
        $this->middleware('permission:Promotion Edit', ['only' => ['updatePromotion']]);
        $this->middleware('permission:Promotion Delete', ['only' => ['deletePromotion']]);
    }
    
    // Get all promotions list
    public function promotionsList($lang, $per_page=6)
    {
        try {
            $promotionQuery = Promotion::orderBy('created_at', 'ASC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $promotions = $promotionQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $promotions->count(), // All items in one "page"
                    'total' => $promotions->count(),
                ];
            } else {
                // Paginate the remaining data
                $promotions = $promotionQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                ];
            }
            
            if($promotions->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Promotion not found'], 200);
            }
            
            $promotions_translations = $promotions->map(function($promotion) use ($lang) {
                $id = $promotion->id;
                $created_by = $promotion->created_by;
                $updated_by = $promotion->updated_by;
                $created_at = $promotion->created_at;
                $updated_at = $promotion->updated_at;
                
                $translation = PromotionTranslation::where('promotion_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = PromotionTranslation::where('promotion_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);
                
                
                $promoCode = PromoCode::where('promotion_id','=',$id)->first();
            
                $promo_code = $code_type = $code_value =  $target_type = $expires_at = "";
                if(!empty($promoCode)){
                    $promo_code = $promoCode->code;
                    $code_type = $promoCode->code_type;
                    $code_value = $promoCode->code_value;
                    $target_type = $promoCode->target_type;
                    $expires_at = $promoCode->expires_at;
                }
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['schedule_date'] = $promotion->schedule_date;
                $translatedData['page_type'] = $promotion->page_type;
                $translatedData['promotion_status'] = (int) $promotion->promotion_status;
                $translatedData['promotion_slug'] = $promotion->slug;
                $translatedData['promotion_image'] = $promotion->image ? $this->getImageUrl($promotion->image) : null;
                $translatedData['promotion_banner'] = $promotion->banner_image ? $this->getImageUrl($promotion->banner_image) : null;
                $translatedData['brand_logo'] = $promotion->brand_logo ? $this->getImageUrl($promotion->brand_logo) : null;
                $translatedData['promo_code'] = $promo_code;
                $translatedData['promo_code_type'] = $code_type;
                $translatedData['promo_code_value'] = $code_value;
                $translatedData['promo_target_type'] = $target_type;
                $translatedData['promo_expiry'] = $expires_at;
                
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $promotions_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // GEt Frontend list
    public function frontendList($lang, $per_page=6)
    {
        try {
            // Get the current datetime
            $currentDateTime = Carbon::now('Asia/Karachi');
            
            // Query for the most recent blog
            $promotionQuery = Promotion::where(function($query) use ($currentDateTime) {
                                      $query->where('schedule_date', '<=', $currentDateTime)
                                            ->orWhereNull('schedule_date');
                                  })
                                ->where('promotion_status', '=', 1)
                                ->where('page_type','=','promotion')
                                ->orderBy('schedule_date', 'DESC'); // Get the last blog easily

            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $promotions = $promotionQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $promotions->count(), // All items in one "page"
                    'total' => $promotions->count(),
                ];
            } else {
                // Paginate the remaining data
                $promotions = $promotionQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                ];
            }
            
            if($promotions->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Promotions not found'], 200);
            }
            
            $promotions_translations = $promotions->map(function($promotion) use ($lang) {
                $id = $promotion->id;
                $created_by = $promotion->created_by;
                $updated_by = $promotion->updated_by;
                $created_at = $promotion->created_at;
                $updated_at = $promotion->updated_at;
                
                $translation = PromotionTranslation::where('promotion_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = PromotionTranslation::where('promotion_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);
                
                
                $promoCode = PromoCode::where('promotion_id','=',$id)->first();
            
                $promo_code = $code_type = $code_value =  $target_type = $expires_at = "";
                if(!empty($promoCode)){
                    $promo_code = $promoCode->code;
                    $code_type = $promoCode->code_type;
                    $code_value = $promoCode->code_value;
                    $target_type = $promoCode->target_type;
                    $expires_at = $promoCode->expires_at;
                }
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['schedule_date'] = $promotion->schedule_date;
                $translatedData['page_type'] = $promotion->page_type;
                $translatedData['promotion_status'] = (int) $promotion->promotion_status;
                $translatedData['promotion_slug'] = $promotion->slug;
                $translatedData['promotion_image'] = $promotion->image ? $this->getImageUrl($promotion->image) : null;
                $translatedData['promotion_banner'] = $promotion->banner_image ? $this->getImageUrl($promotion->banner_image) : null;
                $translatedData['brand_logo'] = $promotion->brand_logo ? $this->getImageUrl($promotion->brand_logo) : null;
                $translatedData['promo_code'] = $promo_code;
                $translatedData['promo_code_type'] = $code_type;
                $translatedData['promo_code_value'] = $code_value;
                $translatedData['promo_target_type'] = $target_type;
                $translatedData['promo_expiry'] = $expires_at;
                
                return $translatedData;
            });
            
            
            // Fetch blogs meta
            $webContentController = new WebContentController();
            $promotionMeta =  $webContentController->getWebMetaDeta('promotions',$lang);
            
            if($promotionMeta->original['data']){
                $metaData = $promotionMeta->original['data'];
            }else{
                $metaData = [];
            }
            
            return response()->json([
                'status' => 'true',
                'message' => 'Promotions retrieved successfully',
                'data' => [
                        'all_promotions' => $promotions_translations,
                        'meta' => $metaData
                        ],     
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // GEt Frontend list for promo code section
    public function frontendListForPromo($lang)
    {
        try {
            // Get the current datetime
            $currentDateTime = Carbon::now('Asia/Karachi');
            
            // Query for the most recent blog
            $promotions = Promotion::where('promotion_status', '=', 1)
                            ->where(function($query) use ($currentDateTime) {
                                  $query->where('schedule_date', '<=', $currentDateTime)
                                        ->orWhereNull('schedule_date');
                              })
                              ->orderBy('schedule_date', 'DESC')
                              ->get();

            if($promotions->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Promotions not found', 'data' => []], 200);
            }
            
            $promotions_list = $promotions->map(function($promotion) use ($lang) {
                $id = $promotion->id;
                $relatedCarsData = [];
                
                // Fetch the first translation instead of using get()
                $promotionTranslation = PromotionTranslation::where('promotion_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
                
                // Ensure field_values exists and is a valid JSON before decoding
                $promotion_title = "";
                if (!empty($promotionTranslation)) {
                    $decodedData = json_decode($promotionTranslation->field_values, true);
                    $promotion_title = $decodedData['promotion_title'] ?? "";
                }
                
                // Handle image URLs for primary fields
                $relatedCarsData['id'] = $id;
                $relatedCarsData['promotion_title'] = $promotion_title;
                
                return $relatedCarsData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $promotions_list
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchDetail($slug, $lang)
    {
        try {
            $promotion = Promotion::where('promotion_status', '=', 1)
                            ->where('slug',$slug)->first();

            if(!$promotion){
                return response()->json(['status' => 'false', 'message' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
            }
            
            $promotionId = $promotion->id;
            
            // Fetch the translation for the given language
            $translations = PromotionTranslation::where('promotion_id', $promotionId)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, '$lang', 'en')")
                    ->get()
                    ->keyBy('language');
                
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
            
            $promo_title = "";
            if(!empty($translatedData)){
                $promo_title = $translatedData['promotion_title'];
            }
            
            $carIds = json_decode($promotion->car_ids, true); // Decode JSON
            
            $ProductsController = new ProductsController();
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);

            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translatedData['related_cars'] = $catalogCarsList;
            }else{
                $translatedData['related_cars'] = [];
            }
            
            $promoCode = PromoCode::where('promotion_id','=',$promotionId)->first();
            
            $promo_code = $code_type = $code_value =  $target_type = $expires_at = "";
            if(!empty($promoCode)){
                $promo_code = $promoCode->code;
                $code_type = $promoCode->code_type;
                $code_value = $promoCode->code_value;
                $target_type = $promoCode->target_type;
                $expires_at = $promoCode->expires_at;
            }
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $promotionId;
            $translatedData['form_type'] = strtolower(str_replace(' ', '_', $promo_title));
            $translatedData['schedule_date'] = $promotion->schedule_date;
            $translatedData['promotion_status'] = (int) $promotion->promotion_status;
            $translatedData['promotion_slug'] = $promotion->slug;
            $translatedData['promotion_image'] = $promotion->image ? $this->getImageUrl($promotion->image) : null;
            $translatedData['promotion_banner'] = $promotion->banner_image ? $this->getImageUrl($promotion->banner_image) : null;
            $translatedData['brand_logo'] = $promotion->brand_logo ? $this->getImageUrl($promotion->brand_logo) : null;
            $translatedData['promo_code'] = $promo_code;
            $translatedData['promo_code_type'] = $code_type;
            $translatedData['promo_code_value'] = $code_value;
            $translatedData['promo_target_type'] = $target_type;
            $translatedData['promo_expiry'] = $expires_at;
            
            // WebContentController
            $webContentController = new WebContentController();
            
            // Element controller
            $elements = $webContentController->getWebElements($lang);

            if($elements->original['data']){
                $elements_data = $elements->original['data'];
            }else{
                $elements_data = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translatedData,
                'elements' => $elements_data
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Promotion get in touch form calculator */
    public function promotionsFormCalculator(Request $request, $promotion_id, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'date_from' => 'required|date_format:Y-m-d',
                "date_to" => 'required|date_format:Y-m-d',
                'product_id' => 'required|numeric'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => false,
                    'message' => $errorMessages,
                    'data' => []
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{
                $date_from = $request->date_from;
                $date_to = $request->date_to;
                $product_id = $request->product_id;
                
                $promotion = Promotion::find($promotion_id);

                if(!$promotion){
                    return response()->json(['status' => false, 'message' => 'Promotion not found', 'data' => []], Response::HTTP_NOT_FOUND);
                }
                
                $daysCount = $total = 0;
                if (!empty($date_from) && !empty($date_to)) {
                    
                    // Convert strings to Carbon instances
                    $from = Carbon::createFromFormat('Y-m-d', $date_from);
                    $to = Carbon::createFromFormat('Y-m-d', $date_to);
                    
                    // Calculate the difference in days
                    $daysCount = $from->diffInDays($to);
                }
                
                $product = Product::find($product_id);
    
                if($product){
                    
                    // Handle image URLs for primary fields
                    $daily_price = $product->daily_price;
                    $weekly_price = $product->weekly_price;
                    $monthly_price = $product->monthly_price;
                    
                    $price = 0;
                    $percentage = 5;
                    if ($daysCount <= 6) {
                        // Daily price
                        $price = $daysCount * $daily_price;
                    } elseif ($daysCount >= 7 && $daysCount < 30) {
                        // Weekly price
                        $weeks = $daysCount / 7;
                        $price = $weeks * $weekly_price;
                    } elseif ($daysCount >= 30) {
                        // Monthly price
                        $months = $daysCount / 30; // Rounds up to nearest month
                        $price = $months * $monthly_price;
                    }
                    
                    $unit_price = $sum_price = $sum_vat = $vat_price = 0;
                    if($price != 0){
                        $unit_price = ($price / $daysCount);
                        $sum_price = $price;
                        
                        // Sum price + VAT
                        $sum_vat = ($percentage / 100) * $sum_price;
                        $vat_price = $sum_price + $sum_vat;
                        
                    }
                    $vat = ($percentage / 100) * $price;
                    $before_discount_price = $price + $vat;
                    
                    $message =  $expires_at = "";
                    $discount_amount = 0;
                    
                    $promoCode = PromoCode::where('promotion_id','=',$promotion_id)
                                    ->where('code_status','=',1)
                                    ->first();
                    
                    $status = true;
                    $promo_status = false;
                    $message =  $expires_at = $promo_title_value = $promo_code_value = "";
                    $discount_amount = $promo_discount_amount = $promo_vat_amount = $promo_total_amount = $total_discount_incl_vat = 0;
                    if(!empty($promoCode)){
                        $code_type = $promoCode->code_type;
                        $code_value = $promoCode->code_value;
                        $target_type = $promoCode->target_type;
                        $expires_at = $promoCode->expires_at;
                        
                        if($target_type != 'promotion'){
                           $messages = [
                                            'en' => "This promo code is invalid.",
                                            'ar' => "رمز ترويجي غير صالح."
                                        ];
                                        
                            // Get the message related to the language 
                            $message = $messages[$lang]; 
                            $status = false;
                        }else if ($expires_at && Carbon::parse($expires_at)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                            $messages = [
                                            'en' => "This promo code has expired.",
                                            'ar' => "انتهت صلاحية الرمز الترويجي هذا."
                                        ];
                                        
                            // Get the message related to the language 
                            $message = $messages[$lang];
                            $status = false;
                        }else{
                            $messages = [
                                'en' => "Promo code applied successfully.",
                                'ar' => "تم تطبيق الرمز الترويجي بنجاح."
                            ];
                                        
                            // Get the message related to the language 
                            $message = $messages[$lang];
                            $promo_code_value = $code_value;
                            if($code_type == 'amount'){
                                $promo_title_value = $code_value; 
                                $discount_amount = $code_value;
                                $promo_discount_amount =  $sum_price - $discount_amount;
                                $promo_vat_amount = ($percentage / 100) * $discount_amount;
                                $promo_total_amount = $discount_amount + $promo_vat_amount;
                                $price -= $discount_amount;
                                
                            }elseif($code_type == 'percentage'){
                                // Subtract percentage discount
                                $promo_title_value = $code_value."%"; 
                                $discount_amount = ($price * $code_value) / 100;
                                $promo_discount_amount = $sum_price - $discount_amount;
                                $promo_vat_amount = ($percentage / 100) * $discount_amount;
                                $promo_total_amount = $discount_amount + $promo_vat_amount;
                                $price -= $discount_amount;
                            }
                            $promo_status = true;
                        }
                    }else{
                        $messages = [
                                        'en' => "This promo code is invalid.",
                                        'ar' => "رمز ترويجي غير صالح."
                                    ];
                                    
                        // Get the message related to the language 
                        $message = $messages[$lang]; 
                        $status = false;
                    }
                    
                    $vat = ($percentage / 100) * $price;
                    $total_price = $price + $vat;
                    
                    $car_detail = [
                        'price' => number_format($unit_price, 2, '.', ''),
                        'sum_price' => number_format($sum_price, 2, '.', ''),
                        'vat' => number_format($sum_vat, 2, '.', ''),
                        'vat_price' => number_format($vat_price, 2, '.', ''),
                        'days_count' => $daysCount,
                        'expires_at' => $expires_at,
                        'before_discount_price' => number_format($before_discount_price, 2, '.', ''),
                        'promo_code_value' => $promo_code_value,
                        'promo_title_value' => $promo_title_value,
                        'promo_discount_amount' => number_format($discount_amount, 2, '.', ''),
                        'promo_vat_amount' => number_format($promo_vat_amount, 2, '.', ''),
                        'promo_total_amount' => number_format($promo_total_amount, 2, '.', ''),
                        'total_discount' => number_format($discount_amount, 2, '.', ''),
                        'total_discount_vat' => number_format($promo_vat_amount, 2, '.', ''),
                        'total_discount_incl_vat' => number_format($promo_total_amount, 2, '.', ''),
                        'total' => number_format($total_price, 2, '.', ''),
                        'promo_status' => $promo_status
                    ];
                    
                    return response()->json([
                        'status' => $status,
                        'message' => $message,
                        'data' => $car_detail
                    ],200);
                }
            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], 200);
        }
    }

    /* Promotion data insertion part POST/{lang} */
    public function storePromotion(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'promotion_status' => 'required|numeric',
                'promotion_slug'  => 'required|string',
                "translation" => 'array',
                "car_ids" => 'required|array',
                "schedule_date" => 'nullable|date_format:Y-m-d H:i'
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{
                
                $user = Auth::user();
                $userId = $user->id;

                $promotion = new Promotion();
                // Handle image uploads for primary fields
                if (!empty($request->promotion_image)) {
                    // Upload new image
                    $promotion->image = $request->promotion_image;
                }
                
                // Handle banner image uploads for primary fields
                if (!empty($request->promotion_banner)) {
                    // Upload new banner image
                    $promotion->banner_image = $request->promotion_banner;
                }
                
                // Handle brand logo uploads
                if (!empty($request->brand_logo)) {
                    // Upload new banner image
                    $promotion->brand_logo = $request->brand_logo;
                }
                
                if($request->has('page_type')){
                    $promotion->page_type = $request->page_type ; 
                }
                
                $promotion->promotion_status = $request->promotion_status;
                $promotion->car_ids = json_encode($request->car_ids);
                $promotion->schedule_date = $request->schedule_date ?? null;
                $promotion->slug = $request->promotion_slug;
                $promotion->created_by = $userId;
                $promotion->save();
        
                $promotionId = $promotion->id;
                $translations = $request->input('translation', []);
                
                $promotion_translation = new PromotionTranslation();
                $promotion_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $promotion_translation->language = $lang;
                $promotion_translation->promotion_id = $promotionId;
                $promotion_translation->save();
                
                DB::commit(); // Commit transaction

                return response()->json(['status' => 'true', 'message' => 'Promotion created successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Promotion tab data update part PUT/{id}/{lang} */
    public function updatePromotion(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'promotion_status' => 'required|numeric',
                'promotion_slug'  => 'nullable|string',
                "translation" => 'array',
                "car_ids" => 'required|array',
                "schedule_date" => 'nullable|date_format:Y-m-d H:i', 
            ];
    
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $user = Auth::user();
                $userId = $user->id;
    
                $promotion = Promotion::find($id);
                if (!$promotion) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Promotion not found'
                    ], Response::HTTP_NOT_FOUND);
                }
        
                // Handle image uploads for primary fields
                if (!empty($request->promotion_image)) {
                    // Delete the old image if it exists
                    if ($promotion->image && Storage::disk('public')->exists($promotion->image)) {
                        Storage::disk('public')->delete($promotion->image);
                    }
                    
                    // Upload new image
                    $promotion->image = $request->promotion_image;
                }
                
                // Handle banner image uploads for primary fields
                if (!empty($request->promotion_banner)) {
                    // Delete the old image if it exists
                    if ($promotion->banner_image && Storage::disk('public')->exists($promotion->banner_image)) {
                        Storage::disk('public')->delete($promotion->banner_image);
                    }
                    
                    // Upload new banner image
                    $promotion->banner_image = $request->promotion_banner;
                }
                
                // Handle brand logo uploads
                if (!empty($request->brand_logo)) {                    
                    // Delete the old image if it exists
                    if ($promotion->brand_logo && Storage::disk('public')->exists($promotion->brand_logo)) {
                        Storage::disk('public')->delete($promotion->brand_logo);
                    }
                    
                    // Upload new banner image
                    $promotion->brand_logo = $request->brand_logo;
                }
                
                if($request->has('page_type')){
                    $promotion->page_type = $request->page_type ; 
                }
                
                $promotion->promotion_status = $request->promotion_status;
                $promotion->car_ids = json_encode($request->car_ids);
                $promotion->schedule_date = $request->schedule_date ?? null;
                $promotion->slug = $request->promotion_slug;
                $promotion->updated_by = $userId;
                $promotion->save();
    
                $translations = $request->input('translation', []);
    
                // Update translations
                $promotionTranslation = PromotionTranslation::where('promotion_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                if (!$promotionTranslation) {
                    $promotionTranslation = new PromotionTranslation();
                    $promotionTranslation->promotion_id = $id;
                    $promotionTranslation->language = $lang;
                }
    
                $promotionTranslation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $promotionTranslation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Promotion updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* promotion fetch part GET/{id}/{lang} */
    public function promotionSingleDetail($id, $lang)
    {
        try {
            $promotion = Promotion::find($id);

            if(!$promotion){
                return response()->json(['status' => 'false', 'message' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
            }
            
            $promotionId = $promotion->id;
            // Fetch the translation for the given language
            $translation = PromotionTranslation::where('promotion_id', $promotionId)
                ->where('language', $lang)
                ->first();
    
            $translatedData = [];
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = PromotionTranslation::where('promotion_id', $promotionId)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $translatedData = json_decode($defaultData->field_values, true);
                }
            }
            
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $promotionId;
            $translatedData['schedule_date'] = $promotion->schedule_date;
            $translatedData['page_type'] = $promotion->page_type;
            $translatedData['promotion_status'] = (int) $promotion->promotion_status;
            $translatedData['promotion_slug'] = $promotion->slug;
            $translatedData['promotion_image'] = $promotion->image ? $this->getImageUrl($promotion->image) : null;
            $translatedData['promotion_banner'] = $promotion->banner_image ? $this->getImageUrl($promotion->banner_image) : null;
            $translatedData['brand_logo'] = $promotion->brand_logo ? $this->getImageUrl($promotion->brand_logo) : null;
            
            $carIds = json_decode($promotion->car_ids, true); // Decode JSON
            
            $ProductsController = new ProductsController();
            $productsFetch =  $ProductsController->frontendProductsList($lang,0,1);
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);

            if ($productsFetch->original['data']) {
                $translatedData['all_cars'] = $productsFetch->original['data'];
            } else {
                $translatedData['all_cars'] = [];
            }
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translatedData['related_cars'] = $catalogCarsList;
            }else{
                $translatedData['related_cars'] = [];
            }
            
            
             return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Promotion data fetch part DELETE/{id} */
    public function deletePromotion($id)
    {
        try {
            // Find the promotion by slug
            $promotion = Promotion::where('id', $id)->first();
    
            if (!$promotion) {
                return response()->json(['status' => 'false', 'message' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Delete images if they exist
            if ($promotion->image && Storage::disk('public')->exists($promotion->image)) {
                Storage::disk('public')->delete($promotion->image);
            }
    
            if ($promotion->banner_image && Storage::disk('public')->exists($promotion->banner_image)) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
    
            if ($promotion->brand_logo && Storage::disk('public')->exists($promotion->brand_logo)) {
                Storage::disk('public')->delete($promotion->brand_logo);
            }
    
            // Get the promotion ID
            $promotionId = $promotion->id;
    
            // Delete the associated translations
            PromotionTranslation::where('promotion_id', $promotionId)->delete();
    
            // Delete the promotion
            $promotion->delete();
    
            return response()->json([
                'status' => 'true',
                'message' => 'Promotion deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Promotions list search function
    public function searchPromotionsList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $promotionQuery = Promotion::query()
            ->join('promotion_translations', function ($join) use ($lang) {
                $join->on('promotions.id', '=', 'promotion_translations.promotion_id')
                    ->where('promotion_translations.language', '=', $lang);
            })
            ->select('promotions.*', 'promotion_translations.field_values');

            // Apply search filters for both slug and promotion_title
            if (!empty($searchQuery)) {
                $promotionQuery->where(function ($query) use ($searchQuery) {
                    $query->where('promotions.slug', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('promotions.page_type', 'LIKE', "%{$searchQuery}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(promotion_translations.field_values, '$.promotion_title')) LIKE ?", ["%{$searchQuery}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(promotion_translations.field_values, '$.promotion_heading')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $promotionQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $promotions = $promotionQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $promotions->count(), // All items in one "page"
                    'total' => $promotions->count(),
                ];
            } else {
                // Paginate the remaining data
                $promotions = $promotionQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                ];
            }
    
            if ($promotions->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No promotions found'], 200);
            }
    
            $promotions_translations = $promotions->map(function ($promotion) use ($lang) {
                $id = $promotion->id;
                $translation = PromotionTranslation::where('promotion_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->field_values, true);
                } else {
                    $defaultData = PromotionTranslation::where('promotion_id', $id)
                        ->where('language', 'en')
                        ->first();
    
                    if (!empty($defaultData)) {
                        $translatedData = json_decode($defaultData->field_values, true);
                    }
                }
    
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['page_type'] = $promotion->page_type;
                $translatedData['promotion_status'] = (int) $promotion->promotion_status;
                $translatedData['promotion_slug'] = $promotion->slug;
                $translatedData['promotion_image'] = $promotion->image ? $this->getImageUrl($promotion->image) : null;
                $translatedData['promotion_banner'] = $promotion->banner_image ? $this->getImageUrl($promotion->banner_image) : null;
                $translatedData['brand_logo'] = $promotion->brand_logo ? $this->getImageUrl($promotion->brand_logo) : null;
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $promotions_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getUserName($userId)
    {
        $userName = \App\Models\User::where('id', $userId)->pluck('name')->first();
        
        return $userName;
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}