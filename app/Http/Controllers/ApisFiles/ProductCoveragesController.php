<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ProductCoverage;
use App\Models\ProductCoverageTranslation;
use App\Models\ProductRelatedCoverage;
use App\Models\ProductTranslation;
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

class ProductCoveragesController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:ProductCoverages View', ['only' => ['coveragesList', 'coverageSingleDetail', 'searchCoveragesList']]);
        $this->middleware('permission:ProductCoverages Add', ['only' => ['storeCoverage']]);
        $this->middleware('permission:ProductCoverages Edit', ['only' => ['updateCoverage']]);
        $this->middleware('permission:ProductCoverages Delete', ['only' => ['deleteCoverage']]);
    }
    
    // Get all List record
    public function coveragesList($lang, $per_page=6)
    {
        try {
            $coverageQuery = ProductCoverage::orderBy('created_at', 'ASC');
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $coverages = $coverageQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $coverages->count(), // All items in one "page"
                    'total' => $coverages->count(),
                ];
            } else {
                // Paginate the remaining
                $coverages = $coverageQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $coverages->currentPage(),
                    'last_page' => $coverages->lastPage(),
                    'per_page' => $coverages->perPage(),
                    'total' => $coverages->total(),
                ];
            }
            
            if($coverages->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Product Coverage not found'], 200);
            }
            
            $coverages_translations = $coverages->map(function($coverage) use ($lang) {
                $id = $coverage->id;
                
                $translation = ProductCoverageTranslation::where('coverage_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];

                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['field_required'] = (int) $coverage->field_required;
                $translatedData['checked_by_default'] = (int) $coverage->checked_by_default;
                $translatedData['coverage_status'] = (int) $coverage->coverage_status;
                $translatedData['countable_value'] = (int) $coverage->countable_value;
                $translatedData['per_day_price'] = (int) $coverage->per_day_price;
                $translatedData['address_is_required'] = (int) $coverage->address_is_required;
                $translatedData['vat_is_applicable'] = (int) $coverage->vat_is_applicable;
                $translatedData['recommended'] = (int) $coverage->recommended;
                $translatedData['less_30_days_price'] = $coverage->less_30_days_price;
                $translatedData['more_30_days_price'] = $coverage->more_30_days_price;
             
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $coverages_translations,
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
    
    // Get all List record for product management
    public function coveragesListForProduct($lang)
    {
        $coverageQuery = ProductCoverage::orderBy('created_at', 'ASC')->get();
        
        $coverages_translations = $coverageQuery->map(function($coverage) use ($lang) {
            $id = $coverage->id;
            
            $translation = ProductCoverageTranslation::where('coverage_id', $id)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();  
                        
            $coverage_title = "";            
            $coverageFilterData = [];
            if(!empty($translation)){
                $translatedData = json_decode($translation->field_values, true);
                $coverage_title = $translatedData['title'];
            } 
            
            // Handle image URLs for primary fields
            $coverageFilterData['coverage_id'] = $id;
            $coverageFilterData['coverage_title'] = $coverage_title;
            $coverageFilterData['less_30_days_price'] = "";
            $coverageFilterData['more_30_days_price'] = "";
         
            return $coverageFilterData;
        });
        
        return response()->json([
            'status' => 'true',
            'data' => $coverages_translations
        ], Response::HTTP_OK);
    }
    
    /* Data insertion part POST/{lang} */
    public function storeCoverage(Request $request, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'coverage_status' => 'required|numeric',
                'less_30_days_price' => 'nullable|numeric',
                'more_30_days_price' => 'nullable|numeric',
                'translation' => 'required|array',
                'translation.title' => 'required|string',
                'translation.tooltip' => 'nullable|string',
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
    
                $productCoverage = new ProductCoverage();
                
                $coverage_status = $request->input('coverage_status');
                $field_required = $request->input('field_required');
                $checked_by_default = $request->input('checked_by_default');
                $countable_value = $request->input('countable_value');
                $per_day_price = $request->input('per_day_price');
                $address_is_required = $request->input('address_is_required');
                $vat_is_applicable = $request->input('vat_is_applicable');
                $recommended = $request->input('recommended');
                $less_30_days_price = $request->input('less_30_days_price');
                $more_30_days_price = $request->input('more_30_days_price');
                $prices_by_locations = $request->input('prices_by_locations');
                
                $filtered_locations_prices = array_filter($prices_by_locations);
             
                $productCoverage->coverage_status = $coverage_status;
                $productCoverage->prices_by_locations = !empty($filtered_locations_prices) ? json_encode($filtered_locations_prices) : null;
                $productCoverage->field_required = $field_required;
                $productCoverage->checked_by_default = $checked_by_default;
                $productCoverage->countable_value = $countable_value;
                $productCoverage->per_day_price = $per_day_price;
                $productCoverage->address_is_required = $address_is_required;
                $productCoverage->vat_is_applicable = $vat_is_applicable;
                $productCoverage->recommended = $recommended;
                $productCoverage->less_30_days_price = $less_30_days_price;
                $productCoverage->more_30_days_price = $more_30_days_price;
                $productCoverage->created_by = $userId;
                $productCoverage->save();
        
                $coverageId = $productCoverage->id;
                $translations = $request->input('translation', []);
              
                $coverag_translation = new ProductCoverageTranslation();
                $coverag_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $coverag_translation->language = $lang;
                $coverag_translation->coverage_id = $coverageId;
                $coverag_translation->save();

                return response()->json(['status' => 'true', 'message' => 'Product coverage created successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data update part PUT/{id}/{lang} */
    public function updateCoverage(Request $request, $coverageId, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'coverage_status' => 'required|numeric',
                'less_30_days_price' => 'nullable|numeric',
                'more_30_days_price' => 'nullable|numeric',
                'translation' => 'required|array',
                'translation.title' => 'required|string',
                'translation.tooltip' => 'nullable|string',
            ];
    
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $user = Auth::user();
                $userId = $user->id;
                
                // Find the coverage by ID
                $productCoverage = ProductCoverage::find($coverageId);
    
                if (!$productCoverage) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Product coverage not found'
                    ], Response::HTTP_NOT_FOUND);
                }
    
                // Get input values
                $coverage_status = $request->input('coverage_status');
                $field_required = $request->input('field_required');
                $checked_by_default = $request->input('checked_by_default');
                $countable_value = $request->input('countable_value');
                $per_day_price = $request->input('per_day_price');
                $address_is_required = $request->input('address_is_required');
                $vat_is_applicable = $request->input('vat_is_applicable');
                $recommended = $request->input('recommended');
                $less_30_days_price = $request->input('less_30_days_price');
                $more_30_days_price = $request->input('more_30_days_price');
                $prices_by_locations = $request->input('prices_by_locations');
                
                $filtered_locations_prices = array_filter($prices_by_locations);
    
                // Update product coverage details
                $productCoverage->coverage_status = $coverage_status;
                $productCoverage->prices_by_locations = !empty($filtered_locations_prices) ? json_encode($filtered_locations_prices) : null;
                $productCoverage->field_required = $field_required;
                $productCoverage->checked_by_default = $checked_by_default;
                $productCoverage->countable_value = $countable_value;
                $productCoverage->per_day_price = $per_day_price;
                $productCoverage->address_is_required = $address_is_required;
                $productCoverage->vat_is_applicable = $vat_is_applicable;
                $productCoverage->recommended = $recommended;
                $productCoverage->less_30_days_price = $less_30_days_price;
                $productCoverage->more_30_days_price = $more_30_days_price;
                $productCoverage->save();
    
                // Handle translations
                $translations = $request->input('translation', []);
    
                // Check if translation exists for the given coverageId and language
                $coverage_translation = ProductCoverageTranslation::where('coverage_id', $coverageId)
                    ->where('language', $lang)
                    ->first();
    
                if (!$coverage_translation) {
                    // If no translation exists, create a new one
                    $coverage_translation = new ProductCoverageTranslation();
                    $coverage_translation->coverage_id = $coverageId;
                    $coverage_translation->language = $lang;
                }
    
                // Update the translation field values
                $coverage_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $coverage_translation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Product coverage updated successfully'], 200);
            }
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data fetch part GET/{id}/{lang} */
    public function coverageSingleDetail($id, $lang)
    {
        try {
            $coverage = ProductCoverage::find($id);

            if(!$coverage){
                return response()->json(['status' => 'false', 'message' => 'Coverage not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Fetch translation for the given language with optimized query
            $translation = ProductCoverageTranslation::where('coverage_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                ->first();
    
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
           
            // Handle image URLs for primary fields
            $translatedData['field_required'] = (int) $coverage->field_required;
            $translatedData['checked_by_default'] = (int) $coverage->checked_by_default;
            $translatedData['coverage_status'] = (int) $coverage->coverage_status;
            $translatedData['countable_value'] = (int) $coverage->countable_value;
            $translatedData['per_day_price'] = (int) $coverage->per_day_price;
            $translatedData['address_is_required'] = (int) $coverage->address_is_required;
            $translatedData['vat_is_applicable'] = (int) $coverage->vat_is_applicable;
            $translatedData['recommended'] = (int) $coverage->recommended;
            $translatedData['less_30_days_price'] = $coverage->less_30_days_price;
            $translatedData['more_30_days_price'] = $coverage->more_30_days_price;
            $translatedData['prices_by_locations'] = json_decode($coverage->prices_by_locations, true);
            
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
  
    /* Coverage DELETE/{id} */
    public function deleteCoverage($id)
    {
        try {
            
            $coverage = ProductCoverage::find($id);
    
            if (!$coverage) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Coverage not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the product record
            $coverage->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Coverage deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // List search function
    public function searchCoveragesList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $coveragesQuery = ProductCoverage::query()
            ->join('product_coverage_translations', function ($join) use ($lang) {
                $join->on('product_coverages.id', '=', 'product_coverage_translations.coverage_id')
                    ->where('product_coverage_translations.language', '=', $lang);
            })
            ->select('product_coverages.*', 'product_coverage_translations.field_values');

            // Apply search filters
            if (!empty($searchQuery)) {
                $coveragesQuery->where(function ($query) use ($searchQuery) {
                    $query->where('product_coverages.less_30_days_price', 'LIKE', "%{$searchQuery}%")
                            ->orWhere('product_coverages.more_30_days_price', 'LIKE', "%{$searchQuery}%")
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(product_coverage_translations.field_values, '$.title')) LIKE ?", ["%{$searchQuery}%"])
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(product_coverage_translations.field_values, '$.tooltip')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $coveragesQuery->orderBy('created_at', 'ASC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $coverages = $coveragesQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $coverages->count(), // All items in one "page"
                    'total' => $coverages->count(),
                ];
            } else {
                // Paginate the remaining partners
                $coverages = $coveragesQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $coverages->currentPage(),
                    'last_page' => $coverages->lastPage(),
                    'per_page' => $coverages->perPage(),
                    'total' => $coverages->total(),
                ];
            }
    
            if ($coverages->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No coverages found'], 200);
            }
    
            $coverages_translations = $coverages->map(function ($coverage) use ($lang) {
                $id = $coverage->id;
                
                // Fetch translation for the given language with optimized query
                $translation = ProductCoverageTranslation::where('coverage_id', $id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->first();
        
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
    
                $translatedData['id'] = $id;
                $translatedData['field_required'] = (int) $coverage->field_required;
                $translatedData['checked_by_default'] = (int) $coverage->checked_by_default;
                $translatedData['coverage_status'] = (int) $coverage->coverage_status;
                $translatedData['countable_value'] = (int) $coverage->countable_value;
                $translatedData['per_day_price'] = (int) $coverage->per_day_price;
                $translatedData['address_is_required'] = (int) $coverage->address_is_required;
                $translatedData['vat_is_applicable'] = (int) $coverage->vat_is_applicable;
                $translatedData['recommended'] = (int) $coverage->recommended;
                $translatedData['less_30_days_price'] = $coverage->less_30_days_price;
                $translatedData['more_30_days_price'] = $coverage->more_30_days_price;
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $coverages_translations,
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
