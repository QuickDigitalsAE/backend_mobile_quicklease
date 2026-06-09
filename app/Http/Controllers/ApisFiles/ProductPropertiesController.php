<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyTranslation;
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

class ProductPropertiesController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:ProductProperties View', ['only' => ['propertiesList', 'propertySingleDetail', 'searchPropertiesList']]);
        $this->middleware('permission:ProductProperties Add', ['only' => ['storeProperty']]);
        $this->middleware('permission:ProductProperties Edit', ['only' => ['updateProperty']]);
        $this->middleware('permission:ProductProperties Delete', ['only' => ['deleteProperty']]);
    }
    
    // Get all fetch list
    public function propertiesList($lang, $per_page=6)
    {
        try {
            $propertyQuery = Property::orderBy('created_at', 'ASC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $properties = $propertyQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $properties->count(), // All items in one "page"
                    'total' => $properties->count(),
                ];
            } else {
                // Paginate the remaining data
                $properties = $propertyQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                ];
            }
            
            if($properties->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Product property not found'], 200);
            }
            
            $properties_translations = $properties->map(function($property) use ($lang) {
                $id = $property->id;
                $translation = PropertyTranslation::where('property_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = PropertyTranslation::where('property_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['property_status'] = (int) $property->property_status;
                $translatedData['type'] = $property->type;
                $translatedData['property_field_type'] = $property->property_field_type;
                $translatedData['property_image'] = $property->property_image ? $this->getImageUrl($property->property_image) : null;
                
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $properties_translations,
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
    public function propertiesFrontendList($lang)
    {
        try {
            $propertiesQuery = Property::where('property_status', 1)->get();
    
            if ($propertiesQuery->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Product properties not found'], 200);
            }
    
            // Group properties by "type" and include translations
            $groupedProperties = $propertiesQuery->groupBy('type')->sortBy(function ($value, $key) {
                    $order = ['general_information' => 1, 'car_options' => 2, 'car_services' => 3];
                    return $order[$key] ?? 99; // Default high value for unknown types
                })->map(function ($items) use ($lang) {
                    return $items->map(function ($item) use ($lang) {
                        $id = $item->id;
                
                        // Fetch translation for the given language with optimized query
                        $translation = PropertyTranslation::where('property_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
                
                        $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                
                        // Merge translation data with main data
                        return array_merge($translatedData, [
                            'id' => $id,
                            'property_status' => (int) $item->property_status,
                            'property_field_type' => $item->property_field_type,
                            'property_image' => $item->property_image ? $this->getImageUrl($item->property_image) : null,
                        ]);
                    });
                });

            
            return response()->json([
                'status' => 'true',
                'message' => 'Properties retrieved successfully',
                'data' => $groupedProperties
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /* Data insertion part POST/{lang} */
    public function storeProperty(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'property_image'  => 'nullable|string',
                'property_status' => 'required|numeric',
                'type' => 'required|string',
                'property_field_type' => 'required|string'
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

                $property = new Property();
                // Handle image uploads for primary fields
                if (!empty($request->property_image)) {
                    // Upload new image
                    $property->property_image = $request->property_image;
                }
                
                $property->property_status = $request->property_status;
                $property->type = $request->type;
                $property->property_field_type = $request->property_field_type;
                $property->created_by = $userId;
                $property->save();
        
                $propertyId = $property->id;
                $translations = $request->input('translation', []);
                
                $property_translation = new PropertyTranslation();
                $property_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $property_translation->language = $lang;
                $property_translation->property_id = $propertyId;
                $property_translation->save();
                
                DB::commit(); // Commit transaction

                return response()->json(['status' => 'true', 'message' => 'Product property created successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Data update part PUT/{id}/{lang} */
    public function updateProperty(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
           $rules = [
                'property_image'  => 'nullable|string',
                'property_status' => 'required|numeric',
                'type' => 'required|string',
                'property_field_type' => 'required|string'
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
    
                $property = Property::find($id);
                if (!$property) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Product property not found'
                    ], Response::HTTP_NOT_FOUND);
                }
        
                // Handle image uploads for primary fields
                if (!empty($request->property_image)) {
                    // Delete the old image if it exists
                    if ($property->property_image && Storage::disk('public')->exists($property->property_image)) {
                        Storage::disk('public')->delete($property->property_image);
                    }
                    
                    // Upload new image
                    $property->property_image = $request->property_image;
                }
                
                $property->property_status = $request->property_status;
                $property->type = $request->type;
                $property->property_field_type = $request->property_field_type;
                $property->save();
    
                $translations = $request->input('translation', []);
    
                // Update translations
                $propertyTranslation = PropertyTranslation::where('property_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                if (!$propertyTranslation) {
                    $propertyTranslation = new PropertyTranslation();
                    $propertyTranslation->property_id = $id;
                    $propertyTranslation->language = $lang;
                }
    
                $propertyTranslation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $propertyTranslation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Product property updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Fetch part GET/{id}/{lang} */
    public function propertySingleDetail($id, $lang)
    {
        try {
            $property = Property::find($id);

            if(!$property){
                return response()->json(['status' => 'false', 'message' => 'Product property not found'], Response::HTTP_NOT_FOUND);
            }
            
            $propertyId = $property->id;
            // Fetch the translation for the given language
            $translation = PropertyTranslation::where('property_id', $propertyId)
                ->where('language', $lang)
                ->first();
    
            $translatedData = [];
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = PropertyTranslation::where('property_id', $propertyId)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $translatedData = json_decode($defaultData->field_values, true);
                }
            }
            
            // Handle image URLs
            $translatedData['property_status'] = (int) $property->property_status;
            $translatedData['property_field_type'] = $property->property_field_type;
            $translatedData['type'] = $property->type;
            $translatedData['property_image'] = $property->property_image ? $this->getImageUrl($property->property_image) : null;
            
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

    /* Property DELETE/{id} */
    public function deleteProperty($id)
    {
        try {
            // Find the propery by id
            $property = Property::where('id', $id)->first();
    
            if (!$property) {
                return response()->json(['status' => 'false', 'message' => 'Product property not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Delete images if they exist
            if ($property->property_image && Storage::disk('public')->exists($property->property_image)) {
                Storage::disk('public')->delete($property->property_image);
            }
    
            // Get the ID
            $propertyId = $property->id;
    
            // Delete the associated translations
            PropertyTranslation::where('property_id', $propertyId)->delete();
    
            // Delete the record
            $property->delete();
    
            return response()->json([
                'status' => 'true',
                'message' => 'Property deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    // Get list search function
    public function searchPropertiesList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $propertiesQuery = Property::query()
            ->join('property_translations', function ($join) use ($lang) {
                $join->on('properties.id', '=', 'property_translations.property_id')
                    ->where('property_translations.language', '=', $lang);
            })
            ->select('properties.*', 'property_translations.field_values');

            // Apply search filters for both slug and promotion_title
            if (!empty($searchQuery)) {
                $propertiesQuery->where(function ($query) use ($searchQuery) {
                    $query->where('properties.type', 'LIKE', "%{$searchQuery}%")
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(property_translations.field_values, '$.property_title')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $propertiesQuery->orderBy('created_at', 'ASC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $properties = $propertiesQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $properties->count(), // All items in one "page"
                    'total' => $properties->count(),
                ];
            } else {
                // Paginate the remaining data
                $properties = $propertiesQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                ];
            }
    
            if ($properties->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No properties found'], 200);
            }
    
            $properties_translations = $properties->map(function ($property) use ($lang) {
                $id = $property->id;
                $translation = PropertyTranslation::where('property_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->field_values, true);
                } else {
                    $defaultData = PropertyTranslation::where('property_id', $id)
                        ->where('language', 'en')
                        ->first();
    
                    if (!empty($defaultData)) {
                        $translatedData = json_decode($defaultData->field_values, true);
                    }
                }
    
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['property_status'] = (int) $property->property_status;
                $translatedData['type'] = $property->type;
                $translatedData['property_field_type'] = $property->property_field_type;
                $translatedData['property_image'] = $property->property_image ? $this->getImageUrl($property->property_image) : null;
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $properties_translations,
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
