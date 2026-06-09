<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enquiry;
use App\Models\Promotion;
use App\Models\PromotionTranslation;
use App\Models\RequestForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Mail\LandingFormMail;
use App\Mail\MailEnquiryForm;
use App\Mail\MailRequestForm;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EnquiryController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Enquiry View', ['only' => ['enquiriesList','searchEnquiriesList','enquriySingleDetail']]);
        // $this->middleware('permission:Enquiry Add', ['only' => ['storeCatalog']]);
        $this->middleware('permission:Enquiry Edit', ['only' => ['updateEnquiry']]);
        $this->middleware('permission:Enquiry Delete', ['only' => ['deleteEnquiry']]);
    }
    
    // Get all Enquiries List
    public function enquiriesList($form_type, $lang, $per_page = 6)
    {
        try {
            // Retrieve enquiries filtered by language
            $enquiries = Enquiry::where('language',$lang)
                            ->orderBy('created_at', 'desc');

            if (!empty($form_type) && $form_type != 'all') {
                $enquiries = $enquiries->where('form_type', $form_type);
            }
            
            $enquiries = $enquiries->paginate($per_page);

            // Check if testimonials exist
            if ($enquiries->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No enquiries found.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Format the data
            $formattedEnquiries = $enquiries->map(function ($enquiry) {
                $form_type = $enquiry->form_type;
                
                return [
                    'id' =>  $enquiry->id,
                    'company_name' => $enquiry->company_name,
                    'client_name' => $enquiry->client_name,
                    'client_last_name' => $enquiry->client_last_name,
                    'client_phone' => $enquiry->client_phone,
                    'client_email' => $enquiry->client_email,
                    'from_datetime' => $enquiry->from_datetime,
                    'to_datetime' => $enquiry->to_datetime,
                    'referer_page_slug' => $enquiry->referer_page_slug,
                    'form_status' => $enquiry->form_status,
                    'form_type' => ucwords(str_replace('_', ' ',$enquiry->form_type)),
                    'car_name' => $enquiry->car_name,
                    'period' => $enquiry->period,
                    'lease_to_own' => $enquiry->lease_to_own,
                    'client_comments' => $enquiry->client_comments,
                    'country' => $enquiry->country,
                    'city' => $enquiry->city,
                    'created_at' => $enquiry->created_at
                ];
            });
    
            return response()->json([
                'status' => 'true',
                'message' => __('Enquiries retrieved successfully.'),
                'data' => $formattedEnquiries,
                'pagination' => [
                    'current_page' => $enquiries->currentPage(),
                    'last_page' => $enquiries->lastPage(),
                    'total' => $enquiries->total(),
                    'per_page' => $enquiries->perPage()
                ]
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* Enquiry form submit part */
    public function offerForm(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
            
            $client_name = $request->client_name ?? "";
            $client_last_name = $request->client_last_name ?? "";
            $client_phone = $request->client_phone ?? "";
            $client_email = $request->client_email ?? "";
            $company_name = $request->company_name ?? "";
            $from_datetime = $request->from_datetime ?? "";
            $to_datetime = $request->to_datetime ?? "";
            $car_name = $request->car_name ?? "";
            $form_type = $request->form_type ?? "";
            $referer_page_slug = $request->referer_page_slug ?? "";
            $country = $request->country ?? "";
            $city = $request->city ?? "";
            $form_status = '';
            $period = $request->period ?? "";
            $lease_to_own = $request->lease_to_own ?? "";
            $client_comments = $request->client_comments ?? "";
            $gclid = $request->gclid;
            $source = $request->source;
            $keyword = $request->keyword;
            $device = $request->device;
            $matchtype = $request->matchtype;
        
            $fetchEnquiry = new Enquiry();
            $fetchEnquiry->company_name = $company_name;
            $fetchEnquiry->client_name = $client_name;
            $fetchEnquiry->client_last_name = $client_last_name;
            $fetchEnquiry->client_phone = $client_phone;
            $fetchEnquiry->client_email = $client_email;
            $fetchEnquiry->from_datetime = $from_datetime;
            $fetchEnquiry->to_datetime = $to_datetime;
            $fetchEnquiry->car_name = $car_name;
            $fetchEnquiry->form_type = $form_type;
            $fetchEnquiry->referer_page_slug = $referer_page_slug;
            $fetchEnquiry->country = $country;
            $fetchEnquiry->city = $city;
            $fetchEnquiry->form_status =$form_status;
            $fetchEnquiry->period = $period;
            $fetchEnquiry->lease_to_own = $lease_to_own;
            $fetchEnquiry->client_comments = $client_comments;
            $fetchEnquiry->language = $lang;
            $fetchEnquiry->save();
    
            DB::commit(); // Commit transaction
            
            $form_type = $fetchEnquiry->form_type;
            
            $recipientEmail = $fetchEnquiry->client_email;
            
            // Combine order details, tracking data
            $enquiryDetail = [
                    'company_name' => $fetchEnquiry->company_name,
                    'client_name' => $fetchEnquiry->client_name,
                    'client_last_name' => $fetchEnquiry->client_last_name,
                    'client_phone' => $fetchEnquiry->client_phone,
                    'client_email' => $fetchEnquiry->client_email,
                    'from_datetime' => $fetchEnquiry->from_datetime,
                    'to_datetime' => $fetchEnquiry->to_datetime,
                    'form_type' => ucwords(str_replace('_', ' ',$form_type)),
                    'referer_page_slug' => $fetchEnquiry->referer_page_slug,
                    'form_status' => $fetchEnquiry->form_status,
                    'car_name' => $fetchEnquiry->car_name,
                    'period' => $fetchEnquiry->period,
                    'lease_to_own' => $fetchEnquiry->lease_to_own,
                    'client_comments' => $fetchEnquiry->client_comments,
                    'country' => $fetchEnquiry->country,
                    'city' => $fetchEnquiry->city,
                    'updated' => false
                ];
                
             // Define messages for different languages
            $messages = [
                'en' => 'Your request has been sent.Our team will contact you soon.',
                'ar' => 'لقد تم ارسال طلبك. سوف يتواصل فريقنا معك قريبًا.',
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];   
          
            $template = 'emails.'.$lang.'_enquiry_form'; // Example for Arabic template
             
            $mailerConfig = config('mail.mailers.main');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            if(!empty($recipientEmail)){
                // Send email to client with form details
                Mail::mailer('main')
                        ->to($recipientEmail)
                        ->send(new MailEnquiryForm($enquiryDetail, $template, $lang, false, $fromAddress, $fromName));    
            }
                
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('main')
                ->to($adminAddress)
                ->send(new MailEnquiryForm($enquiryDetail, $template, $lang, true, $fromAddress, $fromName));    

            return response()->json(['status' => 'true', 'message' => $message], 200);
            
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /* update form data update part PUT/{id} */
    public function updateEnquiry(Request $request, $id, $lang)
    {
        DB::beginTransaction(); // Start transaction
        try {
            
            $form_id = $id;
            $fetchEnquiry = Enquiry::where('language', $lang)
                            ->where('id', $form_id)
                            ->first();
            
            if (!$fetchEnquiry) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Form not exist.'
                ], Response::HTTP_NOT_FOUND);
            }
          
            $fetchEnquiry->company_name = $request->company_name ?? null;
            $fetchEnquiry->client_name = $request->client_name ?? null;
            $fetchEnquiry->client_last_name = $request->client_last_name ?? null;
            $fetchEnquiry->client_phone = $request->client_phone ?? null;
            $fetchEnquiry->client_email = $request->client_email ?? null;
            $fetchEnquiry->from_datetime = $request->from_datetime ?? null;
            $fetchEnquiry->to_datetime = $request->to_datetime ?? null;
            $fetchEnquiry->car_name = $request->car_name ?? null;
            $fetchEnquiry->form_type = $request->form_type ?? null;
            $fetchEnquiry->referer_page_slug = $request->referer_page_slug ?? null;
            $fetchEnquiry->country = $request->country ?? null;
            $fetchEnquiry->city = $request->city ?? null;
            $fetchEnquiry->form_status = '';
            $fetchEnquiry->period = $request->period ?? null;
            $fetchEnquiry->lease_to_own = $request->lease_to_own ?? null;
            $fetchEnquiry->client_comments = $request->client_comments ?? null;
            
            $fetchEnquiry->save();
    
            
            DB::commit(); // Commit transaction
            
            $recipientEmail = $fetchEnquiry->client_email;
            
            // Combine order details, tracking data
            $enquiryDetail = [
                    'company_name' => $fetchEnquiry->company_name,
                    'client_name' => $fetchEnquiry->client_name,
                    'client_last_name' => $fetchEnquiry->client_last_name,
                    'client_phone' => $fetchEnquiry->client_phone,
                    'client_email' => $fetchEnquiry->client_email,
                    'from_datetime' => $fetchEnquiry->from_datetime,
                    'to_datetime' => $fetchEnquiry->to_datetime,
                    'form_type' => ucwords(str_replace('_', ' ',$form_type)),
                    'referer_page_slug' => $fetchEnquiry->referer_page_slug,
                    'form_status' => $fetchEnquiry->form_status,
                    'car_name' => $fetchEnquiry->car_name,
                    'period' => $fetchEnquiry->period,
                    'lease_to_own' => $fetchEnquiry->lease_to_own,
                    'client_comments' => $fetchEnquiry->client_comments,
                    'country' => $fetchEnquiry->country,
                    'city' => $fetchEnquiry->city,
                    'updated' => true
                ];
                
             // Define messages for different languages
            $messages = [
                'en' => 'Form updated successfully',
                'ar' => 'تم تعديل النموذج بنجاح'
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];   
          
            $template = 'emails.'.$lang.'_enquiry_form';
            
            $mailerConfig = config('mail.mailers.main');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            if(!empty($recipientEmail)){
            // Send email to client with form details
            Mail::mailer('main')
                    ->to($recipientEmail)
                    ->send(new MailEnquiryForm($enquiryDetail, $template, $lang, false, $fromAddress, $fromName));    
            }
            
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('main')
                ->to($adminAddress)
                ->send(new MailEnquiryForm($enquiryDetail, $template, $lang, true, $fromAddress, $fromName));   

            return response()->json(['status' => 'true', 'message' => $message], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Form fetch part GET/{id}/{lang} */
    public function enquriySingleDetail($id)
    {
        try {
            // Fetch the testimonial by id and language
            $enquiry = Enquiry::where('id', $id)
                            ->first();
    
            if (!$enquiry) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'No enquiry found'
                ], Response::HTTP_NOT_FOUND);
            }
        
        
            // Prepare the testimonial data for response
            $enquiryData = [
                    'company_name' => $enquiry->company_name,
                    'client_name' => $enquiry->client_name,
                    'client_last_name' => $enquiry->client_last_name,
                    'client_phone' => $enquiry->client_phone,
                    'client_email' => $enquiry->client_email,
                    'from_datetime' => $enquiry->from_datetime,
                    'to_datetime' => $enquiry->to_datetime,
                    'referer_page_slug' => $enquiry->referer_page_slug,
                    'form_status' => $enquiry->form_status,
                    'form_type' => $enquiry->form_type,
                    'car_name' => $enquiry->car_name,
                    'period' => $enquiry->period,
                    'lease_to_own' => $enquiry->lease_to_own,
                    'client_comments' => $enquiry->client_comments,
                    'country' => $enquiry->country,
                    'city' => $enquiry->city,
                    'created_at' => $enquiry->created_at
                ];
    
            return response()->json([
                'status' => 'true',
                'data' => $enquiryData
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Form data fetch part DELETE/{id} */
    public function deleteEnquiry($id)
    {
        try {
            $formQuery = Enquiry::find($id);
    
            if (!$formQuery) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Form not exist.'
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Delete the record
            $formQuery->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Form deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Enquiries Form list search function
    public function searchEnquiriesList(Request $request, $lang, $per_page = 6)
    {
        try {
            // Retrieve the search keyword from the request
            $searchQuery = $request->get('search_query', '');
    
            // Retrieve testimonials filtered by language and search query
            $enquiries = Enquiry::where(function ($query) use ($searchQuery) {
                                $query->where('client_name', 'LIKE', '%' . $searchQuery . '%')
                                    ->orWhere('client_last_name', 'LIKE', '%' . $searchQuery . '%')
                                    ->orWhere('client_email', 'LIKE', '%' . $searchQuery . '%')
                                    ->orWhere('client_phone', 'LIKE', '%' . $searchQuery . '%')
                                    ->orWhere('form_status', 'LIKE', '%' . $searchQuery . '%')
                                    ->orWhere('form_type', 'LIKE', '%' . $searchQuery . '%');
                            })
                            ->where('language',$lang)
                            ->orderBy('created_at', 'desc') // Sort by latest created
                            ->paginate($per_page);
    
            // Check if testimonials exist
            if ($enquiries->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No form found for the search query.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Format the data
            $formattedEnquiries = $enquiries->map(function ($enquiry) {
                
                return [
                    'id' =>  $enquiry->id,
                    'company_name' => $enquiry->company_name,
                    'client_name' => $enquiry->client_name,
                    'client_last_name' => $enquiry->client_last_name,
                    'client_phone' => $enquiry->client_phone,
                    'client_email' => $enquiry->client_email,
                    'from_datetime' => $enquiry->from_datetime,
                    'to_datetime' => $enquiry->to_datetime,
                    'referer_page_slug' => $enquiry->referer_page_slug,
                    'form_status' => $enquiry->form_status,
                    'car_name' => $enquiry->car_name,
                    'period' => $enquiry->period,
                    'lease_to_own' => $enquiry->lease_to_own,
                    'client_comments' => $enquiry->client_comments,
                    'country' => $enquiry->country,
                    'city' => $enquiry->city,
                    'created_at' => $enquiry->created_at
                ];
            });
    
            return response()->json([
                'status' => 'true',
                'message' => __('Enquiries retrieved successfully.'),
                'data' => $formattedEnquiries,
                'pagination' => [
                    'current_page' => $enquiries->currentPage(),
                    'last_page' => $enquiries->lastPage(),
                    'total' => $enquiries->total(),
                    'per_page' => $enquiries->perPage()
                ]
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
    
    protected function getRelatedPageTitle($modelName, $modelTranslationName, $fieldName, $id)
    {
        // Resolve the fully qualified class names
        $modelName = 'App\\Models\\' . $modelName;
        $modelTranslationName = 'App\\Models\\' . $modelTranslationName;
    
        // Retrieve the page query
        $pageQuery = $modelName::find($id);
    
        // Retrieve the translation data
        $translation = $modelTranslationName::where($fieldName, $id)
            ->where('language', 'en')
            ->first();
    
        $fields_value = [];
        if (!empty($translation)) {
            // Decode the JSON data
            $fields_value = json_decode($translation->field_values, true);
        }
    
        return $fields_value;
    }
    
    // Request form function
    public function RequestForm(Request $request)
    {
        $name = $request["full_name"];
        $mobile = $request["mobile"];
        $email = $request["email"];
        $modal = $request["modal"];
        $url = $request["url"];

        try {

            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'mobile' => 'required|max:15',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'error' => implode(',', $validator->errors()->all())
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $mailData = [
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'modal' => $modal
            ];

            $mailData2 = [
                'name'    => $name,
                'email'        => $email,
                'phone_number' => $mobile,
                'car_model'    => $modal,
                'url'    => $url
            ];

            $mailerConfig = config('mail.mailers.landing');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            // Send email to client with form details
            Mail::mailer('landing')
                    ->to($email)
                    ->send(new LandingFormMail($mailData, false, $fromAddress, $fromName));    
                
            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('landing')
                ->to($adminAddress)
                ->send(new LandingFormMail($mailData, true, $fromAddress, $fromName));

            return response()->json([
                'status' => true,
                'message' => 'Mail sent successfully!'
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Get all Customer Enquiries List
    public function customerEnquiriesList(Request $request, $lang, $per_page = 6)
    {
        try {
            
            // Get the authenticated customer
            $customer = $request->user('customer'); 
    
            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not authenticated!',
                    'data' => null
                ], 200);
            }
            
            $email = $customer->email; 
            
            // Retrieve enquiries filtered by language
            $enquiries = Enquiry::where('language',$lang)
                            ->where('client_email',$email)
                            ->orderBy('created_at', 'desc');
            
            $enquiries = $enquiries->paginate($per_page);

            // Check if testimonials exist
            if ($enquiries->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No enquiries found.'),
                    'data' => null
                ], Response::HTTP_NOT_FOUND);
            }
            
            $allEnquiries = [];
            
            // Format the data
            $formattedEnquiries = $enquiries->map(function ($enquiry) {
                return [
                    'id' =>  $enquiry->id,
                    'client_name' => $enquiry->client_name,
                    'client_phone' => $enquiry->client_phone,
                    'client_email' => $enquiry->client_email,
                    'form_type' => ucwords(str_replace('_', ' ',$enquiry->form_type)),
                    'client_comments' => $enquiry->client_comments,
                    'created_at' => $enquiry->created_at
                ];
            });
            
            $allEnquiries['list'] = $formattedEnquiries;
        
            $allEnquiries['pagination'] = [
                    'current_page' => $enquiries->currentPage(),
                    'last_page' => $enquiries->lastPage(),
                    'total' => $enquiries->total(),
                    'per_page' => $enquiries->perPage()
                ];
            
            return response()->json([
                'status' => 'true',
                'message' => __('Enquiries retrieved successfully.'),
                'data' => $allEnquiries
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage(),
                'data' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
