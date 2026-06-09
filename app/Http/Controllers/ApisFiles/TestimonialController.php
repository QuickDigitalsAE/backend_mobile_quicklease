<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\ProductsController;
use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\TestimonialTranslation;
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
use Illuminate\Support\Facades\Mail;
use App\Mail\TestimonialMail;
use Carbon\Carbon;

class TestimonialController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Testimonial View', ['only' => ['testimonialsList', 'editTestimonial', 'searchTestimonialsList']]);
        $this->middleware('permission:Testimonial Add', ['only' => ['storeTestimonial']]);
        $this->middleware('permission:Testimonial Edit', ['only' => ['updateTestimonial']]);
        $this->middleware('permission:Testimonial Delete', ['only' => ['deleteTestimonial']]);
    }
    
    // Get all testimonials list
    public function testimonialsList($lang, $per_page = 6)
    {
        try {
            // Retrieve testimonials filtered by language
            $testimonialsQuery = Testimonial::orderBy('created_at', 'desc');
            
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $testimonials = $testimonialsQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $testimonials->count(), // All items in one "page"
                    'total' => $testimonials->count(),
                ];
            } else {
                // Paginate the remaining record
                $testimonials = $testimonialsQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $testimonials->currentPage(),
                    'last_page' => $testimonials->lastPage(),
                    'per_page' => $testimonials->perPage(),
                    'total' => $testimonials->total(),
                ];
            }
            
            // Check if testimonials exist
            if ($testimonials->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No testimonials found for the selected language.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Format the data
            $formattedTestimonials = $testimonials->map(function ($testimonial) use ($lang)  {
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
    
            return response()->json([
                'status' => 'true',
                'message' => __('Testimonials retrieved successfully.'),
                'data' => $formattedTestimonials,
                'pagination' => $pagination
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function frontendTestimonialsList($lang, $per_page = 6)
    {
        try {
            $clientId = request()->input('client_id');

            $testimonialsQuery = Testimonial::orderBy('created_at', 'desc');
            
            // Agar client_id pass ho
            if (!empty($clientId)) {
                $testimonialsQuery->where('client_id', $clientId);
            } else {
                // warna sirf active testimonials
                $testimonialsQuery->where('testimonial_status', 1);
            }
            
            $perPage = request()->input('per_page', $per_page);

            // page MUST come from query string
            $page = (int) request()->input('page', 1);

            if ($perPage === 0) {
                $testimonials = $testimonialsQuery->get();

                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $testimonials->count(),
                    'total' => $testimonials->count(),
                ];
            } else {
                $testimonials = $testimonialsQuery->paginate($perPage, ['*'], 'page', $page);

                $pagination = [
                    'current_page' => $testimonials->currentPage(),
                    'last_page' => $testimonials->lastPage(),
                    'per_page' => $testimonials->perPage(),
                    'total' => $testimonials->total(),
                ];
            }
    
            if ($testimonials->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __('No testimonials found for the selected language.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Format testimonials
            $formattedTestimonials = $testimonials->map(function ($testimonial) use ($lang) {
                $id = $testimonial->id;
    
                $translation = TestimonialTranslation::where('testimonial_id', $id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->first();
    
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
    
                $car_title = $product_slug = "";
    
                // Only fetch car info if car_id exists
                if (!empty($testimonial->car_id) && $testimonial->car_id != 0) {
                    $product = Product::find($testimonial->car_id);
                    if ($product) {
                        $product_slug = $product->slug;
                        $carTranslation = ProductTranslation::where('product_id', $testimonial->car_id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $carTranslatedData = !empty($carTranslation) ? json_decode($carTranslation->field_values, true) : [];
                        $car_title = $carTranslatedData['product_title'] ?? '';
                    }
                }
    
                $translatedData['id'] = $id;
                $translatedData['client_email'] = $testimonial->client_email;
                $translatedData['client_phone'] = $testimonial->client_phone;
                $translatedData['client_image'] = $testimonial->client_image ? $this->getImageUrl($testimonial->client_image) : null;
                $translatedData['car_title'] = $car_title;
                $translatedData['product_slug'] = $product_slug;
                $translatedData['stars'] = $testimonial->stars ?? null; // Set stars
                $translatedData['testimonial_status'] = $testimonial->testimonial_status ?? null; // Set testimonial status
                $translatedData['created_at'] = $testimonial->created_at->format('Y-m-d');
    
                return $translatedData;
            });
    
            // Fetch meta and elements
            $webContentController = new WebContentController();
            $testimonialsMeta = $webContentController->getWebMetaDeta('testimonials', $lang);
            $metaData = $testimonialsMeta->original['data'] ?? [];

            return response()->json([
                'status' => true,
                'message' => __('Testimonials retrieved successfully.'),
                'data' => [
                    'all_testimonials' => $formattedTestimonials,
                    'meta' => $metaData
                ],
                'pagination' => $pagination
            ], 200);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Store testimonial data insertion part POST/{lang} */
    public function storeTestimonial(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'client_email'  => 'required|string|email',
                'client_phone'  => 'required|string',
                'car_id' => 'nullable|numeric',
                'stars' => 'nullable|integer|min:0|max:5',
                'testimonial_status' => 'required|numeric',
                'translation' => 'required|array',
                'translation.client_name' => 'required|string',
                'translation.client_review' => 'required|string'
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => false,
                    'message' => $errorMessages,
                    'data' => null
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{

                $testimonial = new Testimonial();
               
                // Handle brand logo uploads
                if (!empty($request->client_image)) {
                    // Upload new banner image
                    $testimonial->client_image = $request->client_image;
                }
                
                // OPTIONAL client_id
                if ($request->filled('client_id')) {
                    $testimonial->client_id = $request->client_id;
                }

                
                $recipientEmail = $request->client_email;
                $client_email = $request->client_email;
                $client_phone = $request->client_phone;
                
                // car_id setup
                $car_id = ($request->car_id && $request->car_id != 0)
                    ? $request->car_id
                    : null;
                $stars = $request->stars ?? 0;
                
                $testimonial->stars = $stars;
                $testimonial->client_email = $client_email;
                $testimonial->client_phone = $client_phone;
                $testimonial->car_id = $car_id;
                $testimonial->testimonial_status = $request->testimonial_status;
                $testimonial->save();
                
                
                $testimonialId = $testimonial->id;
                $translations = $request->input('translation', []);
                $client_name = $translations['client_name'];
                $client_review = $translations['client_review'];
                
                $testimonial_translation = new TestimonialTranslation();
                $testimonial_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $testimonial_translation->language = $lang;
                $testimonial_translation->testimonial_id = $testimonialId;
                $testimonial_translation->save();
                
                
                DB::commit(); // Commit transaction
                
                // Fetch translation for the given language with optimized query
                $productTranslation = ProductTranslation::where('product_id', $car_id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->first();
                    
                $car_name = "";
                if(!empty($productTranslation)){
                    $productTranslatedData =  json_decode($productTranslation->field_values, true);
                    $car_name = $productTranslatedData['product_title'];
                }
                
                // Combine order details, tracking data
                $testimonialDetail = [
                        'client_name' => $client_name,
                        'client_email' => $client_email,
                        'client_phone' => $client_phone,
                        'client_review' => $client_review,
                        'stars' => $stars,
                        'car_name' => $car_name
                    ];
            
                // Define messages for different languages
                $messages = [
                    'en' => 'Your request has been sent.Our team will contact you soon.',
                    'ar' => 'تم إرسال طلبك. سيتواصل معك فريقنا قريبًا.'
                ];
                
                // Get the appropriate message
                $message = $messages[$lang];
              
                $template = 'emails.testimonial_templates.'.$lang.'_review'; // Example for Arabic template
                
                $mailerConfig = config('mail.mailers.main');
                $fromAddress = $mailerConfig['from']['address'];
                $fromName = $mailerConfig['from']['name'];
                
                // Send email to client with form details
                Mail::mailer('main')
                        ->to($recipientEmail)
                        ->send(new TestimonialMail($testimonialDetail, $template, $lang, false, $fromAddress, $fromName));    
                    
                // Send email to Admin with review details
                $adminAddress = $mailerConfig['admin_address'];
                Mail::mailer('main')
                    ->to($adminAddress)
                    ->send(new TestimonialMail($testimonialDetail, $template, $lang, true, $fromAddress, $fromName));
                
                
                return response()->json([
                        'status' => true,
                        'message' => $message,
                        'data' => null
                        ], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage(),
                'data' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Store testimonial data insertion part POST/{lang} */
    public function storeFrontendTestimonial(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'client_email'  => 'required|string|email',
                'client_phone'  => 'required|string',
                'car_id' => 'nullable|numeric',
                'stars' => 'nullable|integer|min:0|max:5',
                'testimonial_status' => 'required|numeric',
                'translation' => 'required|array',
                'translation.client_name' => 'required|string',
                'translation.client_review' => 'required|string'
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => false,
                    'message' => $errorMessages,
                    'data' => null
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{

                $testimonial = new Testimonial();
               
                // Handle brand logo uploads
                if (!empty($request->client_image)) {
                    // Upload new banner image
                    $testimonial->client_image = $request->client_image;
                }
                
                // OPTIONAL client_id
                if ($request->filled('client_id')) {
                    $testimonial->client_id = $request->client_id;
                }

                
                $recipientEmail = $request->client_email;
                $client_email = $request->client_email;
                $client_phone = $request->client_phone;
                
                // car_id setup
                $car_id = ($request->car_id && $request->car_id != 0)
                    ? $request->car_id
                    : null;
                $stars = $request->stars ?? 0;
                
                $testimonial->stars = $stars;
                $testimonial->client_email = $client_email;
                $testimonial->client_phone = $client_phone;
                $testimonial->car_id = $car_id;
                $testimonial->testimonial_status = $request->testimonial_status;
                $testimonial->save();
                
                
                $testimonialId = $testimonial->id;
                $translations = $request->input('translation', []);
                $client_name = $translations['client_name'];
                $client_review = $translations['client_review'];
                
                $testimonial_translation = new TestimonialTranslation();
                $testimonial_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $testimonial_translation->language = $lang;
                $testimonial_translation->testimonial_id = $testimonialId;
                $testimonial_translation->save();
                
                
                DB::commit(); // Commit transaction
                
                // Fetch translation for the given language with optimized query
                $productTranslation = ProductTranslation::where('product_id', $car_id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->first();
                    
                $car_name = "";
                if(!empty($productTranslation)){
                    $productTranslatedData =  json_decode($productTranslation->field_values, true);
                    $car_name = $productTranslatedData['product_title'];
                }
                
                // Combine order details, tracking data
                $testimonialDetail = [
                        'client_name' => $client_name,
                        'client_email' => $client_email,
                        'client_phone' => $client_phone,
                        'client_review' => $client_review,
                        'stars' => $stars,
                        'car_name' => $car_name
                    ];
            
                // Define messages for different languages
                $messages = [
                    'en' => 'Your request has been sent.Our team will contact you soon.',
                    'ar' => 'تم إرسال طلبك. سيتواصل معك فريقنا قريبًا.'
                ];
                
                // Get the appropriate message
                $message = $messages[$lang];
              
                $template = 'emails.testimonial_templates.'.$lang.'_review'; // Example for Arabic template
                
                $mailerConfig = config('mail.mailers.main');
                $fromAddress = $mailerConfig['from']['address'];
                $fromName = $mailerConfig['from']['name'];
                
                // Send email to client with form details
                Mail::mailer('main')
                        ->to($recipientEmail)
                        ->send(new TestimonialMail($testimonialDetail, $template, $lang, false, $fromAddress, $fromName));    
                    
                // Send email to Admin with review details
                $adminAddress = $mailerConfig['admin_address'];
                Mail::mailer('main')
                    ->to($adminAddress)
                    ->send(new TestimonialMail($testimonialDetail, $template, $lang, true, $fromAddress, $fromName));
                
                
                return response()->json([
                        'status' => true,
                        'message' => $message,
                        'data' => null
                        ], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage(),
                'data' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* update testimonial data update part PUT/{id}/{lang} */
    public function updateTestimonial(Request $request, $id, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Define validation rules
            $rules = [
                'client_email'  => 'required|string|email',
                'client_phone'  => 'required|string',
                'car_id' => 'nullable|numeric',
                'stars' => 'nullable|integer|min:0|max:5',
                'testimonial_status' => 'required|numeric',
                'translation' => 'required|array',
                'translation.client_name' => 'required|string',
                'translation.client_review' => 'required|string'
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{

                $testimonial = Testimonial::where('id', $id)
                                ->first();
                
                if (!$testimonial) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Testimonial not found'
                    ], Response::HTTP_NOT_FOUND);
                }
               
                // Handle brand logo uploads
                if (!empty($request->client_image)) {
                    // Delete the old image if it exists
                    if ($testimonial->client_image && Storage::disk('public')->exists($testimonial->client_image)) {
                        Storage::disk('public')->delete($testimonial->client_image);
                    }
                    
                    // Upload new client image
                    $testimonial->client_image = $request->client_image;
                }
                
                $recipientEmail = $request->client_email;
                
                // OPTIONAL client_id
                if ($request->filled('client_id')) {
                    $testimonial->client_id = $request->client_id;
                }
                
                // car_id setup
                $car_id = ($request->car_id && $request->car_id != 0)
                    ? $request->car_id
                    : null;
                $stars = $request->stars ?? 0;
                
                $testimonial->client_email = $request->client_email;
                $testimonial->client_phone = $request->client_phone;
                $testimonial->car_id = $car_id;
                $testimonial->stars = $stars;
                $testimonial->testimonial_status = $request->testimonial_status;
                $testimonial->save();
                
                $translations = $request->input('translation', []);
    
                // Update translations
                $testimonial_translation = TestimonialTranslation::where('testimonial_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                if (!$testimonial_translation) {
                    $testimonial_translation = new TestimonialTranslation();
                    $testimonial_translation->testimonial_id = $id;
                    $testimonial_translation->language = $lang;
                }
    
                $testimonial_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $testimonial_translation->save();
                
                
                DB::commit(); // Commit transaction
                
                return response()->json(['status' => 'true', 'message' => 'Testimonial updated successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* testimonial fetch part GET/{id}/{lang} */
    public function editTestimonial($id, $lang)
    {
        try {
            // Fetch the testimonial by id and language
            $testimonial = Testimonial::where('id', $id)
                            ->first();
    
            if (!$testimonial) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Testimonial not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            
            $translation = TestimonialTranslation::where('testimonial_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
            $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
            
            
            // Handle image URLs for primary fields
            $translatedData['id'] = (int) $id;
            $translatedData['client_id'] = $testimonial->client_id ?? null; // Set stars
            $translatedData['client_email'] = $testimonial->client_email;
            $translatedData['client_phone'] = $testimonial->client_phone;
            $translatedData['client_image'] = $testimonial->client_image ? $this->getImageUrl($testimonial->client_image) : null;
            $translatedData['car_id'] = $testimonial->car_id;
            $translatedData['created_at'] = $testimonial->created_at->format('Y-m-d');
            $translatedData['testimonial_status'] = (int) $testimonial->testimonial_status;
            $translatedData['stars'] = $testimonial->stars ?? null; // Set stars
            
            // Fetch Product List 
            $productController = new ProductsController();
            $productListFetch =  $productController->frontendProductsList($lang,0,0);
            
            if($productListFetch->original['data']){
                $carsList = $productListFetch->original['data'];
                
                $translatedData['all_cars'] = $carsList;
            }else{
                $translatedData['all_cars'] = [];
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

    /* Testimonial data fetch part DELETE/{id} */
    public function deleteTestimonial($id)
    {
        try {
            $testimonial = Testimonial::find($id);
    
            if (!$testimonial) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Testimonial not found'
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Delete testimonial client_image if it exists
            if ($testimonial->client_image && Storage::disk('public')->exists($testimonial->client_image)) {
                Storage::disk('public')->delete($testimonial->client_image);
            }

            // Delete the testimonial record
            $testimonial->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Testimonial deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    // Testimonials list search function
    public function searchTestimonialsList(Request $request, $lang, $per_page = 6)
    {
        try {
            // Retrieve the search keyword from the request
            $searchQuery = $request->get('search_query', '');
    
            // Retrieve testimonials filtered by language and search query
            $testimonialsQuery = Testimonial::query()
                                    ->join('testimonial_translations', function ($join) use ($lang) {
                                        $join->on('testimonials.id', '=', 'testimonial_translations.testimonial_id')
                                            ->where('testimonial_translations.language', '=', $lang);
                                    })
                                    ->select('testimonials.*', 'testimonial_translations.field_values');

            // Apply search filters for both slug and partner_title
            if (!empty($searchQuery)) {
                $testimonialsQuery->where(function ($query) use ($searchQuery) {
                    $query->where('client_email', 'LIKE', '%' . $searchQuery . '%')
                            ->orWhere('client_phone', 'LIKE', '%' . $searchQuery . '%')
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(testimonial_translations.field_values, '$.client_name')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
                
            $testimonialsQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $testimonials = $testimonialsQuery->get();
                
                // No pagination meta for full list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $testimonials->count(), // All items in one "page"
                    'total' => $testimonials->count(),
                ];
            } else {
                // Paginate the remaining
                $testimonials = $testimonialsQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $testimonials->currentPage(),
                    'last_page' => $testimonials->lastPage(),
                    'per_page' => $testimonials->perPage(),
                    'total' => $testimonials->total(),
                ];
            }    
                
    
            // Check if testimonials exist
            if ($testimonials->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No testimonials found for the selected language or search query.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Format the data
            $formattedTestimonials = $testimonials->map(function ($testimonial) use ($lang)  {
                $id = $testimonial->id;
                
                $translation = TestimonialTranslation::where('testimonial_id', $id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();                
                $translatedData = !empty($translation) ? json_decode($translation->field_values, true) : [];
                
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['client_email'] = $testimonial->client_email;
                $translatedData['client_phone'] = $testimonial->client_phone;
                $translatedData['client_image'] = $testimonial->client_image ? $this->getImageUrl($testimonial->client_image) : null;
                $translatedData['car_id'] = $testimonial->car_id;
                $translatedData['created_at'] = $testimonial->created_at->format('Y-m-d');
                
                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'message' => __('Testimonials retrieved successfully.'),
                'data' => $formattedTestimonials,
                'pagination' => $pagination
            ], 200);
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
