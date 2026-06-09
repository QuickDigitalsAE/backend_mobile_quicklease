<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\PromotionController;
use Illuminate\Http\Request;
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

class PromoCodeController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:PromoCode View', ['only' => ['promoCodesList', 'promoCodeSingleDetail', 'searchPromoCodesList']]);
        $this->middleware('permission:PromoCode Add', ['only' => ['storePromoCode']]);
        $this->middleware('permission:PromoCode Edit', ['only' => ['updatePromoCode']]);
        $this->middleware('permission:PromoCode Delete', ['only' => ['deletePromoCode']]);
    }
    
    // Get all list
    public function promoCodesList($lang, $per_page=6)
    {
        try {
            $promoCodeQuery = PromoCode::orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $promoCodes = $promoCodeQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $promoCodes->count(), // All items in one "page"
                    'total' => $promoCodes->count(),
                ];
            } else {
                // Paginate the remaining data
                $promoCodes = $promoCodeQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $promoCodes->currentPage(),
                    'last_page' => $promoCodes->lastPage(),
                    'per_page' => $promoCodes->perPage(),
                    'total' => $promoCodes->total(),
                ];
            }
            
            if($promoCodes->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Promo code not found'], 200);
            }
            
            $promoCodesList = $promoCodes->map(function($promo) use ($lang) {
                $id = $promo->id;
                $promotion_id = $promo->promotion_id;
                
                $promoData = [];
                // Fetch the first translation instead of using get()
                $promotionTranslation = PromotionTranslation::where('promotion_id', $promotion_id)
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
                $promoData['id'] = $id;
                $promoData['target_type'] = $promo->target_type;
                $promoData['promotion_id'] = $promotion_id;
                $promoData['promotion_title'] = $promotion_title;
                $promoData['code_title'] = $promo->code_title;
                $promoData['code_type'] = $promo->code_type;
                $promoData['code_value'] = $promo->code_value;
                $promoData['code'] = $promo->code;
                $promoData['code_status'] = $promo->code_status;
                $promoData['expires_at'] = $promo->expires_at;
                $promoData['created_at'] = Carbon::parse($promo->created_at)->format('d-m-Y H:i');
                
                return $promoData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $promoCodesList,
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
  
    /* Data insertion part POST/{lang} */
    public function storePromoCode(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'target_type' => 'required|string',
                'promotion_id' => 'nullable|numeric',
                'code_title' => 'required|string',
                'code_type'  => 'required|string',
                'code_value' => 'required|string',
                'code'       =>   'required|string|unique:promo_codes,code',
                'code_status'  => 'required|numeric',
                'expires_at' => 'nullable|date_format:Y-m-d'
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{
                
                $user = Auth::user();
                $userId = $user->id;

                $promoCode = new PromoCode();
                
                $promoCode->target_type = $request->target_type;
                $promoCode->promotion_id = $request->promotion_id;
                $promoCode->code_title = $request->code_title;
                $promoCode->code_type = $request->code_type;
                $promoCode->code_value = $request->code_value;
                $promoCode->code = $request->code;
                $promoCode->code_status = $request->code_status;
                $promoCode->expires_at = $request->expires_at;
                $promoCode->created_by = $userId;
                $promoCode->save();
                
                DB::commit(); // Commit transaction

                return response()->json(['status' => 'true', 'message' => 'Promo code created successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Data update part PUT/{id}/{lang} */
    public function updatePromoCode(Request $request, $id, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Define validation rules
            $rules = [
                'target_type' => 'required|string',
                'promotion_id' => 'nullable|numeric',
                'code_title'  => 'required|string',
                'code_type'  => 'required|string',
                'code_value'  => 'required|string',
                'code' => 'required|string|unique:promo_codes,code,' . $id . ',id',
                'code_status'  => 'required|numeric',
                'expires_at' => 'nullable|date_format:Y-m-d'
            ];
    
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                // $user = Auth::user();
                // $userId = $user->id;
    
                $promoCode = PromoCode::find($id);
                if (!$promoCode) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Promo Code not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $promoCode->target_type = $request->target_type;
                $promoCode->promotion_id = $request->promotion_id;
                $promoCode->code_title = $request->code_title;
                $promoCode->code_type = $request->code_type;
                $promoCode->code_value = $request->code_value;
                $promoCode->code = $request->code;
                $promoCode->code_status = $request->code_status;
                $promoCode->expires_at = $request->expires_at;
                $promoCode->save();
                
                DB::commit(); // Commit transaction
                
                return response()->json(['status' => 'true', 'message' => 'Promo Code updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Fetch part GET/{id}/{lang} */
    public function promoCodeSingleDetail($id, $lang)
    {
        try {
            $promoCode = PromoCode::find($id);

            if(!$promoCode){
                return response()->json(['status' => 'false', 'message' => 'Promo code not found'], Response::HTTP_NOT_FOUND);
            }
            
            $promoData = [];
            // Handle image URLs for primary fields
            $promoData['id'] = (int) $id;
            $promoData['target_type'] = $promoCode->target_type;
            $promoData['promotion_id'] = (int) $promoCode->promotion_id;
            $promoData['code_title'] = $promoCode->code_title;
            $promoData['code_type'] = $promoCode->code_type;
            $promoData['code_value'] = $promoCode->code_value;
            $promoData['code'] = $promoCode->code;
            $promoData['code_status'] = (int) $promoCode->code_status;
            $promoData['expires_at'] = $promoCode->expires_at;
            $promoData['created_at'] = Carbon::parse($promoCode->created_at)->format('d-m-Y H:i');
            
             // Fetch promotion list
            $promotionController = new PromotionController();
            $promotionList =  $promotionController->frontendListForPromo($lang);
            
            if(isset($promotionList->original['data'])){
                $promoData['promotion_list'] = $promotionList->original['data'];
            }else{
                $promoData['promotion_list'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $promoData
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data fetch part DELETE/{id} */
    public function deletePromoCode($id)
    {
        try {
            // Find the promotion by slug
            $promoCode = PromoCode::where('id', $id)->first();
    
            if (!$promoCode) {
                return response()->json(['status' => 'false', 'message' => 'Promo code not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Delete the promotion
            $promoCode->delete();
    
            return response()->json([
                'status' => 'true',
                'message' => 'Promo code deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // List search function
    public function searchPromoCodesList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $promoCodeQuery = PromoCode::query();

            if (!empty($searchQuery)) {
                $promoCodeQuery->join('promotion_translations', function ($join) use ($lang) {
                    $join->on('promotion_translations.promotion_id', '=', 'promo_codes.promotion_id')
                         ->where('promotion_translations.language', '=', $lang);
                });
            
                $promoCodeQuery->where(function ($query) use ($searchQuery) {
                    $query->where('target_type', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('code_title', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('code', 'LIKE', "%{$searchQuery}%")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(promotion_translations.field_values, '$.promotion_title')) LIKE ?", ["%{$searchQuery}%"]);
                });
                
                $promoCodeQuery->orderBy('promo_codes.created_at', 'DESC');
            }
    
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all excluding the recent one
                $promoCodes = $promoCodeQuery->get();
                
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $promoCodes->count(), // All items in one "page"
                    'total' => $promoCodes->count(),
                ];
            } else {
                // Paginate the remaining data
                $promoCodes = $promoCodeQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $promoCodes->currentPage(),
                    'last_page' => $promoCodes->lastPage(),
                    'per_page' => $promoCodes->perPage(),
                    'total' => $promoCodes->total(),
                ];
            }
    
            if ($promoCodes->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No promo code found'], 200);
            }
    
            $promoCodesList = $promoCodes->map(function($promo) use ($lang) {
                $id = $promo->id;
                $promotion_id = $promo->promotion_id;
                
                $promoData = [];
                // Fetch the first translation instead of using get()
                $promotionTranslation = PromotionTranslation::where('promotion_id', $promotion_id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
                
                // Ensure field_values exists and is a valid JSON before decoding
                $promotion_title = "";
                if (!empty($promotionTranslation)) {
                    $decodedData = json_decode($promotionTranslation->field_values, true);
                    $promotion_title = $decodedData['promotion_title'] ?? "";
                }
                
                $promoData = [];
                // Handle image URLs for primary fields
                $promoData['id'] = $id;
                $promoData['target_type'] = $promo->target_type;
                $promoData['promotion_id'] = $promotion_id;
                $promoData['promotion_title'] = $promotion_title;
                $promoData['code_title'] = $promo->code_title;
                $promoData['code_type'] = $promo->code_type;
                $promoData['code_value'] = $promo->code_value;
                $promoData['code'] = $promo->code;
                $promoData['code_status'] = $promo->code_status;
                $promoData['expires_at'] = $promo->expires_at;
                $promoData['created_at'] = Carbon::parse($promo->created_at)->format('d-m-Y H:i');
                
                return $promoData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $promoCodesList,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
