<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\PartnerTranslation;
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

class PartnerController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Partner View', ['only' => ['partnersList', 'partnerSingleDetail', 'searchPartnersList']]);
        $this->middleware('permission:Partner Add', ['only' => ['storePartner']]);
        $this->middleware('permission:Partner Edit', ['only' => ['updatePartner']]);
        $this->middleware('permission:Partner Delete', ['only' => ['deletePartner']]);
    }
    
    // Get all partners list
    public function partnersList($lang, $per_page=6)
    {
        try {
            $partnerQuery = Partner::orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $partners = $partnerQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $partners->count(), // All items in one "page"
                    'total' => $partners->count(),
                ];
            } else {
                // Paginate the remaining blogs
                $partners = $partnerQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $partners->currentPage(),
                    'last_page' => $partners->lastPage(),
                    'per_page' => $partners->perPage(),
                    'total' => $partners->total(),
                ];
            }
            
            if($partners->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Partners not found'], 200);
            }
            
            $partners_translations = $partners->map(function($partner) use ($lang) {
                $id = $partner->id;
                $created_by = $partner->created_by;
                $updated_by = $partner->updated_by;
                $created_at = $partner->created_at;
                $updated_at = $partner->updated_at;
                
                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);

                $translation = PartnerTranslation::where('partner_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = PartnerTranslation::where('partner_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['partner_status'] = (int) $partner->partner_status;
                $translatedData['partner_slug'] = $partner->slug;
                $translatedData['partner_image'] = $partner->image ? $this->getImageUrl($partner->image) : null;
             
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $partners_translations,
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
    
     // Get all partners frontend list
    public function partnersFrontendList($lang)
    {
        try {
            $partnerQuery = Partner::where('partner_status','=', 1)
                            ->orderBy('created_at', 'DESC');
            $partners = $partnerQuery->get();
            
            if($partners->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Partners not found'], 200);
            }
            
            $partners_translations = $partners->map(function($partner) use ($lang) {
                $id = $partner->id;
                $translation = PartnerTranslation::where('partner_id', $id)
                                ->where('language',$lang)
                                ->first();
                
                $fetchPartnersData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->field_values, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = PartnerTranslation::where('partner_id', $id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->field_values, true);
                    }    
                }
                
                // Handle image URLs for primary fields
                $fetchPartnersData['id'] = $id;
                $fetchPartnersData['partner_slug'] = $partner->slug;
                $fetchPartnersData['partner_title'] = $translatedData['partner_title'];
                $fetchPartnersData['partner_image'] = $partner->image ? $this->getImageUrl($partner->image) : null;
             
                return $fetchPartnersData;
            });
            
            // Fetch blogs meta
            $webContentController = new WebContentController();
            $partnersData =  $webContentController->fetchPartners($lang);
            
            if($partnersData->original['data']){
                $metaData = $partnersData->original['data'];
            }else{
                $metaData = [];
            }
            
            return response()->json([
                'status' => 'true',
                'message' => 'Partners retrieved successfully',
                'data' => [
                        'all_partners' => $partners_translations,
                        'meta' => $metaData
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
    
    /* Partner data insertion part POST/{lang} */
    public function storePartner(Request $request, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'partner_status' => 'required|numeric',
                'partner_slug'  => 'required|string',
                "translation" => 'array'
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
    
                $partner = new Partner();
                
                // Handle image uploads for primary fields
                if (!empty($request->partner_image)) {
                    $partner->image = $request->partner_image;
                }
                
                $partner->partner_status = $request->partner_status;
                $partner->slug = $request->partner_slug;
                $partner->created_by = $userId;
                $partner->save();
        
                $partnerId = $partner->id;
                $translations = $request->input('translation', []);
                
                $partner_translation = new PartnerTranslation();
                $partner_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $partner_translation->language = $lang;
                $partner_translation->partner_id = $partnerId;
                $partner_translation->save();

                return response()->json(['status' => 'true', 'message' => 'Partner created successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Partner tab data update part PUT/{id}/{lang} */
    public function updatePartner(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'partner_status' => 'required|numeric',
                'partner_slug'  => 'nullable|string',
                "translation" => 'array'
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
    
                $partner = Partner::find($id);
                if (!$partner) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Partner not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                // Update image if provided
                if (!empty($request->partner_image)) {
                    // Delete the old image if it exists
                    if ($partner->image && Storage::disk('public')->exists($partner->image)) {
                        Storage::disk('public')->delete($partner->image);
                    }
                    
                    // Upload new image
                    $partner->image = $request->partner_image;
                }
                
                $partner->partner_status = $request->partner_status;
                $partner->slug = $request->partner_slug;
                $partner->updated_by = $userId;
                $partner->save();
    
                $translations = $request->input('translation', []);
    
                // Update translations
                $partnerTranslation = PartnerTranslation::where('partner_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                if (!$partnerTranslation) {
                    $partnerTranslation = new PartnerTranslation();
                    $partnerTranslation->partner_id = $id;
                    $partnerTranslation->language = $lang;
                }
    
                $partnerTranslation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $partnerTranslation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Partner updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Data fetch part GET/{id}/{lang} */
    public function partnerSingleDetail($id, $lang)
    {
        try {
            $partner = Partner::find($id);

            if(!$partner){
                return response()->json(['status' => 'false', 'message' => 'Partner not found'], Response::HTTP_NOT_FOUND);
            }
            
            $partnerId = $partner->id;
            // Fetch the translation for the given language
            $translation = PartnerTranslation::where('partner_id', $partnerId)
                ->where('language', $lang)
                ->first();
    
            $translatedData = [];
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = PartnerTranslation::where('partner_id', $partnerId)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $translatedData = json_decode($defaultData->field_values, true);
                }
            }
            
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $partner->id;
            $translatedData['partner_status'] = (int) $partner->partner_status;
            $translatedData['partner_slug'] = $partner->slug;
            $translatedData['partner_image'] = $partner->image ? $this->getImageUrl($partner->image) : null;
            
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
    
    public function partnerFetchDetail($slug, $lang)
    {
        try {
            $partner = Partner::where('partner_status', '=', 1)
                        ->where('slug',$slug)->first();

            if(!$partner){
                return response()->json(['status' => 'false', 'message' => 'Partner not found'], Response::HTTP_NOT_FOUND);
            }
            
            $partnerId = $partner->id;
            // Fetch the translation for the given language
            $translation = PartnerTranslation::where('partner_id', $partnerId)
                ->where('language', $lang)
                ->first();
    
            $translatedData = [];
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = PartnerTranslation::where('partner_id', $partnerId)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $translatedData = json_decode($defaultData->field_values, true);
                }
            }
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $partner->id;
            $translatedData['partner_status'] = (int) $partner->partner_status;
            $translatedData['partner_slug'] = $partner->slug;
            $translatedData['partner_image'] = $partner->image ? $this->getImageUrl($partner->image) : null;
            
            // Fetch partners meta
            $webContentController = new WebContentController();
            $partnersMeta =  $webContentController->getWebMetaDeta('partners',$lang);
            
            if($partnersMeta->original['data']){
                $metaData = $partnersMeta->original['data'];
                
                $translatedData['partner_banner_image'] = $metaData['banner'];
            }
            
            $partnersFetch = $this->partnersFrontendList($lang);
            
            if($partnersFetch->original['data']){
                $all_partners = $partnersFetch->original['data']['all_partners'];
            }else{
                $all_partners = [];
            }
            
            
            // Element controller
            $elements = $webContentController->getWebElements($lang);

            if($elements->original['data']){
                $elements_data = $elements->original['data'];
            }else{
                $elements_data = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => [
                        'all_partners' => $all_partners,
                        'single_partner' => $translatedData
                        ],
                'elements' => $elements_data
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // protected function relatedPartners($partner, $lang){
        
    //     // Fetch the previous partner
    //     $previousPartner = Partner::where('created_at', '<', $partner->created_at)
    //         ->orderBy('created_at', 'DESC')
    //         ->first();
    //     $partnersData = array();
    //     $previousData = null;
    //     if ($previousPartner) {
    //         $prevTranslation = PartnerTranslation::where('partner_id', $previousPartner->id)
    //             ->where('language', $lang)
    //             ->first();
                
    //         $prevTranslatedData = [];
    //         if (!empty($prevTranslation)) {
    //             // Decode the JSON translation data
    //             $prevTranslatedData = json_decode($prevTranslation->field_values, true);
    //         } else {
    //             // Fetch default language data if translation not found
    //             $defaultData = PartnerTranslation::where('partner_id', $previousPartner->id)
    //                 ->where('language', 'en')
    //                 ->first();
    
    //             if (!empty($defaultData)) {
    //                 $prevTranslatedData = json_decode($defaultData->field_values, true);
    //             }
    //         }
            
    //         $previousData = [
    //             'slug' => $previousPartner->slug,
    //             'title' => $prevTranslatedData ? $prevTranslatedData['partner_title'] ?? '' : ''
    //         ];
    //     }

    //     // Fetch the next partner
    //     $nextPartner = Partner::where('created_at', '>', $partner->created_at)
    //         ->orderBy('created_at', 'ASC')
    //         ->first();

    //     $nextData = null;
    //     if ($nextPartner) {
    //         $nextTranslation = PartnerTranslation::where('partner_id', $nextPartner->id)
    //             ->where('language', $lang)
    //             ->first();
                
    //         $nextTranslatedData = [];
    //         if (!empty($nextTranslation)) {
    //             // Decode the JSON translation data
    //             $nextTranslatedData = json_decode($nextTranslation->field_values, true);
    //         } else {
    //             // Fetch default language data if translation not found
    //             $defaultData = PartnerTranslation::where('partner_id', $nextPartner->id)
    //                 ->where('language', 'en')
    //                 ->first();
    
    //             if (!empty($defaultData)) {
    //                 $nextTranslatedData = json_decode($defaultData->field_values, true);
    //             }
    //         }    
            
    //         $nextData = [
    //             'slug' => $nextPartner->slug,
    //             'title' => $nextTranslatedData ? $nextTranslatedData['partner_title'] ?? '' : ''
    //         ];
    //     }

    //     // Include previous and next partner data
    //     $partnersData['previous_partner'] = $previousData;
    //     $partnersData['next_partner'] = $nextData;
        
    //     return $partnersData;
    // }


    /* Partner data fetch part DELETE/{id} */
    public function deletePartner($id)
    {
        try {
            $partner = Partner::find($id);
    
            if (!$partner) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Partner not found'
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Delete partner image if it exists
            if ($partner->image && Storage::disk('public')->exists($partner->image)) {
                Storage::disk('public')->delete($partner->image);
            }
    
            // Delete associated translations
            $translations = PartnerTranslation::where('partner_id', $id)->get();
    
            foreach ($translations as $translation) {
                $fieldValues = json_decode($translation->field_values, true);
    
                // Delete social images from translation data
                if (isset($fieldValues['social'])) {
                    foreach ($fieldValues['social'] as $social) {
                        if (isset($social['image']) && Storage::disk('public')->exists($social['image'])) {
                            Storage::disk('public')->delete($social['image']);
                        }
                    }
                }
    
                // Delete translation record
                $translation->delete();
            }
    
            // Delete the partner record
            $partner->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Partner deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    // Partners list search function
    public function searchPartnersList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $partnerQuery = Partner::query()
            ->join('partner_translations', function ($join) use ($lang) {
                $join->on('partners.id', '=', 'partner_translations.partner_id')
                    ->where('partner_translations.language', '=', $lang);
            })
            ->select('partners.*', 'partner_translations.field_values');

            // Apply search filters for both slug and partner_title
            if (!empty($searchQuery)) {
                $partnerQuery->where(function ($query) use ($searchQuery) {
                    $query->where('partners.slug', 'LIKE', "%{$searchQuery}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(partner_translations.field_values, '$.partner_title')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $partnerQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $partners = $partnerQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $partners->count(), // All items in one "page"
                    'total' => $partners->count(),
                ];
            } else {
                // Paginate the remaining partners
                $partners = $partnerQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $partners->currentPage(),
                    'last_page' => $partners->lastPage(),
                    'per_page' => $partners->perPage(),
                    'total' => $partners->total(),
                ];
            }
    
            if ($partners->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No partners found'], 200);
            }
    
            $partners_translations = $partners->map(function ($partner) use ($lang) {
                $id = $partner->id;
                $translation = PartnerTranslation::where('partner_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->field_values, true);
                } else {
                    $defaultData = PartnerTranslation::where('partner_id', $id)
                        ->where('language', 'en')
                        ->first();
    
                    if (!empty($defaultData)) {
                        $translatedData = json_decode($defaultData->field_values, true);
                    }
                }
    
                $translatedData['id'] = $id;
                $translatedData['partner_status'] = (int) $partner->partner_status;
                $translatedData['partner_slug'] = $partner->slug;
                $translatedData['partner_image'] = $partner->image ? $this->getImageUrl($partner->image) : null;
    
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $partners_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
