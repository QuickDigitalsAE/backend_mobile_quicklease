<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use Illuminate\Http\Request;
use App\Models\Catalog;
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

class OurLocationsController extends Controller
{
    // Fetch all record list
    public function frontendLocationsList($lang)
    {
        try {
            $locationsFetch = Catalog::where('type','our_locations')
                                ->orderBy('created_at','ASC')
                                ->get();
            
            if($locationsFetch->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Data not found'], 200);
            }
           
            $groupedLocations = [];
            foreach ($locationsFetch as $catalog) {
                $id = $catalog->id;
                
                // Get translation based on language or default 'en' based
                $translationQuery = CatalogTranslation::where('catalog_id', $id)
                    ->where('language', $lang)
                    ->first();
                
                $catalogTitle = "";
                if($translationQuery){
                    $translatedData = json_decode($translationQuery->field_values, true);
                    $catalogTitle = $translatedData['catalog_title'] ?? null;
                }
            
                if (!empty($catalogTitle)) {
                     if($lang == 'ar'){
                        // For Arabic letters
                        $firstChar = mb_substr($catalogTitle, 0, 1, 'UTF-8');
                        $firstLetter = $this->normalizeArabicLetter(mb_strtolower($firstChar, 'UTF-8'));
                    }else{
                        $firstLetter = strtoupper(substr($catalogTitle, 0, 1));
                    }
                    
                    if (!isset($groupedLocations[$firstLetter])) {
                        $groupedLocations[$firstLetter] = [];
                    }
            
                    $groupedLocations[$firstLetter][] = [
                        'id' => $id,
                        'location_name' => $catalogTitle,
                        'slug' => $catalog->slug,
                    ];
                }
            }
            
            if ($lang === 'ar') {
                $arabicLetters = ['أ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
            
                // Remove non-Arabic letter groups
                $groupedLocations = array_filter($groupedLocations, function ($key) use ($arabicLetters) {
                    return in_array($key, $arabicLetters);
                }, ARRAY_FILTER_USE_KEY);
            
                // Sort as per Arabic letter order
                uksort($groupedLocations, function ($a, $b) use ($arabicLetters) {
                    return array_search($a, $arabicLetters) <=> array_search($b, $arabicLetters);
                });
            } else {
                // English or other language: sort A-Z
                ksort($groupedLocations);
            }
            
            // Fetch our location meta
            $webContentController = new WebContentController();
            $locationsMeta =  $webContentController->getWebMetaDeta('our-locations',$lang);
            
            if($locationsMeta->original['data']){
                $metaData = $locationsMeta->original['data'];
            }else{
                $metaData = [];
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
                        'our_locations' => $groupedLocations,
                        'meta' => $metaData
                    ],
                'elements' => $elements_data  
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    function normalizeArabicLetter($char) {
        $groups = [
            ['أ', 'ا', 'إ', 'آ'], // Alif variations
            ['ه', 'ة'],           // Heh and Teh Marbuta
            ['ي', 'ى', 'ئ'],      // Yeh variations
            ['و', 'ؤ'],           // Waw variations
            // Add more if needed
        ];
    
        foreach ($groups as $group) {
            if (in_array($char, $group)) {
                return $group[0]; // Return a standard form, like 'ا'
            }
        }
    
        return $char; // Return as-is if not in group
    }
}
