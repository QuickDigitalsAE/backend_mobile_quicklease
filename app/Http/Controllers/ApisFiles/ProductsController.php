<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\ProductCoveragesController;
use App\Http\Controllers\ApisFiles\GoogleReviewController;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\ProductProperty;
use App\Models\ProductRelatedCoverage;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Catalog;
use App\Models\ProductCoverage;
use App\Models\ProductCoverageTranslation;
use App\Models\ProductTranslation;
use App\Models\PropertyTranslation;
use App\Models\CatalogTranslation;
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

class ProductsController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Products View', ['only' => ['productsList', 'productSingleDetail', 'searchProductsList']]);
        $this->middleware('permission:Products Add', ['only' => ['storeProduct']]);
        $this->middleware('permission:Products Edit', ['only' => ['updateProduct']]);
        $this->middleware('permission:Products Delete', ['only' => ['deleteProduct']]);
    }
    
    // Get all List record
    public function productsList($lang, $per_page=6)
    {
        try {
            $productQuery = Product::orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs exclu ding the recent one
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
            
            if($products->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Products not found', 'data' => []], 200);
            }
            
            $products_translations = $products->map(function($product) use ($lang) {
                $id = $product->id;
                $created_by = $product->created_by;
                $updated_by = $product->updated_by;
                $created_at = $product->created_at;
                $updated_at = $product->updated_at;
                
                $translation = ProductTranslation::where('product_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];

                $catalog_slug = $catalog_title = $parent_slug =  "";
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
                            }
                        }
            
            
                        $catalog_title = $catalogTranslation['catalog_title'];
                        $catalog_slug = $catalog->slug;
                    }
                }
                
                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['parent_slug'] = $parent_slug;
                $translatedData['catalog_title'] = $catalog_title;
                $translatedData['catalog_slug'] = $catalog_slug;
                $translatedData['featured'] = (int) $product->featured;
                $translatedData['promo_status'] = (int) $product->promo_status;
                $translatedData['product_status'] = (int) $product->product_status;
                $translatedData['stock_status'] = (int) $product->stock_status;
                $translatedData['show_documents'] = (int) $product->show_documents;
                $translatedData['book_now_button'] = (int) $product->book_now_button;
                $translatedData['pay_now_discount'] = $product->pay_now_discount;
                $translatedData['vehicle_type']     = $product->vehicle_type;
                $translatedData['show_on_home'] = (int) $product->show_on_home;
                $translatedData['specification_auto'] = (int) $product->specification_auto;
                $translatedData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;;
                $translatedData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
                $translatedData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
                $translatedData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
                $translatedData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
                $translatedData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
                $translatedData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
                $translatedData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
                $translatedData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
                $translatedData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
                $translatedData['year'] = $product->year;
                $translatedData['model'] = $product->model;
                $translatedData['slug'] = $product->slug;
                $translatedData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
             
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $products_translations,
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
    
     // Get all frontend list
    public function frontendProductsList($lang, $home_page = 0, $promotion_list = 0)
    {
        try {
            // Fetch products based on provided car IDs or get all products
            $query = Product::where('product_status', '=', 1);
            
            if($home_page == 1){
                $query->where('show_on_home', '=', 1);
                $query->orderBy('daily_price', 'ASC');
            }elseif($promotion_list == 1){
                $query->where(function ($query) {
                    $query->whereNotNull('daily_price')
                          ->where('daily_price', '>', 0);
                })
                ->where(function ($query) {
                    $query->whereNotNull('weekly_price')
                          ->where('weekly_price', '>', 0);
                })
                ->where(function ($query) {
                    $query->whereNotNull('monthly_price')
                          ->where('monthly_price', '>', 0);
                })
                ->where('stock_status','=',1)
                ->orderBy('created_at', 'DESC');
            }else{
                $query->orderBy('created_at', 'DESC');
            }
    
            $products = $query->get();
            
            if($products->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Products not found', 'data' => [] ], 200);
            }
            
            $products_translations = $products->map(function($product) use ($lang) {
                $id = $product->id;
                $created_by = $product->created_by;
                $updated_by = $product->updated_by;
                $created_at = $product->created_at;
                $updated_at = $product->updated_at;
                
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
                $productListData['pay_now_discount'] = $product->pay_now_discount;
                $productListData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;;
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
                
                if($productInnerDetails->original['data']){
                    $productInnerData = $productInnerDetails->original['data'];
                    $productListData['services'] = $productInnerData['services'];
                    $productListData['telephone_number'] = $productInnerData['telephone_number'];
                    $productListData['whatsapp'] = $productInnerData['whatsapp'];
                }else{
                    $productListData['services'] = "";
                    $productListData['telephone_number'] = "";
                    $productListData['whatsapp'] = "";
                }
                
                return $productListData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $products_translations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Get all frontend list for mobile
    public function mobileProductsList($lang, $home_page = 0, $promotion_list = 0)
    {
        try {
            // Fetch products based on provided car IDs or get all products
            $query = Product::where('product_status', '=', 1)->where('stock_status','=',1);
            
            if($home_page == 1){
                $query->where('show_on_home', '=', 1);
                $query->orderBy('daily_price', 'ASC');
            }elseif($promotion_list == 1){
                $query->where(function ($query) {
                    $query->whereNotNull('daily_price')
                          ->where('daily_price', '>', 0);
                })
                ->where(function ($query) {
                    $query->whereNotNull('weekly_price')
                          ->where('weekly_price', '>', 0);
                })
                ->where(function ($query) {
                    $query->whereNotNull('monthly_price')
                          ->where('monthly_price', '>', 0);
                })
                ->orderBy('created_at', 'DESC');
            }else{
                $query->orderBy('created_at', 'DESC');
            }
    
            $products = $query->get();
            
            if($products->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Products not found', 'data' => [] ], 200);
            }
            
            $products_translations = $products->map(function($product) use ($lang) {
                $id = $product->id;
                $created_by = $product->created_by;
                $updated_by = $product->updated_by;
                $created_at = $product->created_at;
                $updated_at = $product->updated_at;
                
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
                $productListData['pay_now_discount'] = $product->pay_now_discount;
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
                $productListData['page_full_slug'] = $page_full_slug;
                $productListData['slug'] = $product_slug;
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
                
                if($productInnerDetails->original['data']){
                    $productInnerData = $productInnerDetails->original['data'];
                    $productListData['services'] = $productInnerData['services'];
                    $productListData['telephone_number'] = $productInnerData['telephone_number'];
                    $productListData['whatsapp'] = $productInnerData['whatsapp'];
                }else{
                    $productListData['services'] = "";
                    $productListData['telephone_number'] = "";
                    $productListData['whatsapp'] = "";
                }
                
                return $productListData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $products_translations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Get all cars for dropdown list
    public function allCarsDropdownList($lang, $car_ids = "")
    {
        try {
            
            $productQuery = Product::where('product_status', '=', 1)
                        ->orderBy('created_at', 'DESC');
            
            if (!empty($car_ids)) {
                $productQuery->whereIn('id', $car_ids);
            }
    
            $products = $productQuery->get();
            
            if($products->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Products not found', 'data' => []], 200);
            }
            
            $products_translations = $products->map(function($product) use ($lang) {
                $id = $product->id;
                $relatedCarsData = [];
                
                // Fetch the first translation instead of using get()
                $productTranslation = ProductTranslation::where('product_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
                
                // Ensure field_values exists and is a valid JSON before decoding
                $productData = [];
                if (!empty($productTranslation)) {
                    $decodedData = json_decode($productTranslation->field_values, true);
                    if (is_array($decodedData)) {
                        $productData = $decodedData;
                    }
                }
                
                $product_tile = $productData['product_title'] ?? "";

                // Handle image URLs for primary fields
                $relatedCarsData['id'] = $id;
                $relatedCarsData['product_title']  = $product_tile;
                $relatedCarsData['slug'] = $product->slug;
                $relatedCarsData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
                
                return $relatedCarsData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $products_translations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage(),
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Get Frontend all cars List related to the catalog
    public function fetchCatalogCars($lang, $per_page = 0, $car_ids = [], $for_flexible = false , $for_catalog = false, $availability = null)
    {
        try {
            $productQuery = Product::where('product_status', '=', 1)
                                ->orderBy('stock_status', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if($for_catalog && empty($car_ids)){
                return response()->json(['status' => 'false', 'message' => 'array of the car list is empty', 'data' => []], 200);
            }else if (!empty($car_ids)) {
                $productQuery->whereIn('id', $car_ids);
            }
            
            if ($for_flexible) {
                $productQuery->whereNotNull('flexible_cars_monthly_prices')
                            ->where('flexible_cars_monthly_prices', '!=', '');
                $productQuery->where('stock_status', '=', '1');
            }else if (isset($availability)) {
                $productQuery->where('stock_status', $availability);
            }
            
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
            
            if($products->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Products not found', 'data' => []], 200);
            }
            
            $allProducts = $products->map(function($product) use ($lang) {
                $id = $product->id;
                $relatedCarsData = [];
                
                $productTranslation = ProductTranslation::where('product_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
                // Decode JSON translations
                $productData = isset($productTranslation[$lang]) ? json_decode($productTranslation[$lang]->field_values, true) : json_decode($productTranslation['en']->field_values, true);
                
                $product_tile = "";
                if($productData){
                    $product_tile = $productData['product_title'];
                }
                
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

                // Fetch product inner page content
                $webContentController = new WebContentController();
                $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
                
                $show_documents = $product->show_documents;
                $services = "";
                if($productInnerDetails->original['data']){
                    $productInnerData = $productInnerDetails->original['data'];
                    $relatedCarsData['services'] = $productInnerData['services'];
                    $relatedCarsData['telephone_number'] = $productInnerData['telephone_number'];
                    $relatedCarsData['whatsapp'] = $productInnerData['whatsapp'];
                }else{
                    $relatedCarsData['services'] = "";
                    $relatedCarsData['telephone_number'] = "";
                    $relatedCarsData['whatsapp'] = "";
                }
                
                // Get all group properties with related product values 
                $fetchAllProperties = $this->groupProductProperties($id, $lang, 4);
                
                $specification_auto = $product->specification_auto;
                if($specification_auto == 1){
                    $relatedCarsData['specification'] = 'Full Option';
                } elseif($specification_auto == 2) {
                    $relatedCarsData['specification'] = 'Medium Option';
                } elseif($specification_auto == 3) {
                    $relatedCarsData['specification'] = 'Basic Option';
                }
                
                $flexibleMonthlyPrices = json_decode($product->flexible_cars_monthly_prices, true) ?? [];

                $roundedFlexiblePrices = array_map(function ($value) {
                    return round((float) $value);
                }, $flexibleMonthlyPrices);

                
                $monthlyPrices = json_decode($product->personal_cars_monthly_prices, true) ?? [];
                $roundedPrices = array_map(function ($value) {
                    return round((float) $value);
                }, $monthlyPrices);
                
                // Handle image URLs for primary fields
                $relatedCarsData['id'] = $id;
                $relatedCarsData['product_title']  = $product_tile;
                $relatedCarsData['parent_slug'] = $parent_slug;
                $relatedCarsData['catalog_title'] = $catalog_title;
                $relatedCarsData['catalog_slug'] = $catalog_slug;
                $relatedCarsData['vehicle_type'] = $product->vehicle_type;
                $relatedCarsData['product_status'] = (int) $product->product_status;
                $relatedCarsData['featured'] = (int) $product->featured;
                $relatedCarsData['promo_status'] = (int) $product->promo_status;
                $relatedCarsData['stock_status'] = (int) $product->stock_status;
                $relatedCarsData['show_documents'] = (int) $product->show_documents;
                $relatedCarsData['book_now_button'] = (int) $product->book_now_button;
                $relatedCarsData['pay_now_discount'] = $product->pay_now_discount;
                $relatedCarsData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;
                $relatedCarsData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
                $relatedCarsData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
                $relatedCarsData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
                $relatedCarsData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
                $relatedCarsData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
                $relatedCarsData['flexible_cars_monthly_prices'] = $roundedFlexiblePrices;
                $relatedCarsData['personal_cars_monthly_prices'] = $roundedPrices;
                $relatedCarsData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
                $relatedCarsData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
                $relatedCarsData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
                $relatedCarsData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
                $relatedCarsData['year'] = $product->year;
                $relatedCarsData['model'] = $product->model;
                $relatedCarsData['page_full_slug'] = $page_full_slug;
                $relatedCarsData['slug'] = $product_slug;
                $relatedCarsData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
                $relatedCarsData['groupedProperties'] = $fetchAllProperties;
                
                return $relatedCarsData;
            });
            
            // $current_page = request()->input('page', 1);
            // $fetchNextTwoCars = $this->fetchNextTwoCars($lang, $current_page, $car_ids, $availability);
            
            // $allProducts = $products_translations->merge($fetchNextTwoCars);

            return response()->json([
                'status' => 'true',
                'data' => $allProducts,
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
    
    public function fetchNextTwoCars($lang, $current_page = 1, $car_ids = [], $availability = null)
    {
        $productQuery = Product::where('product_status', '=', 1)
                            ->orderBy('stock_status', 'DESC');

        if (!empty($car_ids)) {
            $productQuery->whereIn('id', $car_ids);
        }

        if (isset($availability)) {
            $productQuery->where('stock_status', $availability);
        }

        $offset = $current_page * 10; // Assuming 10 per page from main list
        $nextProducts = $productQuery->skip($offset)->take(2)->get();

        if ($nextProducts->isEmpty()) {
            return [];
        }

        // Translate products same way as in fetchCatalogCars
        $products_translations = $nextProducts->map(function ($product) use ($lang) {
            $id = $product->id;
            $relatedCarsData = [];

            $productTranslation = ProductTranslation::where('product_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');

            $productData = isset($productTranslation[$lang]) ? json_decode($productTranslation[$lang]->field_values, true) : json_decode($productTranslation['en']->field_values, true);
            $product_tile = $productData['product_title'] ?? "";

            $catalog_slug = $catalog_title = $parent_slug = $page_full_slug = "";
            if (!empty($product->catalog_id)) {
                $catalog = Catalog::find($product->catalog_id);
                if ($catalog) {
                    $catalogTranslation = CatalogTranslation::where('catalog_id', $catalog->id)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();

                    $translated = $catalogTranslation ? json_decode($catalogTranslation->field_values, true) : [];

                    if (!empty($catalog->parent_id)) {
                        $parent_catalog = Catalog::find($catalog->parent_id);
                        if ($parent_catalog) {
                            $parent_slug = $parent_catalog->slug;
                            $page_full_slug .= $parent_slug . '/';
                        }
                    }

                    $catalog_title = $translated['catalog_title'] ?? "";
                    $catalog_slug = $catalog->slug;
                    $page_full_slug .= $catalog_slug . '/';
                }
            }

            $product_slug = $product->slug;
            $page_full_slug .= $product_slug . '/';

            $webContentController = new WebContentController();
            $productInnerDetails = $webContentController->getWebProductInnerContent($lang);

            $relatedCarsData['services'] = $productInnerDetails->original['data']['services'] ?? "";
            $relatedCarsData['telephone_number'] = $productInnerDetails->original['data']['telephone_number'] ?? "";
            $relatedCarsData['whatsapp'] = $productInnerDetails->original['data']['whatsapp'] ?? "";

            $fetchAllProperties = $this->groupProductProperties($id, $lang, 4);

            $flexibleMonthlyPrices = json_decode($product->flexible_cars_monthly_prices, true) ?? [];
            $roundedFlexiblePrices = array_map(function ($value) {
                return round((float) $value);
            }, $flexibleMonthlyPrices);
            
            $monthlyPrices = json_decode($product->personal_cars_monthly_prices, true) ?? [];

            $roundedPrices = array_map(function ($value) {
                return round((float) $value);
            }, $monthlyPrices);

            $relatedCarsData['id'] = $id;
            $relatedCarsData['product_title'] = $product_tile;
            $relatedCarsData['parent_slug'] = $parent_slug;
            $relatedCarsData['catalog_title'] = $catalog_title;
            $relatedCarsData['catalog_slug'] = $catalog_slug;
            $relatedCarsData['vehicle_type'] = $product->vehicle_type;
            $relatedCarsData['product_status'] = (int) $product->product_status;
            $relatedCarsData['featured'] = (int) $product->featured;
            $relatedCarsData['promo_status'] = (int) $product->promo_status;
            $relatedCarsData['stock_status'] = (int) $product->stock_status;
            $relatedCarsData['show_documents'] = (int) $product->show_documents;
            $relatedCarsData['book_now_button'] = (int) $product->book_now_button;
            $relatedCarsData['pay_now_discount'] = $product->pay_now_discount;
            $relatedCarsData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;;
            $relatedCarsData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
            $relatedCarsData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
            $relatedCarsData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
            $relatedCarsData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
            $relatedCarsData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
            $relatedCarsData['flexible_cars_monthly_prices'] = $roundedFlexiblePrices;
            $relatedCarsData['personal_cars_monthly_prices'] = $roundedPrices;
            $relatedCarsData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
            $relatedCarsData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
            $relatedCarsData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
            $relatedCarsData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
            $relatedCarsData['year'] = $product->year;
            $relatedCarsData['model'] = $product->model;
            $relatedCarsData['page_full_slug'] = $page_full_slug;
            $relatedCarsData['slug'] = $product_slug;
            $relatedCarsData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
            $relatedCarsData['groupedProperties'] = $fetchAllProperties;

            return $relatedCarsData;
        });

        return $products_translations;
    }

    // Get all cars locations
    public function carsLocations($lang)
    {
        try {
            
            // Fetch product inner page content
            $webContentController = new WebContentController();
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
            
            $locations = [];
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $locations['locations'] = $productInnerData['locations'];
            }else{
                $locations['locations'] = "";
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $locations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], 500);
        }
    }
    
    /* Data insertion part POST/{lang} */
    public function storeProduct(Request $request, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'slug'  => 'required|string|unique:products,slug',
                'catalog_id' => 'required|numeric',
                'vehicle_type' => 'required|string',
                'product_status' => 'required|numeric',
                'featured' => 'required|numeric',
                'promo_status' => 'required|numeric',
                'stock_status' => 'required|numeric',
                'book_now_button' => 'required|numeric',
                'year' => 'nullable|string',
                'model' => 'nullable|string',
                'main_image' => 'required|string',
                'translation' => 'array',
                'properties' => 'array'
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
    
                $product = new Product();
                
                $catalog_id = $request->input('catalog_id');
                $main_image = $request->input('main_image');
                $car_images = $request->input('car_images');
                $additional_catalog_ids = $request->input('additional_catalog_ids');
                $car_locations = $request->input('car_locations');
                $featured = $request->input('featured');
                $pay_now_discount = $request->input('pay_now_discount');
                $promo_status = $request->input('promo_status');
                $product_status = $request->input('product_status');
                $stock_status = $request->input('stock_status');
                $daily_price = $request->input('daily_price');
                $old_daily_price = $request->input('old_daily_price');
                $weekly_price = $request->input('weekly_price');
                $old_weekly_price = $request->input('old_weekly_price');
                $monthly_price = $request->input('monthly_price');
                $old_monthly_price = $request->input('old_monthly_price');
                $flexible_cars_monthly_prices = $request->input('flexible_cars_monthly_prices');
                $personal_cars_monthly_prices = $request->input('personal_cars_monthly_prices');
                $monthly_installment_24_months = $request->input('monthly_installment_24_months');
                $monthly_installment_36_months = $request->input('monthly_installment_36_months');
                $installment_per_month = $request->input('installment_per_month');
                $installment_per_month_with_down = $request->input('installment_per_month_with_down');
                $installment_per_month_final_term = $request->input('installment_per_month_final_term');
                $down_payment = $request->input('down_payment');
                $security_deposit = $request->input('security_deposit');
                $security_deposit_waiver_daily = $request->input('security_deposit_waiver_daily');
                $security_deposit_waiver_monthly = $request->input('security_deposit_waiver_monthly');
                $vehicle_type = $request->input('vehicle_type');
                $specification_auto = $request->input('specification_auto');
                $year = $request->input('year');
                $model = $request->input('model');
                $book_now_button = $request->input('book_now_button');
                $show_on_home = $request->input('show_on_home');
                $show_documents = $request->input('show_documents');
                $slug = $request->input('slug');
                
                
                // Handle main image uploads for car
                if (!empty($main_image)) {
                    $product->main_image = $main_image;
                }
                
                // Gallery images for car
                if (!empty($car_images)) {
                    $product->car_images = json_encode($car_images) ?? null;
                }
                
                $filtered_catalog_ids = $filtered_locations = [];
                if(is_array($additional_catalog_ids) && $additional_catalog_ids != null){
                    $filtered_catalog_ids = array_filter($additional_catalog_ids);
                }
                
                if(is_array($car_locations) && $car_locations != null){
                    $filtered_locations = array_filter($car_locations);
                }
                
                // Ensure $catalog_id is an array
                $merged_catalog_ids = array_map('strval', array_merge([$catalog_id], $filtered_catalog_ids));
            
                $product->catalog_id = $catalog_id;
                $product->additional_catalog_ids = !empty($filtered_catalog_ids) ? json_encode($filtered_catalog_ids) : null;
                $product->car_locations = !empty($filtered_locations) ? json_encode($filtered_locations) : null;
                $product->product_status = $product_status;
                $product->featured = $featured;
                $product->pay_now_discount = $pay_now_discount;
                $product->promo_status = $promo_status;
                $product->stock_status = $stock_status;
                $product->daily_price = $daily_price;
                $product->old_daily_price = $old_daily_price;
                $product->weekly_price = $weekly_price;
                $product->old_weekly_price = $old_weekly_price;
                $product->monthly_price = $monthly_price;
                $product->old_monthly_price = $old_monthly_price;
                $product->flexible_cars_monthly_prices = !empty($flexible_cars_monthly_prices) ? json_encode($flexible_cars_monthly_prices) : null;
                $product->personal_cars_monthly_prices = !empty($personal_cars_monthly_prices) ? json_encode($personal_cars_monthly_prices) : null;
                $product->monthly_installment_24_months = $monthly_installment_24_months;
                $product->monthly_installment_36_months = $monthly_installment_36_months;
                $product->installment_per_month = $installment_per_month;
                $product->installment_per_month_with_down = $installment_per_month_with_down;
                $product->installment_per_month_final_term = $installment_per_month_final_term;
                $product->down_payment = $down_payment;
                $product->security_deposit = $security_deposit;
                $product->security_deposit_waiver_daily = $security_deposit_waiver_daily;
                $product->security_deposit_waiver_monthly = $security_deposit_waiver_monthly;
                $product->vehicle_type = $vehicle_type;
                $product->specification_auto = $specification_auto;
                $product->year = $year;
                $product->model = $model;
                $product->book_now_button = $book_now_button;
                $product->show_on_home = $show_on_home;
                $product->show_documents = $show_documents;
                $product->slug = $slug;
                $product->created_by = $userId;
                $product->save();
        
                $productId = $product->id;
                $translations = $request->input('translation', []);
                $properties = $request->input('properties', []);
                $coverages = $request->input('coverages', []);
                
                if(!empty($merged_catalog_ids)){
                    foreach($merged_catalog_ids as $catalog_id){
                        $catalog = Catalog::find($catalog_id);
    
                        if ($catalog) {
                            // Decode existing car IDs as an array
                            $car_ids = json_decode($catalog->car_ids, true) ?? [];
                    
                            // Add new product ID if not already in the array
                            if (!in_array($productId, $car_ids)) {
                                $car_ids[] = $productId;
                            }
                            
                            // Convert all existing IDs to strings
                            $car_ids = array_map('strval', $car_ids);
                    
                            // Save the updated car IDs back to the catalog
                            $catalog->car_ids = json_encode($car_ids);
                            $catalog->save();
                        }
                    }
                }
                
                // Add product related coverages
                if (!empty($coverages) && is_array($coverages)) {
                    foreach($coverages as $type => $value){
                        $coverage_id = $value['coverage_id'];
                        $less_30_days_price = $value['less_30_days_price'];
                        $more_30_days_price = $value['more_30_days_price'];
                        
                        // Insert part of the product properties
                        ProductRelatedCoverage::create([
                            'coverage_id'   => $coverage_id,
                            'product_id'    => $productId,
                            'less_30_days_price' => $less_30_days_price,
                            'more_30_days_price' => $more_30_days_price
                        ]);
                    }
                }
                
                if(!empty($properties) && is_array($properties)){
                    foreach($properties as $type => $property){
                        foreach($property as $key => $value){
                            $property_id = $value['property_id'];
                            $property_value = $value['property_value'];
                            
                            if(is_array($property_value)){
                                $property_value = !empty($property_value) ? json_encode($property_value) : null;
                            }
                            
                            // Insert part of the product properties
                            ProductProperty::create([
                                'property_id'   => $property_id,
                                'product_id'    => $productId,
                                'property_type' => $type,
                                'property_values' => $property_value,
                                'language'      => $lang,
                            ]);
                        }    
                    }
                }
                
                $product_translation = new ProductTranslation();
                $product_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $product_translation->language = $lang;
                $product_translation->product_id = $productId;
                $product_translation->save();

                return response()->json([
                    'status' => 'true',
                    'message' => 'Product created successfully',
                    'data' => [
                        'product_id' => $productId,
                        ]
                    ], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data update part PUT/{id}/{lang} */
    public function updateProduct(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'slug' => 'required|string|unique:products,slug,' . $id . ',id',
                'catalog_id' => 'required|numeric',
                'vehicle_type' => 'required|string',
                'product_status' => 'required|numeric',
                'featured' => 'required|numeric',
                'promo_status' => 'required|numeric',
                'stock_status' => 'required|numeric',
                'book_now_button' => 'required|numeric',
                'year' => 'nullable|string',
                'model' => 'nullable|string',
                'translation' => 'array',
                'properties' => 'array'
            ];
    
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $user = Auth::user();
                $userId = $user->id;
                
                $product = Product::find($id);
                if (!$product) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Product not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $old_catalog_id = $product->catalog_id;
                $catalog_id = $request->input('catalog_id');
                $main_image = $request->input('main_image');
                $car_images = $request->input('car_images');
                $additional_catalog_ids = $request->input('additional_catalog_ids');
                $previous_catalog_ids = json_decode($product->additional_catalog_ids, true) ?? [];
                $car_locations = $request->input('car_locations');
                $product_status = $request->input('product_status');
                $pay_now_discount = $request->input('pay_now_discount');
                $featured = $request->input('featured');
                $promo_status = $request->input('promo_status');
                $stock_status = $request->input('stock_status');
                $daily_price = $request->input('daily_price');
                $old_daily_price = $request->input('old_daily_price');
                $weekly_price = $request->input('weekly_price');
                $old_weekly_price = $request->input('old_weekly_price');
                $monthly_price = $request->input('monthly_price');
                $old_monthly_price = $request->input('old_monthly_price');
                $flexible_cars_monthly_prices = $request->input('flexible_cars_monthly_prices');
                $personal_cars_monthly_prices = $request->input('personal_cars_monthly_prices');
                $monthly_installment_24_months = $request->input('monthly_installment_24_months');
                $monthly_installment_36_months = $request->input('monthly_installment_36_months');
                $installment_per_month = $request->input('installment_per_month');
                $installment_per_month_with_down = $request->input('installment_per_month_with_down');
                $installment_per_month_final_term = $request->input('installment_per_month_final_term');
                $down_payment = $request->input('down_payment');
                $security_deposit = $request->input('security_deposit');
                $security_deposit_waiver_daily = $request->input('security_deposit_waiver_daily');
                $security_deposit_waiver_monthly = $request->input('security_deposit_waiver_monthly');
                $vehicle_type = $request->input('vehicle_type');
                $specification_auto = $request->input('specification_auto');
                $year = $request->input('year');
                $model = $request->input('model');
                $book_now_button = $request->input('book_now_button');
                $show_on_home = $request->input('show_on_home');
                $show_documents = $request->input('show_documents');
                $slug = $request->input('slug');
                
                $filtered_catalog_ids = $filtered_locations = [];
                if(is_array($additional_catalog_ids) && $additional_catalog_ids != null){
                    $filtered_catalog_ids = array_filter($additional_catalog_ids);
                }
                
                if(is_array($car_locations) && $car_locations != null){
                    $filtered_locations = array_filter($car_locations);
                }
                
                // Handle main image uploads for car
                if (!empty($main_image)) {
                    $product->main_image = $main_image;
                }
                
                // Gallery images for car
                if (!empty($car_images)) {
                    $product->car_images = json_encode($car_images) ?? null;
                }
                
                $product->catalog_id = $catalog_id;
                $product->additional_catalog_ids = !empty($filtered_catalog_ids) ? json_encode($filtered_catalog_ids) : null;
                $product->car_locations = !empty($filtered_locations) ? json_encode($filtered_locations) : null;
                $product->product_status = $product_status;
                $product->pay_now_discount = $pay_now_discount;
                $product->featured = $featured;
                $product->promo_status = $promo_status;
                $product->stock_status = $stock_status;
                $product->daily_price = $daily_price;
                $product->old_daily_price = $old_daily_price;
                $product->weekly_price = $weekly_price;
                $product->old_weekly_price = $old_weekly_price;
                $product->monthly_price = $monthly_price;
                $product->old_monthly_price = $old_monthly_price;
                $product->flexible_cars_monthly_prices = !empty($flexible_cars_monthly_prices) ? json_encode($flexible_cars_monthly_prices) : null;
                $product->personal_cars_monthly_prices = !empty($personal_cars_monthly_prices) ? json_encode($personal_cars_monthly_prices) : null;
                $product->monthly_installment_24_months = $monthly_installment_24_months;
                $product->monthly_installment_36_months = $monthly_installment_36_months;
                $product->installment_per_month = $installment_per_month;
                $product->installment_per_month_with_down = $installment_per_month_with_down;
                $product->installment_per_month_final_term = $installment_per_month_final_term;
                $product->down_payment = $down_payment;
                $product->security_deposit = $security_deposit;
                $product->security_deposit_waiver_daily = $security_deposit_waiver_daily;
                $product->security_deposit_waiver_monthly = $security_deposit_waiver_monthly;
                $product->vehicle_type = $vehicle_type;
                $product->specification_auto = $specification_auto;
                $product->year = $year;
                $product->model = $model;
                $product->book_now_button = $book_now_button;
                $product->show_on_home = $show_on_home;
                $product->show_documents = $show_documents;
                $product->slug = $slug;
                $product->updated_by = $userId;
                $product->save();
    
                $translations = $request->input('translation', []);
                $properties = $request->input('properties', []);
                $coverages = $request->input('coverages', []);
                
                $productId = (string) $id;

                // Ensure $catalog_id is an array
                $merged_catalog_ids = array_map('strval', array_merge([$catalog_id], $filtered_catalog_ids));
                $previous_catalog_ids = array_map('strval', array_merge([$old_catalog_id], $previous_catalog_ids));
                
                // Merge previous and new catalog IDs to check all affected catalogs
                $all_catalog_ids = array_unique(array_merge($previous_catalog_ids, $merged_catalog_ids));
                
                foreach ($all_catalog_ids as $catalog_id) {
                    $catalog = Catalog::find($catalog_id);
                    if ($catalog) {
                        // Decode existing car IDs
                        $car_ids = json_decode($catalog->car_ids, true) ?? [];
                
                        // Ensure all IDs are strings
                        $car_ids = array_map('strval', $car_ids);
                
                        // If the catalog was removed, remove the product ID
                        if (!in_array($catalog_id, $merged_catalog_ids)) {
                            $car_ids = array_filter($car_ids, function ($car_id) use ($productId) {
                                return $car_id !== $productId;
                            });
                        } 
                        // If the catalog is new, add the product ID if not already present
                        elseif (!in_array($productId, $car_ids)) {
                            $car_ids[] = $productId;
                        }
                
                        // Save the updated car_ids back to the catalog
                        $catalog->car_ids = json_encode(array_values($car_ids)); // Re-index array
                        $catalog->save();
                    }
                }
                
                // Add product related coverages
                if (!empty($coverages) && is_array($coverages)) {
                    foreach($coverages as $type => $value){
                        $coverage_id = $value['coverage_id'];
                        $less_30_days_price = $value['less_30_days_price'];
                        $more_30_days_price = $value['more_30_days_price'];
                        
                        // Update part of the product related coverages
                        ProductRelatedCoverage::updateOrCreate(
                            [
                                'coverage_id'   => $coverage_id,
                                'product_id'    => $id
                            ],
                            [
                                'less_30_days_price' => $less_30_days_price,
                                'more_30_days_price' => $more_30_days_price
                            ]
                        );
                    }
                }
                
                if(!empty($properties) && is_array($properties)){
                    foreach($properties as $type => $property){
                        foreach($property as $key => $value){
                            $property_id = $value['property_id'];
                            $property_value = $value['property_value'];
                            
                            if(is_array($property_value)){
                                $property_value = !empty($property_value) ? json_encode($property_value) : null;
                            }
                            
                            // Update part of the product properties
                            ProductProperty::updateOrCreate(
                                [
                                    'property_id'   => $property_id,
                                    'product_id'    => $productId,
                                    'language'      => $lang, // Ensure updates are language-specific
                                ],
                                [
                                    'property_type'  => $type,
                                    'property_values' => $property_value,
                                ]
                            );
                        }    
                    }
                }
                
                // Update translations
                $product_translation = ProductTranslation::where('product_id', $productId)
                    ->where('language', $lang)
                    ->first();
    
                if (!$product_translation) {
                    $product_translation = new ProductTranslation();
                    $product_translation->product_id = $id;
                    $product_translation->language = $lang;
                }
    
                $product_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $product_translation->save();
                
                return response()->json(['status' => 'true', 'message' => 'Product updated successfully.'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data fetch part GET/{id}/{lang} */
    public function productSingleDetail($id, $lang)
    {
        try {
            $product = Product::find($id);

            if(!$product){
                return response()->json(['status' => 'false', 'message' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Fetch translation for the given language with optimized query
            $translation = ProductTranslation::where('product_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                ->first();
    
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
            
            $catalog_slug = $catalog_title = $parent_slug =  "";
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
                        }
                    }
                    
                    if(isset($catalogTranslation['catalog_title'])){
                        $catalog_title = $catalogTranslation['catalog_title'];
                    }
                    $catalog_slug = $catalog->slug;
                }
            }
            
            // Get all group properties with related product values 
            $fetchAllProperties = $this->groupProperties($id, $lang);
            
            // Get all product related coverages
            $productCoverages = $this->groupCoverages($id, $lang);
            
            $additional_catalogs = is_string($product->additional_catalog_ids)
                                    ? json_decode($product->additional_catalog_ids, true) ?? []
                                    : (is_array($product->additional_catalog_ids) ? $product->additional_catalog_ids : []);

            $additionalCatalogData = [];
            if(!empty($additional_catalogs) && $additional_catalogs != null){
                foreach($additional_catalogs as $key => $cat_id){
                    $additional_catalog = Catalog::find($cat_id);
    
                    if($additional_catalog){
                        $catalogQuery = CatalogTranslation::where('catalog_id', $cat_id)
                            ->whereIn('language', [$lang, 'en']) // Check both requested and default language
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang]) // Prioritize requested language first
                            ->first();
                        
                        $catalogTranslation = !empty($catalogQuery) ? json_decode($catalogQuery->field_values, true) : [];
                        $title = "";
                        if(isset($catalogTranslation['catalog_title'])){
                            $title = $catalogTranslation['catalog_title'];
                        }
                        
                        $additionalCatalogData[$key]['value'] = $cat_id;
                        $additionalCatalogData[$key]['label'] = $title;
                    }
                }
            }
            
            $flexibleMonthlyPrices = json_decode($product->flexible_cars_monthly_prices, true) ?? [];
            $roundedFlexiblePrices = array_map(function ($value) {
                return round((float) $value);
            }, $flexibleMonthlyPrices);
            
            $monthlyPrices = json_decode($product->personal_cars_monthly_prices, true) ?? [];
            $roundedPrices = array_map(function ($value) {
                return round((float) $value);
            }, $monthlyPrices);
            
            // Handle image URLs for primary fields
            $translatedData['catalog_id']       = (int) $product->catalog_id;
            $translatedData['parent_slug']      = $parent_slug;
            $translatedData['catalog_title']    = $catalog_title;
            $translatedData['catalog_slug']     = $catalog_slug;
            $translatedData['vehicle_type']     = $product->vehicle_type;
            $translatedData['car_locations'] = json_decode($product->car_locations, true);
            $translatedData['pay_now_discount'] = $product->pay_now_discount;
            $translatedData['product_status'] = (int) $product->product_status;
            $translatedData['featured'] = (int) $product->featured;
            $translatedData['promo_status'] = (int) $product->promo_status;
            $translatedData['stock_status'] = (int) $product->stock_status;
            $translatedData['show_documents'] = (int) $product->show_documents;
            $translatedData['book_now_button'] = (int) $product->book_now_button;
            $translatedData['vehicle_type']     = $product->vehicle_type;
            $translatedData['show_on_home'] = (int) $product->show_on_home;
            $translatedData['specification_auto'] = (int) $product->specification_auto;
            $translatedData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;
            $translatedData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
            $translatedData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
            $translatedData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
            $translatedData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
            $translatedData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
            $translatedData['flexible_cars_monthly_prices'] = $roundedFlexiblePrices;
            $translatedData['personal_cars_monthly_prices'] = $roundedPrices;
            $translatedData['monthly_installment_24_months'] = $product->monthly_installment_24_months;
            $translatedData['monthly_installment_36_months'] = $product->monthly_installment_36_months;
            $translatedData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
            $translatedData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
            $translatedData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
            $translatedData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
            $translatedData['security_deposit'] = $product->security_deposit;
            $translatedData['security_deposit_waiver_daily'] = $product->security_deposit_waiver_daily;
            $translatedData['security_deposit_waiver_monthly'] = $product->security_deposit_waiver_monthly;
            $translatedData['year'] = $product->year;
            $translatedData['model'] = $product->model;
            $translatedData['slug'] = $product->slug;
            $translatedData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
            $translatedData['groupedProperties'] = $fetchAllProperties;
            $translatedData['related_coverages'] = $productCoverages;
            $translatedData['additional_catalogs'] = $additionalCatalogData;
         
            $car_images = json_decode($product->car_images) ?? null;

            // Process car images
            if (!empty($car_images)) {
                foreach ($car_images as $key => $image_path) {
                    $translatedData['car_images'][$key]['image_path'] = $image_path ? $image_path : "";
                    $translatedData['car_images'][$key]['image_full_path'] = $image_path ? $this->getImageUrl($image_path) : null;
                }
            }else{
                $translatedData['car_images'] = [];
            }
            
            // Fetch coverages list record
            $productCoveragesController = new ProductCoveragesController();
            $coveragesDetails =  $productCoveragesController->coveragesListForProduct($lang);
            
            if($coveragesDetails->original['data']){    
                $translatedData['coverages_list'] = $coveragesDetails->original['data'];
            }else{
                $translatedData['coverages_list'] = [];
            }
            
            // Fetch product inner page content
            $webContentController = new WebContentController();
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
        
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $translatedData['locations'] = $productInnerData['locations'];
            }else{
                $translatedData['locations'] = "";
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
    
    /* Data fetch from slug for frontend side */
    public function productFetchDetail($lang, $child_slug)
    {
        try {
            $product = Product::where('product_status', '=', 1)
                        ->where('slug','=',$child_slug)
                        ->first();

            if(!$product){
                return response()->json(['status' => false, 'message' => 'Product not found22','data' => null], Response::HTTP_NOT_FOUND);
            }
            
            $id = $product->id;
            // Fetch translation for the given language with optimized query
            $translation = ProductTranslation::where('product_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                ->first();
    
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
            
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
            
            // Get all group properties with related product values 
            $fetchAllProperties = $this->groupProductProperties($id, $lang);

            $flexibleMonthlyPrices = json_decode($product->flexible_cars_monthly_prices, true) ?? [];
            $roundedFlexiblePrices = array_map(function ($value) {
                return round((float) $value);
            }, $flexibleMonthlyPrices);
            
            $monthlyPrices = json_decode($product->personal_cars_monthly_prices, true) ?? [];

            $roundedPrices = array_map(function ($value) {
                return round((float) $value);
            }, $monthlyPrices);
        
            // Handle image URLs for primary fields
            $translatedData['product_id']      = $id;
            $translatedData['parent_title'] = $catalog_title;
            $translatedData['parent_slug']      = $catalog_slug;
            $translatedData['catalog_title']    = $catalog_title;
            $translatedData['catalog_slug']     = $catalog_slug;
            $translatedData['vehicle_type']     = $product->vehicle_type;
            $translatedData['car_locations'] = json_decode($product->car_locations, true);
            $translatedData['pay_now_discount'] = $product->pay_now_discount;
            $translatedData['product_status'] = (int) $product->product_status;
            $translatedData['featured'] = (int) $product->featured;
            $translatedData['promo_status'] = (int) $product->promo_status;
            $translatedData['stock_status'] = (int) $product->stock_status;
            $translatedData['show_documents'] = (int) $product->show_documents;
            $translatedData['book_now_button'] = (int) $product->book_now_button;
            $translatedData['specification_auto'] = (int) $product->specification_auto;
            $translatedData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;;
            $translatedData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
            $translatedData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
            $translatedData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
            $translatedData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
            $translatedData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
            $translatedData['flexible_cars_monthly_prices'] = $roundedFlexiblePrices;
            $translatedData['personal_cars_monthly_prices'] = $roundedPrices;
            $translatedData['monthly_installment_24_months'] = $product->monthly_installment_24_months;
            $translatedData['monthly_installment_36_months'] = $product->monthly_installment_36_months;
            $translatedData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
            $translatedData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
            $translatedData['installment_per_month_final_term'] = is_numeric($product->installment_per_month_final_term) ? round($product->installment_per_month_final_term) : 0;
            $translatedData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
            $translatedData['security_deposit'] = $product->security_deposit;
            $translatedData['security_deposit_waiver_daily'] = $product->security_deposit_waiver_daily;
            $translatedData['security_deposit_waiver_monthly'] = $product->security_deposit_waiver_monthly;
            $translatedData['year'] = $product->year;
            $translatedData['model'] = $product->model;
            $translatedData['page_full_slug'] = $page_full_slug;
            $translatedData['slug'] = $product_slug;
            $translatedData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
            $translatedData['groupedProperties'] = $fetchAllProperties;
            
            $car_images = json_decode($product->car_images) ?? null;

            // Process car images
            if (!empty($car_images)) {
                foreach ($car_images as $image_path) {
                    $translatedData['car_images'][] = $image_path ? $this->getImageUrl($image_path) : null;
                }
            }else{
                $translatedData['car_images'] = [];
            }
            
            
            // Fetch product inner page content
            $webContentController = new WebContentController();
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
            
            $show_documents = $product->show_documents;
            $services = "";
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $translatedData['services'] = $productInnerData['services'];
                $translatedData['telephone_number'] = $productInnerData['telephone_number'];
                $translatedData['whatsapp'] = $productInnerData['whatsapp'];
                $translatedData['banner_image'] = $productInnerData['banner'];
                
                if($show_documents == 1){
                    $translatedData['document_requirements'] = $productInnerData['document_requirements'];    
                }
            }
            
            $coverages = ProductCoverage::where('coverage_status','=',1)
                            ->orderBy('created_at', 'ASC')
                            ->get();
                
            $default_coverages = [];
            if($coverages){
                // Get all coverages list
                foreach($coverages as $coverage){
                    
                    // Get coverage id                            
                    $coverage_id = $coverage['id'];
                    $field_required = (int) $coverage['field_required'];
                    $checked_by_default = (int) $coverage['checked_by_default'];
                    
                    if($field_required == 1 || $checked_by_default == 1){
                        $default_coverages[] = $coverage_id; 
                    }
                }
            }
            $translatedData['default_coverages'] = $default_coverages;
            
            $googleReviewController = new GoogleReviewController();
            $reviewsFetch =  $googleReviewController->getReview();

            if ($reviewsFetch->original['data']) {
                $translatedData['google_reviews'] = $reviewsFetch->original['data'];
            } else {
                $translatedData['google_reviews'] = [];
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Product detail fetched successfully.',
                'data' => $translatedData
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage(),
                'data' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Get all group properties function for admin product apis
    public function groupProperties($productId, $lang){
        
        // Fetch all properties related to the products
        $propertiesQuery = Property::where('property_status', 1)->get();
        
        // Group properties by "type" and include translations
        $groupedProperties = $propertiesQuery->groupBy('type')->sortBy(function ($value, $key) {
                $order = ['general_information' => 1, 'car_options' => 2, 'car_services' => 3];
                return $order[$key] ?? 99; // Default high value for unknown types
            })->map(function ($items) use ($productId, $lang) {
                return $items->map(function ($item) use ($productId, $lang) {
                    $propertyId = $item->id;
            
                    // Fetch translation for the given language with optimized query
                    $translation = PropertyTranslation::where('property_id', $propertyId)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();
            
                    $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                    
                    $properties_value = ProductProperty::where('property_id', $propertyId)
                                            ->where('product_id', $productId)
                                            ->whereIn('language', [$lang, 'en'])
                                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                            ->first();
                    
                    $product_property_values = "";
                    if($properties_value){
                        $product_property_values = $properties_value['property_values'];  
                        if(json_decode($product_property_values, true)){
                            $product_property_values = json_decode($product_property_values, true);
                        }
                    }                        
            
                    // Merge translation data with main data
                    return array_merge($translatedData, [
                        'property_id' => $propertyId,
                        'property_status' => (int) $item->property_status,
                        'property_field_type' => $item->property_field_type,
                        'product_property_values' => $product_property_values,
                        'property_image' => $item->property_image ? $this->getImageUrl($item->property_image) : null,
                    ]);
                });
            });
            
            return $groupedProperties;
    }
    
    // Get all group coverages function for admin product apis
    public function groupCoverages($productId, $lang){
        
        // Fetch all related to the products
        $relatedCoveragesQuery = ProductRelatedCoverage::where('product_id', '=', $productId)->get();
          
        $groupedCoverages = $relatedCoveragesQuery->map(function($coverage) use ($lang) {
            $id = $coverage->id;
            $coverage_id = $coverage->coverage_id;
            
            $translation = ProductCoverageTranslation::where('coverage_id', $coverage_id)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();  
            $coverage_title = "";            
            if(!empty($translation)){
                $translatedData = json_decode($translation->field_values, true);
                $coverage_title = $translatedData['title'];
            }            

            $related_coverages = [];
            
            // Handle image URLs for primary fields
            $related_coverages['id'] = $id;
            $related_coverages['coverage_id'] = (int) $coverage_id;
            $related_coverages['coverage_title'] = $coverage_title;
            $related_coverages['less_30_days_price'] = $coverage->less_30_days_price;
            $related_coverages['more_30_days_price'] = $coverage->more_30_days_price;
         
            return $related_coverages;
        });
            
        return $groupedCoverages;
    }
    
    // Get all group properties for frontend product api
    public function groupProductProperties($productId, $lang, $limit = 0){
        
        // Fetch all properties related to the products
        $propertiesQuery = Property::where('property_status', 1);
        
        if ($limit > 0) {
            $properties = $propertiesQuery->limit($limit)->get();
        } else {
            $properties = $propertiesQuery->get();
        }
        
        // Group properties by "type" and include translations
        $groupedProperties = $properties->groupBy('type')->sortBy(function ($value, $key) {
                $order = ['general_information' => 1, 'car_options' => 2, 'car_services' => 3];
                return $order[$key] ?? 99; // Default high value for unknown types
            })->map(function ($items) use ($productId, $lang) {
                return $items->map(function ($item) use ($productId, $lang) {
                    $propertyId = $item->id;
                    
                    // Fetch translation for the given language with optimized query
                    $translation = PropertyTranslation::where('property_id', $propertyId)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->first();
            
                    $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                    $property_title = $translatedData['property_title'];
                    $property_image = $item->property_image ? $this->getImageUrl($item->property_image) : null;
                    
                    $properties_value = ProductProperty::where('property_id', $propertyId)
                                            ->where('product_id', $productId)
                                            ->whereIn('language', [$lang, 'en'])
                                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                                            ->first();
                    
                    $product_property_values = "";
                    if($properties_value){
                        $product_property_values = $properties_value['property_values'];  
                        if(json_decode($product_property_values, true)){
                            $product_property_values = json_decode($product_property_values, true);
                        }
                    }                        
            
                    // Merge translation data with main data
                    return  [
                        'property_id' => $propertyId,
                        'property_title' => $property_title,
                        'product_property_values' => $product_property_values,
                        'property_image' => $property_image
                    ];
                });
            });
            
            return $groupedProperties;
    }
    
    /* Product data fetch part DELETE/{id} */
    public function deleteProduct($id)
    {
        try {
            
            $product = Product::find($id);
    
            if (!$product) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Decode images array if stored as JSON
            $catalog_id = $product->catalog_id;
            $car_images = $product->car_images;
            $additional_catalog_ids = $product->additional_catalog_ids;
            
            $previous_catalog_ids = [];
            if(is_array($additional_catalog_ids) && $additional_catalog_ids != null){
                $catalog_ids = json_decode($additional_catalog_ids, true);
                $previous_catalog_ids = array_filter($catalog_ids);
            }
            
            if(is_array($car_images) && $car_images != null){
                $carImages = json_decode($car_images, true);
                $filtered_carImages = array_filter($carImages);
                
                // Delete all images from storage if they exist
                foreach ($filtered_carImages as $imagePath) {
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }
            
            // Delete product main_image if it exists
            if ($product->main_image && Storage::disk('public')->exists($product->main_image)) {
                Storage::disk('public')->delete($product->main_image);
            }
            
            $all_catalog_ids = array_map('strval', array_merge([$catalog_id], $previous_catalog_ids));
            
            foreach ($all_catalog_ids as $catalog_id) {
                $catalog = Catalog::find($catalog_id);
                if ($catalog) {
                    // Decode existing car IDs
                    $car_ids = json_decode($catalog->car_ids, true) ?? [];
            
                    // Ensure all IDs are strings
                    $car_ids = array_map('strval', $car_ids);
            
                    // If the catalog was removed, remove the product ID
                    
                    $car_ids = array_filter($car_ids, function ($car_id) use ($id) {
                        return $car_id !== $id;
                    });
                    
                    // Save the updated car_ids back to the catalog
                    $catalog->car_ids = json_encode(array_values($car_ids)); // Re-index array
                    $catalog->save();
                }
            }
            
            // Delete the product record
            $product->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Product deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // List search function
    public function searchProductsList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $productsQuery = Product::query()
            ->join('product_translations', function ($join) use ($lang) {
                $join->on('products.id', '=', 'product_translations.product_id')
                    ->where('product_translations.language', '=', $lang);
            })
            ->select('products.*', 'product_translations.field_values');

            // Apply search filters for both slug and partner_title
            if (!empty($searchQuery)) {
                $productsQuery->where(function ($query) use ($searchQuery) {
                    $query->where('products.slug', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('products.vehicle_type', 'LIKE', "%{$searchQuery}%")
                          ->orWhere('products.year', 'LIKE', "%{$searchQuery}%")
                          ->orWhereRaw("LOWER(products.model) LIKE ?", ["%".strtolower($searchQuery)."%"])
                          ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(product_translations.field_values, '$.product_title'))) LIKE ?", ["%".strtolower($searchQuery)."%"])
                          ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(product_translations.field_values, '$.heading_one'))) LIKE ?", ["%".strtolower($searchQuery)."%"]);
                });
            }
    
            $productsQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $products = $productsQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $products->count(), // All items in one "page"
                    'total' => $products->count(),
                ];
            } else {
                // Paginate the remaining partners
                $products = $productsQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ];
            }
    
            if ($products->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No products found'], 200);
            }
    
            $products_translations = $products->map(function ($product) use ($lang) {
                $id = $product->id;
                
                // Fetch translation for the given language with optimized query
                $translation = ProductTranslation::where('product_id', $id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->first();
        
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                
                
                $catalog_slug = $catalog_title = $parent_slug =  "";
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
                            }
                        }
            
            
                        $catalog_title = $catalogTranslation['catalog_title'];
                        $catalog_slug = $catalog->slug;
                    }
                }

                $created_by = $product->created_by;
                $updated_by = $product->updated_by;
                $created_at = $product->created_at;
                $updated_at = $product->updated_at;
                

                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);
    
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['parent_slug'] = $parent_slug;
                $translatedData['catalog_title'] = $catalog_title;
                $translatedData['catalog_slug'] = $catalog_slug;
                $translatedData['featured'] = (int) $product->featured;
                $translatedData['promo_status'] = (int) $product->promo_status;
                $translatedData['product_status'] = (int) $product->product_status;
                $translatedData['stock_status'] = (int) $product->stock_status;
                $translatedData['show_documents'] = (int) $product->show_documents;
                $translatedData['book_now_button'] = (int) $product->book_now_button;
                $translatedData['pay_now_discount'] = $product->pay_now_discount;
                $translatedData['vehicle_type']     = $product->vehicle_type;
                $translatedData['show_on_home'] = (int) $product->show_on_home;
                $translatedData['specification_auto'] = (int) $product->specification_auto;
                $translatedData['daily_price'] = is_numeric($product->daily_price) ? round($product->daily_price) : 0;;
                $translatedData['old_daily_price'] = is_numeric($product->old_daily_price) ? round($product->old_daily_price) : 0;
                $translatedData['weekly_price'] = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
                $translatedData['old_weekly_price'] = is_numeric($product->old_weekly_price) ? round($product->old_weekly_price) : 0;
                $translatedData['monthly_price'] = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
                $translatedData['old_monthly_price'] = is_numeric($product->old_monthly_price) ? round($product->old_monthly_price) : 0;
                $translatedData['installment_per_month'] = is_numeric($product->installment_per_month) ? round($product->installment_per_month) : 0;
                $translatedData['installment_per_month_with_down'] = is_numeric($product->installment_per_month_with_down) ? round($product->installment_per_month_with_down) : 0;
                $translatedData['down_payment'] = is_numeric($product->down_payment) ? round($product->down_payment) : 0;
                $translatedData['year'] = $product->year;
                $translatedData['model'] = $product->model;
                $translatedData['slug'] = $product->slug;
                $translatedData['main_image'] = $product->main_image ? $this->getImageUrl($product->main_image) : null;
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $products_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Filter function for product section
    public function applyProductFilter(Request $request, $slug, $lang, $per_page = 0, $carIds = [] )
    {
        try {
            $dataArray = [];
            $finalArray = [];
        
            $catalog_slug = $catalog_title = $parent_slug = $page_full_slug = "";
            $productQuery = Product::query();
            $brandsIds = $request->input('brands') ?? [];
            $productIdsFromBrands = [];

            // Step 1: Handle filtering by selected brands.
            // Brands are catalogs, so we need to find the products linked to these catalogs.
            if ($request->filled('brands') && !empty($brandsIds)) {
                // Find catalogs whose IDs match the selected brand IDs.
                $catalogs = \DB::table('catalogs')
                    ->select('car_ids')
                    ->whereIn('id', $brandsIds)
                    ->get();

                // Collect all the product IDs from the 'car_ids' column of these catalogs.
                foreach ($catalogs as $catalog) {
                    if (!empty($catalog->car_ids)) {
                        $productIdsFromBrands = array_merge($productIdsFromBrands, json_decode($catalog->car_ids, true));
                    }
                }

                // Remove any duplicate IDs to optimize the query.
                $productIdsFromBrands = array_unique($productIdsFromBrands);
            }

            // Step 2: Apply the filters to the main product query.
            // Filter by carIds. This assumes $carIds is already set from another part of your code.
            if (!empty($carIds)) {
                $productQuery->whereIn('id', $carIds);
            }

            // Filter by the products we found in the brand catalogs.
            if (!empty($productIdsFromBrands)) {
                // We use a nested query here to ensure the logic is correctly combined.
                $productQuery->where(function ($q) use ($productIdsFromBrands) {
                    $q->whereIn('products.id', $productIdsFromBrands);
                });
            }

            // // Filtering options
            if (!empty($request->input('car_types')) && $request->filled('car_types') && is_array($request->input('car_types'))) {
                $types = $request->input('car_types');
                $productQuery->whereIn('vehicle_type', $types);
            }
            
            if (!empty($request->input('specs')) && $request->filled('specs') && is_array($request->input('specs'))) {
                $productQuery->whereIn('specification_auto', $request->input('specs'));
            }
            
            if ($request->filled('featured')) {
                $featured = $request->input('featured');
                $productQuery->where('featured', $featured);
            }

            if ($request->filled('year')) {
                $year = $request->input('year');
                $productQuery->where('year', $year);
            }
    
             if ($request->filled('availability')) {
                $productQuery->where('stock_status', $request->input('availability'));
            }
            
            if ($request->filled('min') && $request->filled('max')
                    && $slug != 'flexible-rentals' && $slug != 'personal-lease')
            {
                $min = (int) $request->input('min', 0);
                $max = (int) $request->input('max', 9999999);
                
                if ($slug === 'lease-to-own-with-down-payment') {
                    $priceColumn = "installment_per_month_with_down";
                } elseif ($slug === 'lease-to-own-without-down-payment') {
                    $priceColumn = "installment_per_month";
                } elseif ($slug === 'lease-to-own-final-term-payment') {
                    $priceColumn = "installment_per_month_final_term";
                }
                else{
                    $priceColumn = $request->input('price_category') . "_price";
                }
            
                $productQuery->where(function ($q) use ($priceColumn, $min, $max) {
                    $q->whereRaw("CAST(COALESCE($priceColumn, 0) AS UNSIGNED) BETWEEN ? AND ?", [$min, $max]);
                });
            }

            
            // Sorting by price
            if ($request->filled('price_type')) {
                $priceOrder = $request->input('price_type');
                
                $orderDirection = ($priceOrder === 'high-to-low') ? 'DESC' : 'ASC';
                if ($slug === 'flexible-rentals') {
                    $priceColumn = 'flexible_cars_monthly_prices';
                    
                    // This query extracts the first key from the JSON, then gets its value.
                    $productQuery->orderByRaw(
                        'CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(products.'.$priceColumn.', \'"\', 4), \'"\', -1), \'"\', -1) AS DECIMAL(10, 2)) ' . $orderDirection
                    );
                } elseif ($slug === 'personal-lease') {
                    $priceColumn = 'personal_cars_monthly_prices';
                    
                    // This query extracts the first key from the JSON, then gets its value.
                    $productQuery->orderByRaw(
                        'CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(products.'.$priceColumn.', \'"\', 4), \'"\', -1), \'"\', -1) AS DECIMAL(10, 2)) ' . $orderDirection
                    );
                } elseif ($slug === 'lease-to-own-with-down-payment') {
                    $priceColumn = "installment_per_month_with_down";
                    $productQuery->orderBy('products.'.$priceColumn, $orderDirection);
                } elseif ($slug === 'lease-to-own-without-down-payment') {
                    $priceColumn = "installment_per_month";
                    $productQuery->orderBy('products.'.$priceColumn, $orderDirection);
                }elseif ($slug === 'lease-to-own-final-term-payment') {
                    $priceColumn = "installment_per_month_final_term";
                    $productQuery->orderBy('products.'.$priceColumn, $orderDirection);
                }else{
                    $priceColumn = $request->input('price_category') ? $request->input('price_category') . "_price" : "daily_price";
                    $productQuery->orderBy('products.'.$priceColumn, $orderDirection);
                }
            }
            
            // Select product fields
            $productQuery->where('product_status', 1)
                        ->orderBy('stock_status', 'DESC');
            
            $perPage = $per_page;
            
            // Check if full list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all products excluding the recent one
                $filteredProds = $productQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $filteredProds->count(), // All items in one "page"
                    'total' => $filteredProds->count(),
                ];
            } else {
                // Paginate the remaining record
                $filteredProds = $productQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $filteredProds->currentPage(),
                    'last_page' => $filteredProds->lastPage(),
                    'per_page' => $filteredProds->perPage(),
                    'total' => $filteredProds->total(),
                ];
            }
            
            // Fetch product inner page content
            $webContentController = new WebContentController();
            $productInnerDetails = $webContentController->getWebProductInnerContent($lang);
    
            $dataArray['services'] = $productInnerDetails->original['data']['services'] ?? "";
            $dataArray['telephone_number'] = $productInnerDetails->original['data']['telephone_number'] ?? "";
            $dataArray['whatsapp'] = $productInnerDetails->original['data']['whatsapp'] ?? "";
    
            foreach ($filteredProds as $dt) {
                $id = $dt['id'];
                $page_full_slug = $catalog_slug . '/' . $dt['slug'] . '/';
                
                $productTranslation = ProductTranslation::where('product_id', $id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
                // Decode JSON translations
                $productData = isset($productTranslation[$lang]) ? json_decode($productTranslation[$lang]->field_values, true) : json_decode($productTranslation['en']->field_values, true);
                
                $product_title = "";
                if($productData){
                    $product_title = $productData['product_title'];
                }
                
                // Get all group properties with related product values 
                $fetchAllProperties = $this->groupProductProperties($id, $lang, 4);
                
                $specification = "";
                $specification_auto = $dt['specification_auto'];
                if($specification_auto == 1){
                    $specification = 'Full Option';
                } elseif($specification_auto == 2) {
                    $specification = 'Medium Option';
                } elseif($specification_auto == 3) {
                    $specification = 'Basic Option';
                }
                
                $flexibleMonthlyPrices = json_decode($dt['flexible_cars_monthly_prices'], true) ?? [];
                $roundedFlexiblePrices = array_map(function ($value) {
                    return round((float) $value);
                }, $flexibleMonthlyPrices);
                
                $monthlyPrices = json_decode($dt['personal_cars_monthly_prices'], true) ?? [];
    
                $roundedPrices = array_map(function ($value) {
                    return round((float) $value);
                }, $monthlyPrices);
                
                $dataArray = array_merge($dataArray,[
                    'id' => $id,
                    'product_title' => $product_title,
                    'catalog_title' => $dt['catalog_title'],
                    'product_status' => $dt['product_status'],
                    'vehicle_type' => $dt['vehicle_type'],
                    'featured' => $dt['featured'],
                    'specification' => $specification,
                    'promo_status' => $dt['promo_status'],
                    'stock_status' => $dt['stock_status'],
                    'show_documents' => $dt['show_documents'],
                    'book_now_button' => $dt['book_now_button'],
                    'pay_now_discount' => $dt['pay_now_discount'],
                    'daily_price' => is_numeric($dt['daily_price']) ? round($dt['daily_price']) : 0,
                    'old_daily_price' => is_numeric($dt['old_daily_price']) ? round($dt['old_daily_price']) : 0,
                    'weekly_price' => is_numeric($dt['weekly_price']) ? round($dt['weekly_price']) : 0,
                    'old_weekly_price' => is_numeric($dt['old_weekly_price']) ? round($dt['old_weekly_price']) : 0,
                    'monthly_price' => is_numeric($dt['monthly_price']) ? round($dt['monthly_price']) : 0,
                    'old_monthly_price' => is_numeric($dt['old_monthly_price']) ? round($dt['old_monthly_price']) : 0,
                    'flexible_cars_monthly_prices' => $roundedFlexiblePrices,
                    'personal_cars_monthly_prices' => $roundedPrices,
                    'installment_per_month' => is_numeric($dt['installment_per_month']) ? round($dt['installment_per_month']) : 0,
                    'installment_per_month_with_down' => is_numeric($dt['installment_per_month_with_down']) ? round($dt['installment_per_month_with_down']) : 0,
                    'installment_per_month_final_term' => is_numeric($dt['installment_per_month_final_term']) ? round($dt['installment_per_month_final_term']) : 0,
                    'down_payment' => is_numeric($dt['down_payment']) ? round($dt['down_payment']) : 0,
                    'year' => $dt['year'],
                    'model' => $dt['model'],
                    'slug' => $dt['slug'],
                    'main_image' => $dt['main_image'] ? $this->getImageUrl($dt['main_image']) : null,
                    'groupedProperties' => $fetchAllProperties
                ]);
                
                $finalArray[] = $dataArray;
            }
    
            return response()->json([
                'status' => 'true', 
                'data' => $finalArray,
                'pagination' => $pagination
                ]);
    
        } catch (\Exception $ex) {
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function getOldSocialImagePath($lang, $partnerId, $index, $section)
    {
        // Retrieve the web content translation entry for the current language and web content ID
        $partnerTranslation = PartnerTranslation::where('language', $lang)
            ->where('partner_id', $partnerId)
            ->first();
    
        // Check if the translation exists
        if (!$partnerTranslation) {
            return null; // or handle the case where the translation is not found
        }
    
        // Decode the JSON stored in the 'translated_value' field
        $oldTranslation = json_decode($partnerTranslation->field_values, true);
    
        // Check if the section exists and return the appropriate path
        if (isset($oldTranslation[$section])) {
            // Handle sec_six and similar sections where the image is nested in an array of objects
            if (isset($oldTranslation[$section][$index])) {
                return $oldTranslation[$section][$index]['image'] ?? null;
            }
        }
    
        return null;
    }
    
    /* Product booking form calculator */
    public function productsFormCalculator(Request $request, $car_id, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'date_from' => 'required|date_format:Y-m-d',
                "date_to" => 'required|date_format:Y-m-d',
                'pickup_time' => 'required|date_format:H:i',
                'dropoff_time' => 'required|date_format:H:i',
                "promo_code" => 'nullable|string',
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
                $pickup_time = $request->pickup_time;
                $dropoff_time = $request->dropoff_time;
                $promo_code = $request->promo_code;
                $promo_type = $request->promo_type;
                $percentage = $request->percentage;
                $page_type = $request->page_type;
                $button_type = $request->button_type;
                $car_monthly_price = $request->car_monthly_price;
                
                $daysCount = $total = 0;
                if (!empty($date_from) && !empty($date_to) && !empty($pickup_time) && !empty($dropoff_time)) {

                    $fromDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $date_from . ' ' . $pickup_time
                    );

                    $toDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $date_to . ' ' . $dropoff_time
                    );

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
                
                $product = Product::find($car_id);
    
                if($product){
                    
                    // Handle image URLs for primary fields
                    $daily_price = is_numeric($product->daily_price) ? round($product->daily_price) : 0;
                    $weekly_price = is_numeric($product->weekly_price) ? round($product->weekly_price) : 0;
                    $monthly_price = is_numeric($product->monthly_price) ? round($product->monthly_price) : 0;
                    $pay_now_percentage = $product->pay_now_discount ?? 0;
                    
                    $price = 0;
                    
                    if(!empty($page_type) && ($page_type == "flexible" || $page_type == "personal_lease")){
                        $price += $car_monthly_price;
                    } elseif ($daysCount <= 6) {
                        // Daily price
                        $price = $daysCount * $daily_price;
                    } elseif ($daysCount >= 7 && $daysCount < 30) {
                        // Weekly price
                        $weeks = $daysCount / 7;
                        $price = $weeks * $weekly_price;
                    } elseif ($daysCount >= 30) {
                        // Monthly price
                        $months = $daysCount / 30;
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
                    
                    $before_vat = ($percentage / 100) * $price;
                    $before_discount_price = $price + $before_vat;
                    
                    $status = true;
                    $message =  $expires_at = $promo_title_value = $pay_now_title = $promo_code_value = "";
                    $discount_amount = $promo_discount_amount = $promo_vat_amount = $promo_total_amount = $pay_now_unitprice =
                    $pay_now_discount = $pay_now_vat_amount = $paynow_total_amount = $total_discount = $total_discount_vat =
                    $total_discount_incl_vat = 0;
                    if(!empty($promo_code)){
                        $promoCode = PromoCode::where('code','=',$promo_code)
                                        ->where('code_status','=',1)
                                        ->first();
                        if($promoCode && $price != 0){
                            $code_type = $promoCode->code_type;
                            $code_value = $promoCode->code_value;
                            $target_type = $promoCode->target_type;
                            $expires_at = $promoCode->expires_at;
                            
                            if($target_type != $promo_type){
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
                                $promo_code_value = $promo_code;
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
                                
                                $total_discount += $discount_amount;
                                $total_discount_vat += $promo_vat_amount;
                                $total_discount_incl_vat += $promo_total_amount;
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
                    }
                    
                    if($button_type == 'pay_now' && !empty($pay_now_percentage) && $pay_now_percentage != 0){
                        $pay_now_title = $pay_now_percentage.'%';
                        $pay_now_unitprice = ($promo_discount_amount > 0 ) ? $promo_discount_amount : $price;
                        $pay_now_discount = ($pay_now_unitprice * $pay_now_percentage) / 100;
                        $pay_now_vat_amount = ($percentage / 100) * $pay_now_discount;
                        $paynow_total_amount = $pay_now_discount + $pay_now_vat_amount;
                        $price -= $pay_now_discount;
                        
                        $total_discount += $pay_now_discount;
                        $total_discount_vat += $pay_now_vat_amount;
                        $total_discount_incl_vat += $paynow_total_amount;
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
                        'pay_now_title' => $pay_now_title,
                        'pay_now_unitprice' => number_format($pay_now_unitprice, 2, '.', ''),
                        'pay_now_discount' => number_format($pay_now_discount, 2, '.', ''),
                        'pay_now_vat_amount' => number_format($pay_now_vat_amount, 2, '.', ''),
                        'pay_now_total_amount' => number_format($paynow_total_amount, 2, '.', ''),
                        'total_discount' => number_format($total_discount, 2, '.', ''),
                        'total_discount_vat' => number_format($total_discount_vat, 2, '.', ''),
                        'total_discount_incl_vat' => number_format($total_discount_incl_vat, 2, '.', ''),
                        'total' => number_format($total_price, 2, '.', '')
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