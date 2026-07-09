<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\PromotionController;
use App\Http\Controllers\ApisFiles\BlogController;
use App\Http\Controllers\ApisFiles\TestimonialController;
use App\Http\Controllers\ApisFiles\ProductsController;
use App\Http\Controllers\ApisFiles\CatalogController;
use App\Http\Controllers\ApisFiles\GoogleReviewController;
use Illuminate\Http\Request;
use App\Models\PeopleVisit;
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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;


class WebContentController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        // $this->middleware('permission:WebContents View', ['only' => ['getWebHomeContent', 'getWebAboutUsContent', 'getWebFaqs',
        //                                                             'getWebVideoTestimonials','getWebContactUs','getWebCorporateLeaseContent',
        //                                                             'getWebLeaseToOwnContent','getWebMeta','getWebProductInnerContent']]);
                                                                    
        $this->middleware('permission:WebContents Add', ['only' => ['createOrUpdateWebHome','createOrUpdateWebAboutUs','createUpdateFaqs',
                                                                    'createUpdateVideoTestimonials','createUpdateContactUs',
                                                                    'createOrUpdateWebCorporateLease','createOrUpdateWebLeaseToOwn',
                                                                    'createUpdateMetaData','createOrUpdateWebProductInner']]);
                                                                    
        $this->middleware('permission:WebContents Edit', ['only' => ['createOrUpdateWebHome','createOrUpdateWebAboutUs','createUpdateFaqs',
                                                                    'createUpdateVideoTestimonials','createUpdateContactUs',
                                                                    'createOrUpdateWebCorporateLease','createOrUpdateWebLeaseToOwn',
                                                                    'createUpdateMetaData','createOrUpdateWebProductInner']]);
        // $this->middleware('permission:WebContents Delete', ['only' => ['deleteTestimonial']]);
    }
    /* Home tab data fetch part GET */
    public function getWebHomeContent($lang)
    {
        try {
            $tranlateArray = array();
            $webContent = WebContent::where('slug','home')->first();

            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
            
            $translateArray['sec_three_image'] = $webContent->sec_three_image ? $this->getImageUrl($webContent->sec_three_image) : null;
            $translateArray['sec_six_image'] = $webContent->sec_six_image ? $this->getImageUrl($webContent->sec_six_image) : null;
            $translateArray['sec_seven_image'] = $webContent->sec_seven_image ? $this->getImageUrl($webContent->sec_seven_image) : null;
            
            
             // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $translateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_two'] = [];
            }
    
            // Process sec_five images
            if (isset($defaultTranslatedData['sec_five'])) {
                foreach ($defaultTranslatedData['sec_five'] as $index => $section) {
                    $translateArray['sec_five'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_five'] = [];
            }

            // Process sec_eight images
            if (isset($defaultTranslatedData['sec_eight'])) {
                foreach ($defaultTranslatedData['sec_eight'] as $index => $section) {
                    $translateArray['sec_eight'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_eight'] = [];
            }
            
            // Process sec_nine images
            if (isset($defaultTranslatedData['sec_nine'])) {
                foreach ($defaultTranslatedData['sec_nine'] as $index => $section) {
                    $translateArray['sec_nine'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_nine'] = [];
            }

            // Process banner
            if (isset($defaultTranslatedData['banner'])) {
                foreach ($defaultTranslatedData['banner'] as $index => $section) {
                    $translateArray['banner'][$index]['banner_image'] = $section['banner_image'] ? $this->getImageUrl($section['banner_image']) : null;
                    $translateArray['banner'][$index]['car_image'] = $section['car_image'] ? $this->getImageUrl($section['car_image']) : null;
                }
            }else{
                $translateArray['banner'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], Response::HTTP_OK);
            
        } catch (\Exception $ex) {
            
            Log::error('error_webcontent_get_function',$ex->getMessage());
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // fetch home page content
    public function fetchHomePageContent($lang)
    {
        try {
            $translateArray = array();
            $webContent = WebContent::where('slug','home')->first();

            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;

            $PeopleVisitdCount = PeopleVisit::getVisitCount('home');
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];

            $translateArray['people_visited'] = $PeopleVisitdCount;
            $translateArray['sec_three_image'] = $webContent->sec_three_image ? $this->getImageUrl($webContent->sec_three_image) : null;
            $translateArray['sec_six_image'] = $webContent->sec_six_image ? $this->getImageUrl($webContent->sec_six_image) : null;
            $translateArray['sec_seven_image'] = $webContent->sec_seven_image ? $this->getImageUrl($webContent->sec_seven_image) : null;
            
            
             // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $translateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_two'] = [];
            }
    
            // Process sec_five images
            if (isset($defaultTranslatedData['sec_five'])) {
                foreach ($defaultTranslatedData['sec_five'] as $index => $section) {
                    $translateArray['sec_five'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_five'] = [];
            }

            // Process sec_eight images
            if (isset($defaultTranslatedData['sec_eight'])) {
                foreach ($defaultTranslatedData['sec_eight'] as $index => $section) {
                    $translateArray['sec_eight'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_eight'] = [];
            }

            // Process sec_nine images
            if (isset($defaultTranslatedData['sec_nine'])) {
                foreach ($defaultTranslatedData['sec_nine'] as $index => $section) {
                    $translateArray['sec_nine'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_nine'] = [];
            }
            
            // Process banner
            if (isset($defaultTranslatedData['banner'])) {
                foreach ($defaultTranslatedData['banner'] as $index => $section) {
                    $translateArray['banner'][$index]['banner_image'] = $section['banner_image'] ? $this->getImageUrl($section['banner_image']) : null;
                    $translateArray['banner'][$index]['car_image'] = $section['car_image'] ? $this->getImageUrl($section['car_image']) : null;
                }
            }else{
                $translateArray['banner'] = [];
            }
            
            
            $promotionController = new PromotionController();
            $promotions =  $promotionController->frontendList($lang, 6);

            if ($promotions->original['data']) {
                $translateArray['promotions'] = $promotions->original['data']['all_promotions'];
            } else {
                $translateArray['promotions'] = [];
            }
            
            
            $BlogController = new BlogController();
            $blogsFetch =  $BlogController->blogsFrontendList($lang, 4);

            if ($blogsFetch->original['data']) {
                $translateArray['recent_blog'] = $blogsFetch->original['data']['recent_blog'];
                $translateArray['blogs'] = $blogsFetch->original['data']['all_blogs'];
            } else {
                $translateArray['recent_blog'] = [];
                $translateArray['blogs'] = [];
            }
            
            $TestimonitalController = new TestimonialController();
            $testimonialsFetch =  $TestimonitalController->frontendTestimonialsList($lang, 6);

            if ($testimonialsFetch->original['data']) {
                $translateArray['testimonials'] = $testimonialsFetch->original['data']['all_testimonials'];
            } else {
                $translateArray['testimonials'] = [];
            }
            
            $ProductsController = new ProductsController();
            $productsFetch =  $ProductsController->frontendProductsList($lang,1,0);

            if ($productsFetch->original['data']) {
                $translateArray['all_cars'] = $productsFetch->original['data'];
            } else {
                $translateArray['all_cars'] = [];
            }
            
            // Fetch product inner page content
            $productInnerDetails =  $this->getWebProductInnerContent($lang);
            
            $locations = [];
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $translateArray['locations'] = $productInnerData['locations'];
            }else{
                $translateArray['locations'] = [];
            }
            
            $fetchFaqsList = $this->fetchFaqsList($lang,8);
            
            if ($fetchFaqsList->original['data']) {
                $translateArray['faqs'] = $fetchFaqsList->original['data']['faqs'];
            } else {
                $translateArray['faqs'] = [];
            }

            $googleReviewController = new GoogleReviewController();
            $reviewsFetch =  $googleReviewController->getReview();

            if ($reviewsFetch->original['data']) {
                $translateArray['google_reviews'] = $reviewsFetch->original['data'];
            } else {
                $translateArray['google_reviews'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $ex) {
            
            Log::error('error_webcontent_get_function',$ex->getMessage());
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Home tab data insertion part POST */
    public function createOrUpdateWebHome(Request $request,$lang)
    {
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'home' content already exists
            $webContent = WebContent::where('slug', 'home')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'home';
            }
    
            // Handle image uploads for primary fields
            $imgFields = ['sec_three_image','sec_six_image','sec_seven_image'];
            foreach ($imgFields as $imgField) {
                if (!empty($request->$imgField)) {
                    // Delete old image if exists
                    if ($webContent->$imgField) {
                        Storage::disk('public')->delete($webContent->$imgField);
                    }
                    // Upload new image
                    $webContent->$imgField = $request->$imgField;
                }
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Process sec_one images
            if (isset($translation['sec_one'])) {
                foreach ($translation['sec_one'] as $index => $section) {
                    $imageKey = "translation.sec_one.$index.image";
                    
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_one'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        $translation['sec_one'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($translation['sec_two'])) {
                foreach ($translation['sec_two'] as $index => $section) {
                    $imageKey = "translation.sec_two.$index.image";
                    
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_two'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        $translation['sec_two'][$index]['image'] = $oldImagePath;
                    } 
                }
            }
            
            // Process sec_five images
            if (isset($translation['sec_five'])) {
                foreach ($translation['sec_five'] as $index => $section) {
                    $imageKey = "translation.sec_five.$index.image";

                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_five', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_five'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_five', 'image');
                        $translation['sec_five'][$index]['image'] = $oldImagePath;
                    } 
                }
            }else{
                $translation['sec_five'] = [];
            }


            // Process sec_eight images
            if (isset($translation['sec_eight'])) {
                foreach ($translation['sec_eight'] as $index => $section) {
                    $imageKey = "translation.sec_eight.$index.image";
                    
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_eight', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_eight'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_eight', 'image');
                        $translation['sec_eight'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_eight'] = [];
            }

            // Process sec_eight images
            if (isset($translation['sec_nine'])) {
                foreach ($translation['sec_nine'] as $index => $section) {
                    $imageKey = "translation.sec_nine.$index.image";
                    
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_nine', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_nine'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_nine', 'image');
                        $translation['sec_nine'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_nine'] = [];
            }
            
            // Process banner images
            if (isset($translation['banner'])) {
                foreach ($translation['banner'] as $index => $section) {
                    $bannerImageKey = "translation.banner.$index.banner_image";
                    $imageKey = "translation.banner.$index.car_image";
                    
                    if (!empty($request->$bannerImageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'banner', 'banner_image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['banner'][$index]['banner_image'] = $request->$bannerImageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'banner', 'banner_image');
                        $translation['banner'][$index]['banner_image'] = $oldImagePath;
                    }
                    
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldcarImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'banner', 'car_image');
                        if ($oldcarImagePath != null) {
                            Storage::disk('public')->delete($oldcarImagePath);
                        }
                        // Upload new image
                        $translation['banner'][$index]['car_image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'banner', 'car_image');
                        $translation['banner'][$index]['car_image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['banner'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            return response()->json(['status' => 'true', 'message' => 'Web home content saved or updated successfully'], 200);

        } catch (\Exception $ex) {
            Log::error('error_webcontent_store_function',$ex->getMessage());
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            // return $ex;
        }
    }
    
    public function createOrUpdateWebAboutUs(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'about-us' content already exists
            $webContent = WebContent::where('slug', 'about-us')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'about-us';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {    
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Process sec_one images
            if (isset($translation['sec_one'])) {
                foreach ($translation['sec_one'] as $index => $section) {
                    $imageKey = "translation.sec_one.$index.image";

                    if (!empty($request->$imageKey)) {        
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_one'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        $translation['sec_one'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($translation['sec_two'])) {
                foreach ($translation['sec_two'] as $index => $section) {
                    $imageKey = "translation.sec_two.$index.image";
                    
                    if (!empty($request->$imageKey)) {            
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_two'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        $translation['sec_two'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_two'] = [];
            }

            // Process sec_three images
            if (isset($translation['sec_three'])) {
                foreach ($translation['sec_three'] as $index => $section) {
                    $imageKey = "translation.sec_three.$index.image";
                    
                    if (!empty($request->$imageKey)) {            
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_three'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        $translation['sec_three'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_three'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web about-us content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebAboutUsContent($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'about-us')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
                
            // Decode JSON translations
            $tranlateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
            
            // Handle image URLs for primary fields
            $tranlateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $tranlateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $tranlateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_two'] = [];
            }

            // Process sec_three images
            if (isset($defaultTranslatedData['sec_three'])) {
                foreach ($defaultTranslatedData['sec_three'] as $index => $section) {
                    $tranlateArray['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_three'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $tranlateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchAboutUs($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'about-us')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;

            $PeopleVisitdCount = PeopleVisit::getVisitCount('about-us');
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $tranlateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];

            $tranlateArray['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $tranlateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $tranlateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $tranlateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_two'] = [];
            }

            // Process sec_three images
            if (isset($defaultTranslatedData['sec_three'])) {
                foreach ($defaultTranslatedData['sec_three'] as $index => $section) {
                    $tranlateArray['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_three'] = [];
            }
            
            $TestimonitalController = new TestimonialController();
            $testimonialsFetch =  $TestimonitalController->frontendTestimonialsList($lang, 6);

            if ($testimonialsFetch->original['data']) {
                $tranlateArray['testimonials'] = $testimonialsFetch->original['data']['all_testimonials'];
            } else {
                $tranlateArray['testimonials'] = [];
            }
            
            $googleReviewController = new GoogleReviewController();
            $reviewsFetch =  $googleReviewController->getReview();

            if ($reviewsFetch->original['data']) {
                $tranlateArray['google_reviews'] = $reviewsFetch->original['data'];
            } else {
                $tranlateArray['google_reviews'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $tranlateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createOrUpdateWebPartner(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'partners' content already exists
            $webContent = WebContent::where('slug', 'partners')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'partners';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {    
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Process sec_one images
            if (isset($translation['sec_one'])) {
                foreach ($translation['sec_one'] as $index => $section) {
                    $imageKey = "translation.sec_one.$index.image";

                    if (!empty($request->$imageKey)) {        
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_one'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        $translation['sec_one'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($translation['sec_two'])) {
                foreach ($translation['sec_two'] as $index => $section) {
                    $imageKey = "translation.sec_two.$index.image";
                    
                    if (!empty($request->$imageKey)) {            
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_two'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        $translation['sec_two'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_two'] = [];
            }
            
            // Process sec_three images
            if (isset($translation['sec_three'])) {
                foreach ($translation['sec_three'] as $index => $section) {
                    $imageKey = "translation.sec_three.$index.image";
                    
                    if (!empty($request->$imageKey)) {            
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_three'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        $translation['sec_three'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_three'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web partners content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebPartnerContent($lang)
    {
        try {
            // Fetch the 'partners' content
            $webContent = WebContent::where('slug', 'partners')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
                
            // Decode JSON translations
            $tranlateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
            
            // Handle image URLs for primary fields
            $tranlateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $tranlateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $tranlateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_two'] = [];
            }
            
            // Process sec_three images
            if (isset($defaultTranslatedData['sec_three'])) {
                foreach ($defaultTranslatedData['sec_three'] as $index => $section) {
                    $tranlateArray['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_three'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $tranlateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchPartners($lang)
    {
        try {
            // Fetch the 'partners' content
            $webContent = WebContent::where('slug', 'partners')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;

            $PeopleVisitdCount = PeopleVisit::getVisitCount('partners');
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $tranlateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];

            $tranlateArray['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $tranlateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $tranlateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $tranlateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_two'] = [];
            }
            
            // Process sec_three images
            if (isset($defaultTranslatedData['sec_three'])) {
                foreach ($defaultTranslatedData['sec_three'] as $index => $section) {
                    $tranlateArray['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $tranlateArray['sec_three'] = [];
            }
            
            $TestimonitalController = new TestimonialController();
            $testimonialsFetch =  $TestimonitalController->frontendTestimonialsList($lang, 6);

            if ($testimonialsFetch->original['data']) {
                $tranlateArray['testimonials'] = $testimonialsFetch->original['data']['all_testimonials'];
            } else {
                $tranlateArray['testimonials'] = [];
            }
            
            $googleReviewController = new GoogleReviewController();
            $reviewsFetch =  $googleReviewController->getReview();

            if ($reviewsFetch->original['data']) {
                $tranlateArray['google_reviews'] = $reviewsFetch->original['data'];
            } else {
                $tranlateArray['google_reviews'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $tranlateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function createUpdateFaqs(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'about-us' content already exists
            $webContent = WebContent::where('slug', 'faqs')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'faqs';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {    
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);

            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web faqs content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebFaqs($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'faqs')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Faqs content not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Fetch the translation for the given language
            $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();
                
             $translatedData = []; 
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->translated_value, true);
            }else{
                // For Defualt Language Data Fetch
                $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                if (!empty($defaultData)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($defaultData->translated_value, true);
                }    
            }
    
            // Handle image URLs for primary fields
            $translatedData['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchFaqsList($lang, $per_page = 6)
    {
        try {
            // Fetch the 'faqs' content
            $webContent = WebContent::where('slug', 'faqs')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Faqs content not found'], Response::HTTP_NOT_FOUND);
            }

            $PeopleVisitdCount = PeopleVisit::getVisitCount('faqs');
    
            // Fetch the translation for the given language
            $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();
                
            $translatedData = []; 
            if (!empty($translation)) {
                $translatedData = json_decode($translation->translated_value, true);
            } else {
                // Fetch default language (English)
                $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                if (!empty($defaultData)) {
                    $translatedData = json_decode($defaultData->translated_value, true);
                }    
            }

            $translatedData['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $translatedData['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Check if FAQs exist
            if (!empty($translatedData['faqs'])) {
                $faqs = collect($translatedData['faqs'])->reverse()->values();
    
                // Check if full list is requested (per_page = 0)
                if ($per_page == 0) {
                    $paginatedFaqs = $faqs;
                    $pagination = [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $faqs->count(), // All items on one page
                        'total' => $faqs->count(),
                    ];
                } else {
                    // Apply manual pagination
                    $currentPage = request()->get('page', 1);
                    $offset = ($currentPage - 1) * $per_page;
                    $paginatedFaqs = $faqs->slice($offset, $per_page)->values();
    
                    // Pagination metadata
                    $pagination = [
                        'current_page' => (int) $currentPage,
                        'last_page' => ceil($faqs->count() / $per_page),
                        'per_page' => (int) $per_page,
                        'total' => $faqs->count(),
                    ];
                }
    
                // Replace the original `faqs` with paginated data
                $translatedData['faqs'] = $paginatedFaqs;
                $translatedData['pagination'] = $pagination;
            }
    
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createUpdateVideoTestimonials(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'about-us' content already exists
            $webContent = WebContent::where('slug', 'video-testimonial')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'video-testimonial';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {   
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);

            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Testimonials videos saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebVideoTestimonials($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'video-testimonial')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Videos not found'], Response::HTTP_NOT_FOUND);
            }

            $PeopleVisitdCount = PeopleVisit::getVisitCount('video-testimonial');
    
            // Fetch the translation for the given language
            $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();
                
             $translatedData = []; 
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->translated_value, true);
            }else{
                // For Defualt Language Data Fetch
                $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                if (!empty($defaultData)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($defaultData->translated_value, true);
                }    
            }

            $translatedData['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $translatedData['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function createUpdateContactUs(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'about-us' content already exists
            $webContent = WebContent::where('slug', 'contact-us')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'contact-us';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {   
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
            
             // Process social images
            if (isset($translation['social'])) {
                foreach ($translation['social'] as $index => $section) {
                    $imageKey = "translation.social.$index.image";
                    
                    if (!empty($request->$imageKey)) {       
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'social', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['social'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'social', 'image');
                        $translation['social'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['social'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web contact us content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebContactUs($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'contact-us')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
            
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process social images
            if (isset($defaultTranslatedData['social'])) {
                foreach ($defaultTranslatedData['social'] as $index => $section) {
                    $translateArray['social'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['social'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function createOrUpdateWebCorporateLease(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'corporate-lease' content already exists
            $webContent = WebContent::where('slug', 'corporate-lease')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'corporate-lease';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {           
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            
            // Handle image uploads for primary fields
            if (!empty($request->sec_one_image)) {           
                // Delete old image if exists
                if ($webContent->sec_one_image) {
                    Storage::disk('public')->delete($webContent->sec_one_image);
                }
                // Upload new image
                $webContent->sec_one_image = $request->sec_one_image;
            }
            
            $filtered_cars_ids = [];
            if ($request->has('car_ids')) {
                $car_ids = $request->input('car_ids', []); // Default to an empty array if null
                if (is_array($car_ids)) {
                    $filtered_cars_ids = array_filter($car_ids); // Remove empty values
                }
            }
            
            $webContent->car_ids = !empty($filtered_cars_ids) ? json_encode($filtered_cars_ids) : null;
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Process sec_one images
            if (isset($translation['sec_one'])) {
                foreach ($translation['sec_one'] as $index => $section) {
                    $imageKey = "translation.sec_one.$index.image";
                    if (!empty($request->$imageKey)) {           
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_one'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        $translation['sec_one'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($translation['sec_two'])) {
                foreach ($translation['sec_two'] as $index => $section) {
                    $imageKey = "translation.sec_two.$index.image";
                    if (!empty($request->$imageKey)) {           
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_two'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        $translation['sec_two'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_two'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web corporate-lease content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebCorporateLeaseContent($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'corporate-lease')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
    
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            $translateArray['sec_one_image'] = $webContent->sec_one_image ? $this->getImageUrl($webContent->sec_one_image) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $translateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_two'] = [];
            }
            
            // Handle car_ids count
            $carIds = json_decode($webContent->car_ids, true); // Decode JSON
            
            // Fetch Product List 
            $ProductsController = new ProductsController();
            $productListFetch =  $ProductsController->frontendProductsList($lang,0,0);
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);
            
            if($productListFetch->original['data']){
                $carsList = $productListFetch->original['data'];
                
                $translateArray['all_cars'] = $carsList;
            }else{
                $translateArray['all_cars'] = [];
            }
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translateArray['related_cars'] = $catalogCarsList;
            }else{
                $translateArray['related_cars'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchCorporateLease($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'corporate-lease')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;

            $PeopleVisitdCount = PeopleVisit::getVisitCount('corporate-lease');
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];

            $translateArray['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            $translateArray['sec_one_image'] = $webContent->sec_one_image ? $this->getImageUrl($webContent->sec_one_image) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $translateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_one'] = [];
            }
            
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_two'] = [];
            }
            
            // Handle car_ids count
            $carIds = json_decode($webContent->car_ids, true); // Decode JSON
            
            // Fetch Product List 
            $ProductsController = new ProductsController();
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translateArray['related_cars'] = $catalogCarsList;
            }else{
                $translateArray['related_cars'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function createOrUpdateWebLeaseToOwn(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'corporate-lease' content already exists
            $webContent = WebContent::where('slug', 'lease-to-own-page')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'lease-to-own-page';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {           
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            
            // Handle image uploads for primary fields
            if (!empty($request->sec_one_image)) {           
                // Delete old image if exists
                if ($webContent->sec_one_image) {
                    Storage::disk('public')->delete($webContent->sec_one_image);
                }
                // Upload new image
                $webContent->sec_one_image = $request->sec_one_image;
            }
            
            $filtered_cars_ids = [];
            if ($request->has('car_ids')) {
                $car_ids = $request->input('car_ids', []); // Default to an empty array if null
                if (is_array($car_ids)) {
                    $filtered_cars_ids = array_filter($car_ids); // Remove empty values
                }
            }
            
            $webContent->car_ids = !empty($filtered_cars_ids) ? json_encode($filtered_cars_ids) : null;
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web lease-to-own-page content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebLeaseToOwnContent($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'lease-to-own-page')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
    
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            $translateArray['sec_one_image'] = $webContent->sec_one_image ? $this->getImageUrl($webContent->sec_one_image) : null;
    
            // Process banner images
            if (isset($defaultTranslatedData['slider_banner'])) {
                foreach ($defaultTranslatedData['slider_banner'] as $index => $section) {
                    $translateArray['slider_banner'][$index]['slider_image'] = $section['slider_image'] ? $this->getImageUrl($section['slider_image']) : null;
                }
            }
            
            // Handle car_ids count
            $carIds = json_decode($webContent->car_ids, true); // Decode JSON
            
            // Fetch Product List 
            $ProductsController = new ProductsController();
            $productListFetch =  $ProductsController->frontendProductsList($lang,0,0);
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);
            
            if($productListFetch->original['data']){
                $carsList = $productListFetch->original['data'];
                
                $translateArray['all_cars'] = $carsList;
            }else{
                $translateArray['all_cars'] = [];
            }
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translateArray['related_cars'] = $catalogCarsList;
            }else{
                $translateArray['related_cars'] = [];
            }

            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchLeaseToOwnContent($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'lease-to-own-page')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;

            $PeopleVisitdCount = PeopleVisit::getVisitCount('lease-to-own-page');
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];

            $translateArray['people_visited'] = $PeopleVisitdCount;
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            $translateArray['sec_one_image'] = $webContent->sec_one_image ? $this->getImageUrl($webContent->sec_one_image) : null;
    
            // Process banner images
            if (isset($defaultTranslatedData['slider_banner'])) {
                foreach ($defaultTranslatedData['slider_banner'] as $index => $section) {
                    $translateArray['slider_banner'][$index]['slider_image'] = $section['slider_image'] ? $this->getImageUrl($section['slider_image']) : null;
                }
            }
            
            // Handle car_ids count
            $carIds = json_decode($webContent->car_ids, true); // Decode JSON
            
            // Fetch Product List 
            $ProductsController = new ProductsController();
            $catalogCarsFetch =  $ProductsController->allCarsDropdownList($lang, $carIds);
            
            if($catalogCarsFetch->original['data']){
                $catalogCarsList = $catalogCarsFetch->original['data'];
                
                $translateArray['related_cars'] = $catalogCarsList;
            }else{
                $translateArray['related_cars'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function removeInnerObject(Request $request, $lang)
    {
        // Validate incoming request
        $rules = [
            'slug' => 'required|string',
            'section' => 'required|string',
            'index' => 'required|integer|min:0',
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
    
        DB::beginTransaction(); // Start transaction
    
        try {
            // Fetch service and related translations
            $slug = $request->slug;
            $section = $request->section;
            $indexToRemove = $request->index;
            
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', $slug)->first();
            
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => $slug . ' content not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Fetch the translation for the given language
            $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();
            
            if (!$translation) {
                return response()->json(['status' => 'false', 'message' => 'No translation data found!'], 404);
            }
            
            // Decode the translation data
            $translatedData = json_decode($translation->translated_value, true);
            
            if (!is_array($translatedData) || !isset($translatedData[$section]) || !isset($translatedData[$section][$indexToRemove])) {
                return response()->json(['status' => 'false', 'message' => 'Index not found in the section!'], 400);
            }
            
            // Handle image deletion for 'banner' section
            if ($slug === 'home' && $section === 'banner') {
                $itemToRemove = $translatedData[$section][$indexToRemove];
                
                // Delete banner_image
                if (!empty($itemToRemove['banner_image'])) {
                    Storage::disk('public')->delete($itemToRemove['banner_image']); // Deletes the banner image
                }
                
                // Delete car_image
                if (!empty($itemToRemove['car_image'])) {
                    Storage::disk('public')->delete($itemToRemove['car_image']); // Deletes the car image
                }
            }
            
            // Remove the object at the specified index
            unset($translatedData[$section][$indexToRemove]);
            
            // Re-index the array to maintain proper numeric keys
            $translatedData[$section] = array_values($translatedData[$section]);
            
            // Encode back to JSON and save the updated translation
            $translation->translated_value = json_encode($translatedData);
            $translation->save();
            
            DB::commit(); // Commit the transaction
            
            return response()->json(['status' => 'true', 'message' => 'Item removed successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction on error
            
            return response()->json(['status' => 'false', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
     /* ####### Web content all inner pages section meta fields ########## */
    /* ############################################################################## */
    public function createUpdateMetaData(Request $request, $slug, $lang)
    {
        DB::beginTransaction(); // Start transaction
        
        try {
            $validator = Validator::make($request->all(), [
                "translation" => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $user = Auth::user();
            $userId = $user->id;
            // Check if content already exists
            $webContent = WebContent::where('slug', $slug)->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = $slug;
            }
    
            // Handle image uploads for primary fields
            
            if (!empty($request->banner)) {           
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            
            $webContent->created_by = $userId;
            $webContent->save();


            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction

            return response()->json(['status' => 'true', 'message' => $slug.' content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWebMeta($slug, $lang)
    {
        try {
            $webContent = WebContent::where('slug', $slug)->first();
            $dataArray = array();
            
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found', 'data' => []], Response::HTTP_NOT_FOUND);
            } else {
                // Fetch the translation for the given language
                $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', $lang)
                    ->first();
                    
                 $catalogMetaData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->translated_value, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->translated_value, true);
                    }    
                }
        
                // Handle image URLs for primary fields
                $catalogMetaData['meta_title'] = $translatedData['meta_title'];
                $catalogMetaData['meta_description'] = $translatedData['meta_description'];
                
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $catalogMetaData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWebMetaDeta($slug, $lang)
    {
        try {
            $webContent = WebContent::where('slug', $slug)->first();
            $dataArray = array();
            
            // return $webContent;

            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found', 'data' => []], Response::HTTP_NOT_FOUND);
            } else {
                // Fetch the translation for the given language
                $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', $lang)
                    ->first();
                    
                 $translatedData = []; 
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->translated_value, true);
                }else{
                    // For Defualt Language Data Fetch
                    $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                        ->where('language', 'en')
                        ->first();
                        
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->translated_value, true);
                    }    
                }
        
                // Handle image URLs for primary fields
                $translatedData['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
                $translatedData['slug'] = $webContent->slug;
                
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function createOrUpdateElements(Request $request, $lang)
    {
        try {
             $user = Auth::user();
            $userId = $user->id;
            // Check if 'about-us' content already exists
            $webContent = WebContent::where('slug', 'elements')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'elements';
            }
    
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);

            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            return response()->json([
                "status" => true,
                "message" => "Element data created or updated successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
     /* ####### product inner pages section ########## */
    public function createOrUpdateWebProductInner(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'product-inner-page' content already exists
            $webContent = WebContent::where('slug', 'product-inner')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'product-inner';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {   
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
            
             // Process social images
            if (isset($translation['document_requirements'])) {
                foreach ($translation['document_requirements'] as $index => $section) {
                    $imageKey = "translation.document_requirements.$index.image";
                    
                    if (!empty($request->$imageKey)) {       
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'document_requirements', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['document_requirements'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'document_requirements', 'image');
                        $translation['document_requirements'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['document_requirements'] = [];
            }
            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContentId],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Product inner page content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebProductInnerContent($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'product-inner')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found', 'data' => [] ], Response::HTTP_NOT_FOUND);
            }
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $webContent->id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
    
            // Handle image URLs for primary fields
            $translatedData['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
            
            if (isset($defaultTranslatedData['locations'])) {
                foreach ($defaultTranslatedData['locations'] as $index => $section) {
                    $translatedData['locations'][$section] = $translatedData['locations'][$index] ?? "";
                    unset($translatedData['locations'][$index]);
                }
            }
            
            // Process document_requirements images
            if (isset($defaultTranslatedData['document_requirements'])) {
                foreach ($defaultTranslatedData['document_requirements'] as $index => $section) {
                    $translatedData['document_requirements'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translatedData['document_requirements'] = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // get elements by language
    public function getWebElements($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'elements')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Elements content not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Fetch the translation for the given language
            $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', $lang)
                ->first();
                
             $translatedData = []; 
            if (!empty($translation)) {
                // Decode the JSON translation data
                $translatedData = json_decode($translation->translated_value, true);
            }else{
                // For Defualt Language Data Fetch
                $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                if (!empty($defaultData)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($defaultData->translated_value, true);
                }    
            }
    
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function combineContent($lang)
    {
        try {
            
            $dataArray = array();
            
            // Fetch Contact Us Function
            $contactus = $this->getWebContactUs($lang);
    
            if($contactus->original['data']){
                $dataArray['contact_us'] = $contactus->original['data'];
            }else{
                $dataArray['contact_us'] = [];
            }
            
            
            // Fetch Function
            $CatalogController = new CatalogController();

            $car_brands = $CatalogController->catalogsMenuList($lang, 'car_brands', '', 0, null);
            
            if($car_brands->original['data']){
                $brandsList = $car_brands->original['data'];
                
                $dataArray['brands_list'] = $brandsList;
            }else{
                $dataArray['brands_list'] = [];
            }
            
            // Fetch product inner page content
            $productInnerDetails =  $this->getWebProductInnerContent($lang);
            
            $locations = [];
            if($productInnerDetails->original['data']){
                $productInnerData = $productInnerDetails->original['data'];
                $dataArray['locations'] = $productInnerData['locations'];
            }else{
                $dataArray['locations'] = "";
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $dataArray
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* #################################### */
    protected function getOldImagePath($lang, $webContentId, $index, $section, $name)
    {
        // Retrieve the web content translation entry for the current language and web content ID
        $webContentTranslation = WebContentTranslation::where('language', $lang)
            ->where('web_content_id', $webContentId)
            ->first();
    
        // Check if the translation exists
        if (!$webContentTranslation) {
            return null; // or handle the case where the translation is not found
        }
    
        // Decode the JSON stored in the 'translated_value' field
        $oldTranslation = json_decode($webContentTranslation->translated_value, true);
    
        // Check if the section exists and return the appropriate path
        if (isset($oldTranslation[$section])) {
            // Handle sec_six and similar sections where the image is nested in an array of objects
            if (isset($oldTranslation[$section][$index])) {
                return $oldTranslation[$section][$index][$name] ?? null;
            }
        }
    
        return null;
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
    
    public function subscriptionEmail(Request $request)
    {

        try {
            $email = $request["email"];
            $data = [
                'email' => $email,
                'user' => explode('@', $request["email"])[0]
            ];

            Mail::send('emails.subscribe', $data, function ($message) use ($email) {
                $message->to($email)->subject('Subscription Successful');
            });

            // if (Mail::failures()) {
            //     return response()->json(['status' => false, 'message' => 'Email not sent.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            // }

            return response()->json(['status' => true, 'message' => 'Subscription Successful!'], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json(['status' => false, 'message' => 'An error occurred: ' . $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createOrUpdateWebFlexibleRental(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $user = Auth::user();
            $userId = $user->id;
            // Check if 'corporate-lease' content already exists
            $webContent = WebContent::where('slug', 'flexible-rentals')->first();
            
            if (!$webContent) {
                // If not found, create a new record
                $webContent = new WebContent();
                $webContent->slug = 'flexible-rentals';
            }
    
            // Handle image uploads for primary fields
            if (!empty($request->banner)) {           
                // Delete old image if exists
                if ($webContent->banner) {
                    Storage::disk('public')->delete($webContent->banner);
                }
                // Upload new image
                $webContent->banner = $request->banner;
            }
            
            $webContent->created_by = $userId;
            $webContent->save(); // Save the updated or newly created primary content
            
            $webContentId = $webContent->id;
            $translation = $request->input('translation', []);
           
            // Process sec_one images
            if (isset($translation['sec_one'])) {
                foreach ($translation['sec_one'] as $index => $section) {
                    $imageKey = "translation.sec_one.$index.image";
                    if (!empty($request->$imageKey)) {           
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_one'][$index]['image'] = $request->$imageKey;
                    }else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_one', 'image');
                        $translation['sec_one'][$index]['image'] = $oldImagePath;
                    }
                }
            }else{
                $translation['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($translation['sec_two'])) {
                foreach ($translation['sec_two'] as $index => $section) {
                    $imageKey = "translation.sec_two.$index.image";
                    if (!empty($request->$imageKey)) {
                        // Delete old image if it exists
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        // Upload new image
                        $translation['sec_two'][$index]['image'] = $request->$imageKey;
                    } else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_two', 'image');
                        $translation['sec_two'][$index]['image'] = $oldImagePath;
                    }
                }
            } else {
                $translation['sec_two'] = [];
            }
            
            // Process sec_three images
            if (isset($translation['sec_three'])) {
                foreach ($translation['sec_three'] as $index => $section) {
                    $imageKey = "translation.sec_three.$index.image";
                    if (!empty($request->$imageKey)) {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        $translation['sec_three'][$index]['image'] = $request->$imageKey;
                    } else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_three', 'image');
                        $translation['sec_three'][$index]['image'] = $oldImagePath;
                    }
                }
            } else {
                $translation['sec_three'] = [];
            }
            
            // Process sec_four section images
            if (isset($translation['sec_four'])) {
                foreach ($translation['sec_four'] as $index => $section) {
                    $imageKey = "translation.sec_four.$index.image";
                    if (!empty($request->$imageKey)) {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_four', 'image');
                        if ($oldImagePath != null) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                        $translation['sec_four'][$index]['image'] = $request->$imageKey;
                    } else {
                        $oldImagePath = $this->getOldImagePath($lang, $webContentId, $index, 'sec_four', 'image');
                        $translation['sec_four'][$index]['image'] = $oldImagePath;
                    }
                }
            } else {
                $translation['sec_four'] = [];
            }

            
            // Update or create web_content_translation entry
            WebContentTranslation::updateOrCreate(
                ['language' => $lang, 'web_content_id' => $webContent->id],
                ['translated_value' => json_encode($translation)]
            );
    
            DB::commit(); // Commit transaction
    
            return response()->json(['status' => 'true', 'message' => 'Web flexible-rentals content saved or updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getWebFlexibleRental($lang)
    {
        try {
            // Fetch the 'about-us' content
            $webContent = WebContent::where('slug', 'flexible-rentals')->first();
    
            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translateArray = isset($translations[$lang]) ? json_decode($translations[$lang]->translated_value, true) : json_decode($translations['en']->translated_value, true);
            $defaultTranslatedData = isset($translations['en']) ? json_decode($translations['en']->translated_value, true) : [];
    
            // Handle image URLs for primary fields
            $translateArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
    
            // Process sec_one images
            if (isset($defaultTranslatedData['sec_one'])) {
                foreach ($defaultTranslatedData['sec_one'] as $index => $section) {
                    $translateArray['sec_one'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_one'] = [];
            }
            
            // Process sec_two images
            if (isset($defaultTranslatedData['sec_two'])) {
                foreach ($defaultTranslatedData['sec_two'] as $index => $section) {
                    $translateArray['sec_two'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_two'] = [];
            }
            
            // Process sec_three images
            if (isset($defaultTranslatedData['sec_three'])) {
                foreach ($defaultTranslatedData['sec_three'] as $index => $section) {
                    $translateArray['sec_three'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_three'] = [];
            }
            
            // Process sec_four images
            if (isset($defaultTranslatedData['sec_four'])) {
                foreach ($defaultTranslatedData['sec_four'] as $index => $section) {
                    $translateArray['sec_four'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                }
            }else{
                $translateArray['sec_four'] = [];
            }
            

            return response()->json([
                'status' => 'true',
                'data' => $translateArray
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* ####### Mobile app related function ########## */
    /* ############################################################################## */
    
    public function getDashboard()
    {
        try {
            $translateArray = array();
            $webContent = WebContent::where('slug','home')->first();

            if (!$webContent) {
                return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            }
            
            $page_id = $webContent->id;
            
            // Get translation based on language or default 'en' based
            $translations = WebContentTranslation::where('web_content_id', $page_id)
                            ->where('language', 'en')
                            ->first();
            
            // Decode JSON translations
            $defaultTranslatedData =  json_decode($translations->translated_value, true);
            
            $dataArray = [];
            // Process banner
            if (isset($defaultTranslatedData['banner'])) {
            
                $dataArray['slider'] = [];
            
                // First custom mobile banner object
                $dataArray['slider'][] = [
                    'title' => 'mobile banner 1',
                    'description' => 'mobile banner 1',
                    'banner_image' => 'home_banner_images/mobile_banner_1.webp',
                    'car_image' => $this->getImageUrl('home_banner_images/mobile_banner_1.webp'),
                    'image' => $this->getImageUrl('home_banner_images/mobile_banner_1.webp'),
                ];
            
                // Existing sliders
                foreach ($defaultTranslatedData['banner'] as $index => $section) {
            
                    $dataArray['slider'][] = [
                        'title' => $section['title'] ?? '',
                        'description' => $section['description'] ?? '',
                        'banner_image' => $section['banner_image'] ?? null,
                        'car_image' => $section['car_image']
                            ? $this->getImageUrl($section['car_image'])
                            : null,
                        'image' => $section['banner_image']
                            ? $this->getImageUrl($section['banner_image'])
                            : null,
                    ];
                }
            
            }else{
                $dataArray['slider'] = [];
            }
            
            // Fetch product inner page content
            $productInnerContent = WebContent::where('slug', 'product-inner')->first();
    
            if ($productInnerContent) {
                // Get translation based on language or default 'en' based
                $translations = WebContentTranslation::where('web_content_id', $productInnerContent->id)
                                    ->where('language', 'en')
                                    ->first();
                
                // Decode JSON translations
                $defaultTranslatedData = json_decode($translations->translated_value, true);;
        
            
                if (isset($defaultTranslatedData['locations'])) {
                    $dataArray['locations'] = $defaultTranslatedData['locations'];
                }else{
                    $dataArray['locations'] = [];
                }
            }
            
            
            // Fetch Function
            $CatalogController = new CatalogController();

            $car_brands = $CatalogController->catalogsMenuList('en', 'car_brands', '', 1, null);
            
            if($car_brands->original['data']){
                $brandsList = $car_brands->original['data'];
                
                $dataArray['brands'] = $brandsList;
            }else{
                $dataArray['brands'] = [];
            }
            
            $ProductsController = new ProductsController();
            $productsFetch =  $ProductsController->mobileProductsList('en', 1, 0);

            if ($productsFetch->original['data']) {
                $dataArray['cars'] = $productsFetch->original['data'];
            } else {
                $dataArray['cars'] = [];
            }
            
            return response()->json([
                'status' => true,
                'message' => "Dashboard content fetched successfully",
                'data' => $dataArray
            ], 200);
            
        } catch (\Exception $ex) {
            
            return response()->json(['status' => false, 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getFaqs()
    {
        try {
            // Fetch the 'faqs' content
            $webContent = WebContent::where('slug', 'faqs')->first();
    
            if (!$webContent) {
                return response()->json(['status' => false, 'message' => 'Faqs content not found'], Response::HTTP_NOT_FOUND);
            }
    
            $translatedData = []; 
            
            // Fetch default language (English)
            $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                ->where('language', 'en')
                ->first();
             
            $translatedData = json_decode($defaultData->translated_value, true);
            
            $dataFaqs = "";
            // Check if FAQs exist
            if (!empty($translatedData['faqs'])) {
                $faqs = collect($translatedData['faqs']); // Convert to Laravel Collection
    
                // Replace the original `faqs` with paginated data
                $dataFaqs = $faqs;
            }
    
            return response()->json([
                'status' => true,
                'message' => 'FAQs fetched successfully.',
                'data' => $dataFaqs
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getPage($slug)
    {
        try {
            $webContent = WebContent::where('slug', $slug)->first();
         
            if (!$webContent) {
                return response()->json(['status' => false, 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            } else {
                 $dataArray = []; 
                // For Defualt Language Data Fetch
                $defaultData = WebContentTranslation::where('web_content_id', $webContent->id)
                    ->where('language', 'en')
                    ->first();
                    
                // Decode the JSON translation data
                $dataArray = json_decode($defaultData->translated_value, true);
                
        
                // Handle image URLs for primary fields
                $dataArray['banner'] = $webContent->banner ? $this->getImageUrl($webContent->banner) : null;
                $dataArray['slug'] = $webContent->slug;
                
                // Process social images
                if (isset($dataArray['social'])) {
                    foreach ($dataArray['social'] as $index => $section) {
                        $dataArray['social'][$index]['image'] = $section['image'] ? $this->getImageUrl($section['image']) : null;
                    }
                }
                
                return response()->json([
                    'status' => true,
                    'message' => 'Page content fetched successfully.',
                    'data' => $dataArray
                ], Response::HTTP_OK);
            }
            
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
