<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\ProductsController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Catalog;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use App\Models\ProductTranslation;
use App\Models\CatalogTranslation;
use App\Models\WebContent;
use App\Models\WebContentTranslation;
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

class CatalogController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Catalogs View', ['only' => ['catalogsList','catalogSingleDetail','searchCatalogsList']]);
        $this->middleware('permission:Catalogs Add', ['only' => ['storeCatalog']]);
        $this->middleware('permission:Catalogs Edit', ['only' => ['updateCatalog']]);
        $this->middleware('permission:Catalogs Delete', ['only' => ['deleteCatalog']]);

        // CarWithDriver 
        $this->middleware('permission:CarWithDriver View', ['only' => ['catalogsList','catalogSingleDetail']]);
        $this->middleware('permission:CarWithDriver Edit', ['only' => ['updateCatalog']]);
    }
    
    // Fetch all record list
    public function catalogsList($lang, $per_page=6, $type = "")
    {
        try {
            $catalogQuery = Catalog::orderBy('created_at', 'DESC');

            if(!empty($type) && $type != null){
                $catalogQuery->where('type', $type);
            }else{
                $catalogQuery->where('type', '!=','car_with_driver');
            }

            $perPage = request()->input('per_page', $per_page);
            $catalogs = $catalogQuery->paginate($perPage);
            
            if($catalogs->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Data not found'], 200);
            }
            
            $catalogs_translations = $catalogs->map(function($catalog) use ($lang) {
                $id = $catalog->id;
                $created_by = $catalog->created_by;
                $updated_by = $catalog->updated_by;
                $created_at = $catalog->created_at;
                $updated_at = $catalog->updated_at;
                
                $translation = CatalogTranslation::where('catalog_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = CatalogTranslation::where('catalog_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                // Handle car_ids count
                $carIds = json_decode($catalog->car_ids, true); // Decode JSON
                $carCount = is_array($carIds) ? count($carIds) : 0; // Count if valid array

                $parent_slug = $parent_title =  "";
                if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                    $parent_id = $catalog->parent_id;
                    $parent_catalog = Catalog::find($parent_id);
    
                    if($parent_catalog){
                        $parentQuery = CatalogTranslation::where('catalog_id', $parent_id)
                            ->whereIn('language', [$lang, 'en']) // Check both requested and default language
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang]) // Prioritize requested language first
                            ->first();
                        
                        $parentTranslation = !empty($parentQuery) ? json_decode($parentQuery->field_values, true) : [];
                        $parent_title = $parentTranslation['catalog_title'];
                        $parent_slug = $parent_catalog->slug;
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
                $translatedData['parent_id'] = (int) $catalog->parent_id;
                $translatedData['parent_title'] = $parent_title;
                $translatedData['parent_slug'] = $parent_slug;
                $translatedData['catalog_status'] = (int) $catalog->catalog_status;
                $translatedData['slug'] = $catalog->slug;
                $translatedData['type'] = $catalog->type;
                $translatedData['new_style_page_type'] = $catalog->new_style_page_type;
                $translatedData['banner_image'] = $catalog->banner_image ? $this->getImageUrl($catalog->banner_image) : null;
                $translatedData['brand_logo'] = $catalog->brand_logo ? $this->getImageUrl($catalog->brand_logo) : null;
                $translatedData['cars_count'] = $carCount;
                
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $catalogs_translations,
                'pagination' => [
                        'current_page' => $catalogs->currentPage(),
                        'last_page' => $catalogs->lastPage(),
                        'per_page' => $catalogs->perPage(),
                        'total' => $catalogs->total(),
                    ]
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Fetch all menu list
    public function catalogsMenuList($lang, $menu_type, $carIds = "", $is_mobile = 0)
    {
        try {
            
            // Step 1: Get all Brands rows
            $catalogQuery = Catalog::where('type', '=', $menu_type)
                ->where('catalog_status', '=', 1)
                ->orderBy('created_at', 'ASC');

            $catalogs = $catalogQuery->get();

            if ($catalogs->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Data not found',
                    'data' => []
                ], 200);
            }

            $catalogs_translations = [];
            if(!empty($carIds)){
                
                if (!is_array($carIds)) {
                    $carIds = array_filter(explode(',', $carIds));
                }
                
                // Step 2: Get related catalog IDs from products table
                $catalogIdsFromProducts = DB::table('products')
                    ->whereIn('id', $carIds)
                    ->select('catalog_id', 'additional_catalog_ids')
                    ->get()
                    ->flatMap(function ($product) {
                        $ids = [];
    
                        if (!empty($product->catalog_id)) {
                            $ids[] = $product->catalog_id;
                        }
    
                        if (is_array($product->additional_catalog_ids)) {
                            $ids = array_merge($ids, $product->additional_catalog_ids);
                        } elseif (is_string($product->additional_catalog_ids) && !empty($product->additional_catalog_ids)) {
                            $decoded = json_decode($product->additional_catalog_ids, true);
                            if (is_array($decoded)) {
                                $ids = array_merge($ids, $decoded);
                            }
                        }
    
                        return $ids;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
    
                
                // Step 3: Filter $catalogs based on IDs found in products table
                $catalogs = $catalogs->whereIn('id', $catalogIdsFromProducts)->values();
                
            }    
            
            if ((int) $is_mobile === 1) {
                $catalogs = $catalogs->filter(function ($catalog) {
                    if (empty($catalog->car_ids) || $catalog->car_ids === '[]') {
                        return false;
                    }

                    $decodedCarIds = is_array($catalog->car_ids)
                        ? $catalog->car_ids
                        : json_decode($catalog->car_ids, true);

                    if (!is_array($decodedCarIds) || empty(array_filter($decodedCarIds))) {
                        return false;
                    }

                    $decodedCarIds = array_values(array_filter($decodedCarIds));

                    // Check product table: at least one valid product must exist
                    $hasValidProduct = Product::whereIn('id', $decodedCarIds)
                        ->where('stock_status', 1)
                        ->where('product_status', 1)
                        ->exists();

                    return $hasValidProduct;
                })->values();
            }

            if ($catalogs->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Data not found',
                    'data' => []
                ], 200);
            }
            
            $catalogs_translations = $catalogs->map(function ($catalog) use ($lang) {
                $id = $catalog->id;
    
                $translations = CatalogTranslation::where('catalog_id', $id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, ?)", [$lang, 'en'])
                    ->get()
                    ->keyBy('language');
    
                $translatedData = [];
    
                if (isset($translations[$lang])) {
                    $translatedData = json_decode($translations[$lang]->field_values, true) ?? [];
                } elseif (isset($translations['en'])) {
                    $translatedData = json_decode($translations['en']->field_values, true) ?? [];
                }
    
                return [
                    'id' => $id,
                    'slug' => $catalog->slug,
                    'catalog_title' => $translatedData['catalog_title'] ?? '',
                    'type' => $catalog->type,
                    'new_style_page_type' => $catalog->new_style_page_type,
                    'brand_logo' => !empty($catalog->brand_logo) && $catalog->brand_logo !== 'undefined'
                        ? $this->getImageUrl($catalog->brand_logo)
                        : null,
                ];
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $catalogs_translations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Fetch all catalogs dropdown list
    public function catalogsDropdownList($lang)
    {
        try {
            // Fetch all catalogs with translations
            $catalogs = Catalog::leftJoin('catalog_translations', 'catalogs.id', '=', 'catalog_translations.catalog_id')
                ->select('catalogs.*', 'catalog_translations.field_values')
                ->where('catalog_translations.language', $lang)
                ->get();
    
            if ($catalogs->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Data not found'], 200);
            }
    
            // Process data: JSON decode `field_values`
            $items = [];
            $childrenMap = [];
    
            foreach ($catalogs as $catalog) {
                $fields = json_decode($catalog->field_values, true);
                $items[$catalog->id] = [
                    'id' => (int) $catalog->id,
                    'parent_id' => $catalog->parent_id ? (int) $catalog->parent_id : null,
                    'slug' => $catalog->slug,
                    'catalog_title' => $fields['catalog_title'] ?? ''
                ];
                if ($catalog->parent_id !== null) {
                    $childrenMap[$catalog->parent_id][] = $catalog->id;
                }
            }
            
            // Build hierarchical tree
            $catalogHierarchy = $this->buildTree($items, $childrenMap);
    
            return response()->json([
                'status' => 'true',
                'data' => $catalogHierarchy
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Builds a tree ensuring categories with children appear first.
     */
    public function buildTree($items, $childrenMap, $parentId = null, $parent_slug = "")
    {
        $tree = [];
    
        // Get all parent with children catalogs
        foreach ($items as $id => $item) {
            if ($item['parent_id'] == $parentId && isset($childrenMap[$id])) {
                $parent_slug = $item['slug'];
                $item['children'] = $this->buildTree($items, $childrenMap, $id, $parent_slug);
                
                // Add parent slug in the children object
                foreach ($item['children'] as &$child) {
                    $child['parent_slug'] = $parent_slug;
                }
                
                $tree[] = $item;
                unset($items[$id]); // Processed, remove from list
            }
        }
    
        // Get all without children catalogs
        foreach ($items as $id => $item) {
            if ($item['parent_id'] === $parentId) {
                $item['children'] = $this->buildTree($items, $childrenMap, $id, "");
    
                // Empty children ko remove karna
                if (empty($item['children'])) {
                    unset($item['children']);
                }
    
                $tree[] = $item;
                unset($items[$id]);
            }
        }
    
        return $tree;
    }

    /* Data insertion part POST/{lang} */
    public function storeCatalog(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Define validation rules
            $rules = [
                'slug'  => 'required|string|unique:catalogs,slug',
                "type" => 'required|string',
                "catalog_status" => 'required|numeric',
                "translation" => 'array',
                "car_ids" => 'nullable|array'
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

                $catalog = new Catalog();
                
                $type = $request->input('type');
                $new_style_page_type = $request->input('new_style_page_type');
                $parent_id = $request->input('parent_id');
                $catalog_status = $request->input('catalog_status');
                $sec_one_slider_status = $request->input('sec_one_slider_status');
                $sec_two_slider_status = $request->input('sec_two_slider_status');
                $sec_three_slider_status = $request->input('sec_three_slider_status');
                $slug = $request->input('slug');
                $banner_images = $request->input('banner_image');
                $brand_logo = $request->input('brand_logo');
                
                $filtered_cars_ids = [];
                if ($request->has('car_ids')) {
                    $car_ids = $request->input('car_ids', []); // Default to an empty array if null
                    if (is_array($car_ids)) {
                        $filtered_cars_ids = array_filter($car_ids); // Remove empty values
                    }
                }
                
                // Handle banner_image uploads for primary fields
                if (!empty($banner_images)) {
                    $catalog->banner_image = $banner_images;
                }
                
                if (!empty($brand_logo)) {
                    $catalog->brand_logo = $brand_logo;
                }
                
                $catalog->type = $type;
                $catalog->new_style_page_type = $new_style_page_type;
                $catalog->parent_id = $parent_id;
                $catalog->sec_one_slider_status = $sec_one_slider_status;
                $catalog->sec_two_slider_status = $sec_two_slider_status;
                $catalog->sec_three_slider_status = $sec_three_slider_status;
                $catalog->catalog_status = $catalog_status;
                $catalog->car_ids = !empty($filtered_cars_ids) ? json_encode($filtered_cars_ids) : null;
                $catalog->slug = $slug;
                $catalog->created_by = $userId;
                $catalog->save();
        
                $catalogId = $catalog->id;
                $translations = $request->input('translation', []);
                
                if(!empty($filtered_cars_ids)){
                    foreach($filtered_cars_ids as $car_id){
                        $carData = Product::find($car_id);
    
                        if ($carData) {
                            // Decode existing car IDs as an array
                            $additional_catalog_ids = json_decode($carData->additional_catalog_ids, true) ?? [];
                    
                            // Add new product ID if not already in the array
                            if (!in_array($catalogId, $additional_catalog_ids)) {
                                $additional_catalog_ids[] = $catalogId;
                            }
                            
                            // Convert all existing IDs to strings
                            $additional_catalog_ids = array_map('strval', $additional_catalog_ids);
                    
                            // Save the updated car IDs back to the catalog
                            $carData->additional_catalog_ids = json_encode($additional_catalog_ids);
                            $carData->save();
                        }
                    }
                }
                
                
                $catalog_translation = new CatalogTranslation();
                $catalog_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $catalog_translation->language = $lang;
                $catalog_translation->catalog_id = $catalogId;
                $catalog_translation->save();
                
                DB::commit(); // Commit transaction

                return response()->json([
                    'status' => 'true',
                    'message' => 'Catalog created successfully.',
                    'data' => [
                        'catalog_id' => $catalogId,
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
    public function updateCatalog(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'slug' => 'required|string|unique:catalogs,slug,' . $id . ',id',
                "catalog_status" => 'required|numeric',
                "translation" => 'array',
                "car_ids" => 'nullable|array'
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
    
                $catalog = Catalog::find($id);
                if (!$catalog) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Catalog not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $type = $request->input('type');
                $new_style_page_type = $request->input('new_style_page_type');
                $parent_id = $request->input('parent_id');
                $sec_one_slider_status = $request->input('sec_one_slider_status');
                $sec_two_slider_status = $request->input('sec_two_slider_status');
                $sec_three_slider_status = $request->input('sec_three_slider_status');
                $catalog_status = $request->input('catalog_status');
                $previous_car_ids = json_decode($catalog->car_ids, true) ?? [];
                $slug = $request->input('slug');
                $banner_images = $request->input('banner_image');
                $brand_logo = $request->input('brand_logo');
                
                $filtered_cars_ids = [];
                if ($request->has('car_ids')) {
                    $car_ids = $request->input('car_ids', []); // Default to an empty array if null
                    if (is_array($car_ids)) {
                        $filtered_cars_ids = array_filter($car_ids); // Remove empty values
                    }
                }
                
                // Handle banner image uploads for primary fields
                if (!empty($banner_images)) {
                    // Delete the old image if it exists
                    if ($catalog->banner_image && Storage::disk('public')->exists($catalog->banner_image)) {
                        Storage::disk('public')->delete($catalog->banner_image);
                    }
                    
                    $catalog->banner_image = $banner_images;
                }
                
                if (!empty($brand_logo)) {
                    // Delete the old image if it exists
                    if ($catalog->brand_logo && Storage::disk('public')->exists($catalog->brand_logo)) {
                        Storage::disk('public')->delete($catalog->brand_logo);
                    }
                    
                    $catalog->brand_logo = $brand_logo;
                }
                
                $catalog->type = $type;
                $catalog->new_style_page_type = $new_style_page_type;
                $catalog->parent_id = $parent_id;
                $catalog->sec_one_slider_status = $sec_one_slider_status;
                $catalog->sec_two_slider_status = $sec_two_slider_status;
                $catalog->sec_three_slider_status = $sec_three_slider_status;
                $catalog->catalog_status = $catalog_status;
                $catalog->car_ids = !empty($filtered_cars_ids) ? json_encode($filtered_cars_ids) : null;
                $catalog->slug = $slug;
                $catalog->updated_by = $userId;
                $catalog->save();
                
                $catalogId = $catalog->id;
                $translation = $request->input('translation', []);
                
                // Merge previous and new catalog IDs to check all affected catalogs
                $all_cars_ids = array_unique(array_merge($previous_car_ids, $filtered_cars_ids));
                
                if(!empty($all_cars_ids)){
                    foreach($all_cars_ids as $car_id){
                        $carData = Product::find($car_id);
    
                        if ($carData) {
                            // Decode existing car IDs as an array
                            $additional_catalog_ids = json_decode($carData->additional_catalog_ids, true) ?? [];
                            
                            // If the catalog was removed, remove the product ID
                            if (!in_array($car_id, $filtered_cars_ids)) {
                                $additional_catalog_ids = array_filter($additional_catalog_ids, function ($catalog_id) use ($catalogId) {
                                    return $catalog_id != $catalogId;
                                });
                            } elseif (!in_array($catalogId, $additional_catalog_ids)) {
                                $additional_catalog_ids[] = $catalogId;
                            }
                            
                            // Convert all existing IDs to strings
                            $additional_catalog_ids = array_map('strval', $additional_catalog_ids);
                            
                            // Save the updated car IDs back to the catalog
                            $carData->additional_catalog_ids = json_encode($additional_catalog_ids);
                            $carData->save();
                        }
                    }
                } 
               
                // Process Lease banner images
                if (isset($translation['banner'])) {
                    foreach ($translation['banner'] as $index => $section) {
                        $imageKey = "translation.banner.$index.slider_image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'banner', 'slider_image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['banner'][$index]['slider_image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'banner', 'slider_image');
                            $translation['banner'][$index]['slider_image'] = $oldImagePath;
                        }
                    }
                }
                
                // Handle image uploads for primary fields
                $imgFields = ['sec_one_image', 'sec_two_image', 'sec_three_image', 'sec_four_image'];
                foreach ($imgFields as $imgField) {
                    if (isset($translation[$imgField]) && !empty($translation[$imgField])) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldSingleImagePath($lang, $catalogId, $imgField);
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                    }else {
                        $oldImagePath = $this->getOldSingleImagePath($lang, $catalogId, $imgField);
                        $translation[$imgField] = $oldImagePath;
                    }
                }
                
                // Process section one images
                if (isset($translation['sec_one'])) {
                    foreach ($translation['sec_one'] as $index => $section) {
                        $imageKey = "translation.sec_one.$index.image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'banner', 'image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['sec_one'][$index]['image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_one', 'image');
                            $translation['sec_one'][$index]['image'] = $oldImagePath;
                        }
                    }
                }
                
                // Process section two images
                if (isset($translation['sec_two'])) {
                    foreach ($translation['sec_two'] as $index => $section) {
                        $imageKey = "translation.sec_two.$index.image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_two', 'image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['sec_two'][$index]['image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_two', 'image');
                            $translation['sec_two'][$index]['image'] = $oldImagePath;
                        }
                    }
                }

                // Process section three images
                if (isset($translation['sec_three'])) {
                    foreach ($translation['sec_three'] as $index => $section) {
                        $imageKey = "translation.sec_three.$index.image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_three', 'image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['sec_three'][$index]['image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_three', 'image');
                            $translation['sec_three'][$index]['image'] = $oldImagePath;
                        }
                    }
                }

                 // Process section four images
                if (isset($translation['sec_four'])) {
                    foreach ($translation['sec_four'] as $index => $section) {
                        $imageKey = "translation.sec_four.$index.image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_four', 'image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['sec_four'][$index]['image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'sec_four', 'image');
                            $translation['sec_four'][$index]['image'] = $oldImagePath;
                        }
                    }
                }
                
                // Process services section images
                if (isset($translation['services'])) {
                    foreach ($translation['services'] as $index => $section) {
                        $imageKey = "translation.services.$index.image";
                        
                        if (!empty($request->$imageKey)) {
                            // Delete old image if it exists
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'services', 'image');
                            if ($oldImagePath != null) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                            // Upload new image
                            $translation['services'][$index]['image'] = $request->$imageKey;
                        }else {
                            $oldImagePath = $this->getOldImagePath($lang, $catalogId, $index, 'services', 'image');
                            $translation['services'][$index]['image'] = $oldImagePath;
                        }
                    }
                }
    
                // Update translations
                $catalogsTranslation = CatalogTranslation::where('catalog_id', $catalogId)
                    ->where('language', $lang)
                    ->first();
    
                if (!$catalogsTranslation) {
                    $catalogsTranslation = new CatalogTranslation();
                    $catalogsTranslation->catalog_id = $id;
                    $catalogsTranslation->language = $lang;
                }
    
                $catalogsTranslation->field_values = json_encode($translation, JSON_UNESCAPED_UNICODE);
                $catalogsTranslation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Catalog updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Get single page content GET/{id}/{lang} */
    public function catalogSingleDetail($id, $lang)
    {
        try {
            $catalog = Catalog::find($id);

            if(!$catalog){
                return response()->json(['status' => 'false', 'message' => 'Catalog not found'], Response::HTTP_NOT_FOUND);
            }
            
            $catalogId = $catalog->id;
            
            // Get translation based on language or default 'en' based
            $translations = CatalogTranslation::where('catalog_id', $catalogId)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
            $defualtTranslatedData = isset($translations['en']) ? json_decode($translations['en']->field_values, true) : [];
            
            // Handle car_ids count
            $carIds = json_decode($catalog->car_ids, true); // Decode JSON

            $parent_slug = "";
            if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                $parent_id = $catalog->parent_id;
                $parent_catalog = Catalog::find($parent_id);

                if($parent_catalog){
                    $parent_slug = $parent_catalog->slug;
                }
            }
            
            // Handle image URLs for primary fields
            $translatedData['catalog_id'] = $catalogId;
            $translatedData['parent_id'] = (int) $catalog->parent_id;
            $translatedData['parent_slug'] = $parent_slug;
            $translatedData['sec_one_slider_status'] = (int) $catalog->sec_one_slider_status;
            $translatedData['sec_two_slider_status'] = (int) $catalog->sec_two_slider_status;
            $translatedData['sec_three_slider_status'] = (int) $catalog->sec_three_slider_status;
            $translatedData['catalog_status'] = (int) $catalog->catalog_status;
            $translatedData['slug'] = $catalog->slug;
            $translatedData['type'] = $catalog->type;
            $translatedData['new_style_page_type'] = $catalog->new_style_page_type;
            $translatedData['banner_image'] = !empty($catalog->banner_image) && $catalog->banner_image != 'undefined'
                                                ? $this->getImageUrl($catalog->banner_image) 
                                                : null;
            $translatedData['brand_logo'] = !empty($catalog->brand_logo) && $catalog->brand_logo != 'undefined' ? 
                                            $this->getImageUrl($catalog->brand_logo) : null;
            
            // Fetch Product List 
            $productController = new ProductsController();
            // $productListFetch =  $productController->frontendProductsList($lang,0,0);
            $catalogCarsFetch =  $productController->fetchCatalogCars($lang, 0, $carIds, false, true);
            
            // if($productListFetch->original['data']){
            //     $carsList = $productListFetch->original['data'];
                
            //     $translatedData['all_cars'] = $carsList;
            // }else{
            //     $translatedData['all_cars'] = [];
            // }
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translatedData['related_cars'] = $catalogCarsList;
            }else{
                $translatedData['related_cars'] = [];
            }
            
            // Process banner images
            if (isset($defualtTranslatedData['banner'])) {
                foreach ($defualtTranslatedData['banner'] as $index => $section) {
                    $translatedData['banner'][$index]['slider_image'] = $section['slider_image'] ? $this->getImageUrl($section['slider_image']) : null;
                }
            }
            
            // Process section one images
            $translatedData['sec_one_image'] = (isset($defualtTranslatedData['sec_one_image']) && !empty($defualtTranslatedData['sec_one_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_one_image']) : null;
            if (isset($defualtTranslatedData['sec_one'])) {
                foreach ($defualtTranslatedData['sec_one'] as $index => $section) {
                    $translatedData['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section two images
            $translatedData['sec_two_image'] = (isset($defualtTranslatedData['sec_two_image']) && !empty($defualtTranslatedData['sec_two_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_two_image']) : null;
            if (isset($defualtTranslatedData['sec_two'])) {
                foreach ($defualtTranslatedData['sec_two'] as $index => $section) {
                    $translatedData['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section three images
            $translatedData['sec_three_image'] = (isset($defualtTranslatedData['sec_three_image']) && !empty($defualtTranslatedData['sec_three_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_three_image']) : null;
            if (isset($defualtTranslatedData['sec_three'])) {
                foreach ($defualtTranslatedData['sec_three'] as $index => $section) {
                    $translatedData['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section four images
            $translatedData['sec_four_image'] = (isset($defualtTranslatedData['sec_four_image']) && !empty($defualtTranslatedData['sec_four_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_four_image']) : null;
            if (isset($defualtTranslatedData['sec_four'])) {
                foreach ($defualtTranslatedData['sec_four'] as $index => $section) {
                    $translatedData['sec_four'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }

            // Process section faqs
            if (isset($defualtTranslatedData['sec_faqs'])) {
                foreach ($defualtTranslatedData['sec_faqs'] as $index => $section) {
                    if(!isset($translatedData['sec_faqs'][$index])){
                        $translatedData['sec_faqs'][$index] = $section;
                    }
                }
            }
            
            if (isset($defualtTranslatedData['sec_testimonials'])) {
                foreach ($defualtTranslatedData['sec_testimonials'] as $index => $section) {
                    $translatedData['sec_testimonials'][$index]['image'] = isset($section['image']) ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process services section images
            if (isset($defualtTranslatedData['services'])) {
                foreach ($defualtTranslatedData['services'] as $index => $section) {
                    $translatedData['services'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
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
    
    /* Fetch frontend page content GET/{slug}/{lang}/{page_type} */
    public function fetchFrontendContent($lang, $slug)
    {
        try {
            $per_page = request()->query('per_page');
            
            $webContentController = new WebContentController();
            
            // Fetch product inner page content
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
            
            $locations = "";
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $telephone_number = $productInnerData['telephone_number'];
                $whatsapp = $productInnerData['whatsapp'];
                $locations = $productInnerData['locations'];
            }
            
            $productsController = new ProductsController();
            
            // Get product single detail by slug
            $productFetchDetail = $productsController->productFetchDetail($lang, $slug);
            
            if(isset($productFetchDetail->original['data']) && !empty($productFetchDetail->original['data'])){
                $productData = $productFetchDetail->original['data'];
                $productData['locations'] = $locations;
                return response()->json([
                    'status' => 'true',
                    'data' => $productData
                ], Response::HTTP_OK);
            }
            
            $catalog = Catalog::where('slug',$slug)
                        ->first();

            if(!$catalog){
                return response()->json(['status' => 'false', 'message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }
            
            $catalogId = $catalog->id;
            
            // Get translation based on language or default 'en' based
            $translations = CatalogTranslation::where('catalog_id', $catalogId)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
            $defualtTranslatedData = isset($translations['en']) ? json_decode($translations['en']->field_values, true) : [];
            
            $carIds = json_decode($catalog->car_ids, true); // Decode JSON
            
            $parent_slug = $parent_title = $page_full_slug = "";
            if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                $parent_id = $catalog->parent_id;
                $parent_catalog = Catalog::find($parent_id);

                if($parent_catalog){
                    $parentQuery = CatalogTranslation::where('catalog_id', $parent_id)
                        ->whereIn('language', [$lang, 'en']) // Check both requested and default language
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang]) // Prioritize requested language first
                        ->first();
                    
                    $parentTranslation = !empty($parentQuery) ? json_decode($parentQuery->field_values, true) : [];
                    $parent_title = $parentTranslation['catalog_title'];
                    $parent_slug = $parent_catalog->slug;
                    $page_full_slug .= $parent_slug.'/';
                }
            }
            
            $productController = new ProductsController();
            
            $product_filter = request()->query('product_filter');
            $availability = request()->query('availability');
            if(!empty($product_filter) && $product_filter == 1){
                
                $price_type = request()->query('price_type');
                $car_types = request()->query('car_types');
                $featured = request()->query('featured');
                $year = request()->query('year');
                $price_category = request()->query('price_category');
                $min = request()->query('min');
                $max = request()->query('max');
                $specs = request()->query('specs');
                $brands = request()->query('brands');
                
                $request = new Request([
                            'price_type'      => $price_type,
                            'car_types'       => $car_types,
                            'featured'        => $featured,
                            'year'            => $year,
                            'availability'    => $availability,
                            'price_category'  => $price_category,
                            'min'             => $min,
                            'max'             => $max,
                            'specs'           => $specs,
                            'brands'          => $brands
                        ]);    
                    
                $catalogCarsFetch = $productController->applyProductFilter($request, $slug, $lang, $per_page, $carIds);
            }else{
                $catalogCarsFetch =  $productController->fetchCatalogCars($lang, $per_page, $carIds, false, true, $availability);
            }
            
            if($catalogCarsFetch->original['data']){
                $carsList = $catalogCarsFetch->original['data'];
                $carsListPagination = $catalogCarsFetch->original['pagination'];
                
                $translatedData['cars_details']['all_cars'] = $carsList;
                $translatedData['cars_details']['cars_pagination'] = $carsListPagination;
            }else{
                $translatedData['cars_details'] = [];
            }
            
            $catalog_slug = $catalog->slug;
            $type = $catalog->type;
            $new_style_page_type = $catalog->new_style_page_type;
            $page_full_slug .= $catalog_slug.'/';
            
            // Handle image URLs for primary fields
            $translatedData['parent_id'] = (int) $catalog->parent_id;
            $translatedData['parent_title'] = $parent_title;
            $translatedData['parent_slug'] = $parent_slug;
            $translatedData['sec_one_slider_status'] = (int) $catalog->sec_one_slider_status;
            $translatedData['sec_two_slider_status'] = (int) $catalog->sec_two_slider_status;
            $translatedData['sec_three_slider_status'] = (int) $catalog->sec_three_slider_status;
            $translatedData['catalog_status'] = (int) $catalog->catalog_status;
            $translatedData['catalog_type'] = $type;
            $translatedData['new_style_page_type'] = $new_style_page_type;
            $translatedData['page_full_slug'] = $page_full_slug;
            $translatedData['slug'] = $catalog_slug;
            $translatedData['telephone_number'] = $telephone_number;
            $translatedData['whatsapp'] = $whatsapp;
            $translatedData['banner_image'] = !empty($catalog->banner_image) && $catalog->banner_image != 'undefined'
                                                ? $this->getImageUrl($catalog->banner_image) 
                                                : null;
            $translatedData['brand_logo'] = !empty($catalog->brand_logo) && $catalog->brand_logo != 'undefined' ? 
                                            $this->getImageUrl($catalog->brand_logo) : null;
                                            
            // Process banner images
            if (isset($defualtTranslatedData['banner'])) {
                foreach ($defualtTranslatedData['banner'] as $index => $section) {
                    $translatedData['banner'][$index]['slider_image'] = $section['slider_image'] ? $this->getImageUrl($section['slider_image']) : null;
                }
            }
            
            // Process section one images
            $translatedData['sec_one_image'] = (isset($defualtTranslatedData['sec_one_image']) && !empty($defualtTranslatedData['sec_one_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_one_image']) : null;
            if (isset($defualtTranslatedData['sec_one'])) {
                foreach ($defualtTranslatedData['sec_one'] as $index => $section) {
                    $translatedData['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section two images
            $translatedData['sec_two_image'] = (isset($defualtTranslatedData['sec_two_image']) && !empty($defualtTranslatedData['sec_two_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_two_image']) : null;
            if (isset($defualtTranslatedData['sec_two'])) {
                foreach ($defualtTranslatedData['sec_two'] as $index => $section) {
                    $translatedData['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section three images
            $translatedData['sec_three_image'] = (isset($defualtTranslatedData['sec_three_image']) && !empty($defualtTranslatedData['sec_three_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_three_image']) : null;
            if (isset($defualtTranslatedData['sec_three'])) {
                foreach ($defualtTranslatedData['sec_three'] as $index => $section) {
                    $translatedData['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process section four images
            $translatedData['sec_four_image'] = (isset($defualtTranslatedData['sec_four_image']) && !empty($defualtTranslatedData['sec_four_image']))
                                                        ? $this->getImageUrl($defualtTranslatedData['sec_four_image']) : null;
            if (isset($defualtTranslatedData['sec_four'])) {
                foreach ($defualtTranslatedData['sec_four'] as $index => $section) {
                    $translatedData['sec_four'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }

            // Process section faqs
            if (isset($defualtTranslatedData['sec_faqs'])) {
                foreach ($defualtTranslatedData['sec_faqs'] as $index => $section) {
                    if(!isset($translatedData['sec_faqs'][$index])){
                        $translatedData['sec_faqs'][$index] = $section;
                    }
                }
            }
            
            if (isset($defualtTranslatedData['sec_testimonials'])) {
                foreach ($defualtTranslatedData['sec_testimonials'] as $index => $section) {
                    $translatedData['sec_testimonials'][$index]['image'] = isset($section['image']) ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Process services section images
            if (isset($defualtTranslatedData['services'])) {
                foreach ($defualtTranslatedData['services'] as $index => $section) {
                    $translatedData['services'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }
            
            // Fetch Function
            $car_brands = $this->catalogsMenuList($lang, 'car_brands', $carIds);
            
            $brandsList = "";
            if(isset($car_brands->original['data'])){
                $brandsList = $car_brands->original['data'];
                $translatedData['brands_list'] = $brandsList;
            }
            
            // Get all location list
            $translatedData['locations'] = $locations;

            $testimonialsQuery = Testimonial::latest()
                                    ->take(12)
                                    ->get();
                                    
            $formattedTestimonials = $testimonialsQuery->map(function ($testimonial) use ($lang)  {
                $id = $testimonial->id;
                
                $translation = TestimonialTranslation::where('testimonial_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                $car_id =  $testimonial->car_id;
                
                
                $carTranslation = ProductTranslation::where('product_id', $car_id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
                $carTranslatedData = !empty($carTranslation) ? json_decode($carTranslation->field_values, true) : [];
                
                $car_title = isset($carTranslatedData['product_title']) ? $carTranslatedData['product_title'] : '';
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['client_id'] = $testimonial->client_id ?? null; // Set stars
                $translatedData['client_email'] = $testimonial->client_email;
                $translatedData['client_phone'] = $testimonial->client_phone;
                $translatedData['client_image'] = $testimonial->client_image ? $this->getImageUrl($testimonial->client_image) : null;
                $translatedData['car_title'] = $car_title;
                $translatedData['stars'] = $testimonial->stars ?? null; // Set stars
                $translatedData['created_at'] = $testimonial->created_at->format('Y-m-d');
                
                return $translatedData;
            });

        
            $translatedData['home_testimonials'] = $formattedTestimonials->isNotEmpty()
                ? $formattedTestimonials->values()
                : [];

            // Fetch Home webcontent for document list data
            $webContent = WebContent::where('slug','home')->first();
            
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $webTranslations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
              
            // Decode JSON translations
            $translateArray = isset($webTranslations[$lang]) ? json_decode($webTranslations[$lang]->translated_value, true) : json_decode($webTranslations['en']->translated_value, true);
            $defaultTranslatedData = isset($webTranslations['en']) ? json_decode($webTranslations['en']->translated_value, true) : [];
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
                $translatedData['document_list'] = $translateArray['sec_two'];
            }else{
                $translatedData['document_list'] = [];
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
    
    public function fetchCatalogMetaData($lang, $slug)
    {
        try {
            $catalogMetaData = [];
            if($slug == 'vehicle-listing'){
                $webContentController = new WebContentController();
                $flexibleMeta =  $webContentController->getWebMetaDeta($slug,$lang);
                
                if(!empty($flexibleMeta->original['data'])){
                    $flexiMetaData = $flexibleMeta->original['data'];
                    $catalogMetaData['meta_title'] = $flexiMetaData['meta_title'];
                    $catalogMetaData['meta_description'] = $flexiMetaData['meta_description'];
                }
            }else{
                
                // Get meta data for product single detail page
                $productsController = new ProductsController();
            
                // Get product single detail by slug
                $productFetchDetail = $productsController->productFetchDetail($lang, $slug);
                
                if(isset($productFetchDetail->original['data']) && !empty($productFetchDetail->original['data'])){
                    $productData = $productFetchDetail->original['data'];
                    $catalogMetaData['meta_title'] = $productData['meta_title'];
                    $catalogMetaData['meta_description'] = $productData['meta_description'];
                    
                    return response()->json([
                        'status' => 'true',
                        'data' => $catalogMetaData
                    ], Response::HTTP_OK);
                }
            
                $catalog = Catalog::where('slug', $slug)->first();
        
                if (!$catalog) {
                    return response()->json(['status' => 'false', 'message' => 'Data not found', 'data' => []], Response::HTTP_NOT_FOUND);
                }
        
                $catalogId = $catalog->id;
        
                $translations = CatalogTranslation::where('catalog_id', $catalogId)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, '$lang', 'en')")
                    ->get()
                    ->keyBy('language');
        
                $translatedData = isset($translations[$lang])
                    ? json_decode($translations[$lang]->field_values, true)
                    : json_decode($translations['en']->field_values, true);
        
                
                if (!empty($translatedData)) {
                    $catalogMetaData['meta_title'] = $translatedData['meta_title'];
                    $catalogMetaData['meta_description'] = $translatedData['meta_description'];
                }
            }
    
            return response()->json([
                'status' => 'true',
                'data' => $catalogMetaData
            ], Response::HTTP_OK);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    /* Fetch frontend page content for flexible */
    public function flexibleFrontendContent($lang = 'en')
    {
        try {
            $per_page = request()->query('per_page');
            
            $catalog = Catalog::where('slug','our-fleet')
                        ->first();

            if(!$catalog){
                return response()->json(['status' => 'false', 'message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }
            
            $catalogId = $catalog->id;
            $carIds = json_decode($catalog->car_ids, true); // Decode JSON
            
            $productController = new ProductsController();
            
            $product_filter = request()->query('product_filter');
            if(!empty($product_filter) && $product_filter == 1){
                
                $price_type = request()->query('price_type');
                $car_types = request()->query('car_types');
                $featured = request()->query('featured');
                $year = request()->query('year');
                $availability = request()->query('availability');
                $price_category = request()->query('price_category');
                $min = request()->query('min');
                $max = request()->query('max');
                $specs = request()->query('specs');
                $brands = request()->query('brands');
                
                $request = new Request([
                            'price_type'      => $price_type,
                            'car_types'       => $car_types,
                            'featured'        => $featured,
                            'year'            => $year,
                            'availability'    => $availability,
                            'price_category'  => $price_category,
                            'min'             => $min,
                            'max'             => $max,
                            'specs'           => $specs,
                            'brands'          => $brands
                        ]);    
                        
                $catalogCarsFetch = $productController->applyProductFilter($request, 'our-fleet', $lang, $per_page, true);
            }else{
                $catalogCarsFetch =  $productController->fetchCatalogCars($lang, $per_page, $carIds,true);
            }
            
            
            $flexibleCarList = [];
            if($catalogCarsFetch->original['data']){
                $carsList = $catalogCarsFetch->original['data'];
                $carsListPagination = $catalogCarsFetch->original['pagination'];
                
                $flexibleCarList['cars_details']['all_cars'] = $carsList;
                $flexibleCarList['cars_details']['cars_pagination'] = $carsListPagination;
            }else{
                $flexibleCarList['cars_details'] = [];
            }
            
             $webContentController = new WebContentController();
            
            // Fetch product inner page content
            $productInnerDetails =  $webContentController->getWebProductInnerContent($lang);
            
            $locations = "";
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $locations = $productInnerData['locations'];
            }
            
            
            // Fetch Function
            $car_brands = $this->catalogsMenuList($lang, 'car_brands', $carIds);
            
            $brandsList = "";
            if(isset($car_brands->original['data'])){
                $brandsList = $car_brands->original['data'];
                $flexibleCarList['brands_list'] = $brandsList;
            }
            
            // Get all location list
            $flexibleCarList['locations'] = $locations;
            
            $flexibleMeta =  $webContentController->getWebFlexibleRental($lang);
            
            if($flexibleMeta->original['data']){
                $flexibleCarList['meta'] = $flexibleMeta->original['data'];
            }else{
                $flexibleCarList['meta'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $flexibleCarList
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Data fetch part DELETE/{id} */
    public function deleteCatalog($id)
    {
        try {
            // Find the promotion by slug
            $catalog = Catalog::where('id', $id)->first();
    
            if (!$catalog) {
                return response()->json(['status' => 'false', 'message' => 'Catalog not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Delete images if they exist
            if ($catalog->banner_image && Storage::disk('public')->exists($catalog->banner_image)) {
                Storage::disk('public')->delete($catalog->banner_image);
            }
    
            // Delete the associated translations
            CatalogTranslation::where('catalog_id', $id)->delete();
    
            // Delete the promotion
            $catalog->delete();
    
            return response()->json([
                'status' => 'true',
                'message' => 'Catalog deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Catalog list search function
    public function searchCatalogsList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $catalogQuery = Catalog::query()
            ->join('catalog_translations', function ($join) use ($lang) {
                $join->on('catalogs.id', '=', 'catalog_translations.catalog_id')
                    ->where('catalog_translations.language', '=', $lang);
            })
            ->select('catalogs.*', 'catalog_translations.field_values');

            // Apply search filters for both slug and promotion_title
            if (!empty($catalogQuery)) {
                $catalogQuery->where(function ($query) use ($searchQuery) {
                    $query->where('catalogs.slug', 'LIKE', "%{$searchQuery}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(catalog_translations.field_values, '$.catalog_title')) LIKE ?", ["%{$searchQuery}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(catalog_translations.field_values, '$.title')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $catalogQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
             if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $catalogs = $catalogQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $catalogs->count(), // All items in one "page"
                    'total' => $catalogs->count(),
                ];
            } else {
                // Paginate the remaining partners
                $catalogs = $catalogQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $catalogs->currentPage(),
                    'last_page' => $catalogs->lastPage(),
                    'per_page' => $catalogs->perPage(),
                    'total' => $catalogs->total(),
                ];
            }
    
            if ($catalogs->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No catalogs found'], 200);
            }
    
            $catalogs_translations = $catalogs->map(function ($catalog) use ($lang) {
                $id = $catalog->id;
                $translation = CatalogTranslation::where('catalog_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->field_values, true);
                } else {
                    $defaultData = CatalogTranslation::where('catalog_id', $id)
                        ->where('language', 'en')
                        ->first();
    
                    if (!empty($defaultData)) {
                        $translatedData = json_decode($defaultData->field_values, true);
                    }
                }
                
                // Process banner images
                if (isset($translatedData['banner'])) {
                    foreach ($translatedData['banner'] as $index => $section) {
                        $translatedData['banner'][$index]['slider_image'] = $section['slider_image'] ? $this->getImageUrl($section['slider_image']) : null;
                    }
                }
                
                // Handle car_ids count
                $carIds = json_decode($catalog->car_ids, true); // Decode JSON
                $carCount = is_array($carIds) ? count($carIds) : 0; // Count if valid array

                
                $parent_slug = "";
                if(!empty($catalog->parent_id) && $catalog->parent_id != null){
                    $parent_id = $catalog->parent_id;
                    $parent_catalog = Catalog::find($parent_id);
    
                    if(!$parent_catalog){
                        return response()->json(['status' => 'false', 'message' => 'Parent Catalog not found'], Response::HTTP_NOT_FOUND);
                    }
                    $parent_slug = $parent_catalog->slug;
                }

    
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['parent_id'] = (int) $catalog->parent_id;
                $translatedData['parent_slug'] = $parent_slug;
                $translatedData['catalog_status'] = (int) $catalog->catalog_status;
                $translatedData['slug'] = $catalog->slug;
                $translatedData['type'] = $catalog->type;
                $translatedData['new_style_page_type'] = $catalog->new_style_page_type;
                $translatedData['banner_image'] = !empty($catalog->banner_image) && $catalog->banner_image != 'undefined' ?
                                                    $this->getImageUrl($catalog->banner_image) : null;
                $translatedData['brand_logo'] = !empty($catalog->brand_logo) && $catalog->brand_logo != 'undefined' ? 
                                            $this->getImageUrl($catalog->brand_logo) : null;
                $translatedData['cars_count'] = $carCount;
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $catalogs_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    protected function getOldSingleImagePath($lang, $catalogId, $key)
    {
        // Retrieve the web content translation entry for the current language and web content ID
        $catalogTranslation = CatalogTranslation::where('language', $lang)
            ->where('catalog_id', $catalogId)
            ->first();
    
        // Check if the translation exists
        if (!$catalogTranslation) {
            return null; // or handle the case where the translation is not found
        }
    
        // Decode the JSON stored in the 'translated_value' field
        $oldTranslation = json_decode($catalogTranslation->field_values, true);
    
        // Check if the section exists and return the appropriate path
        if (isset($oldTranslation[$key])) {
            return $oldTranslation[$key] ?? null;
        }
    
        return null;
    }
    
    protected function getOldImagePath($lang, $catalogId, $index, $section, $name)
    {
        // Retrieve the web content translation entry for the current language and web content ID
        $catalogTranslation = CatalogTranslation::where('language', $lang)
            ->where('catalog_id', $catalogId)
            ->first();
    
        // Check if the translation exists
        if (!$catalogTranslation) {
            return null; // or handle the case where the translation is not found
        }
    
        // Decode the JSON stored in the 'translated_value' field
        $oldTranslation = json_decode($catalogTranslation->field_values, true);
    
        // Check if the section exists and return the appropriate path
        if (isset($oldTranslation[$section])) {
            // Handle sec_six and similar sections where the image is nested in an array of objects
            if (isset($oldTranslation[$section][$index])) {
                return $oldTranslation[$section][$index][$name] ?? null;
            }
        }
    
        return null;
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
