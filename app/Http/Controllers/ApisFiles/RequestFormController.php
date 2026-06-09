<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestForm;
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
use App\Mail\MailEnquiryForm;
use App\Mail\MailRequestForm;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class RequestFormController extends Controller
{
    // Get all requestFormList
    public function requestFormList($per_page = 6)
    {
        try {
            // Retrieve data
            $requestFormFetch = RequestForm::orderBy('created_at', 'desc') // Sort by latest created
                            ->paginate($per_page);
    
            // Check if testimonials exist
            if ($requestFormFetch->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => __('No request found.')
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Formatted the data
            $formattedFormList = $requestFormFetch->map(function ($request) {
                
                return [
                    'id' => $request->id,
                    'client_name' => $request->client_name,
                    'client_email' => $request->client_email ?? null,
                    'client_contract_number' => $request->client_contract_number,
                    'service_name' => $request->service_name,
                    'message' => $request->message,
                    'team_comment' => $request->team_comment ?? '',
                    'created_at' => $request->created_at
                ];
            });
    
            return response()->json([
                'status' => 'true',
                'message' => __('Request form list retrieved successfully.'),
                'data' => $formattedFormList,
                'pagination' => [
                    'current_page' => $requestFormFetch->currentPage(),
                    'last_page' => $requestFormFetch->lastPage(),
                    'total' => $requestFormFetch->total(),
                    'per_page' => $requestFormFetch->perPage()
                ]
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
       
    /* Request form submit part */
    public function requestForm(Request $request)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'client_name'  => 'required|string',
                'client_contract_number'  => 'required|string',
                'client_email'  => 'nullable|email',
                'service_name' => 'required|string',
                'message'  => 'required|string'
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
                
                $fetchRequestForm = new RequestForm();
                $fetchRequestForm->client_name = $request->client_name;
                $fetchRequestForm->client_contract_number = $request->client_contract_number;
                $fetchRequestForm->client_email = $request->client_email ?? null;
                $fetchRequestForm->service_name = $request->service_name ?? null;
                $fetchRequestForm->message = $request->message;
                $fetchRequestForm->save();
        
                DB::commit(); // Commit transaction
                
                $recipientEmail = $fetchRequestForm->client_email;
                
                // Combine order details, tracking data
                $requestDetail = [
                        'client_name' => $fetchRequestForm->client_name,
                        'client_contract_number' => $fetchRequestForm->client_contract_number,
                        'client_email' => $recipientEmail ?? null,
                        'service_name' => $fetchRequestForm->service_name ?? null,
                        'message' => $fetchRequestForm->message
                    ];
            
                if(!empty($recipientEmail)){
                    // Send email to client with form details
                    Mail::to($recipientEmail)->send(new MailRequestForm($requestDetail, false));
                }
                
                // Send email to Admin with review details
                $isAdmin = config('mail.admin_address');
                Mail::to($isAdmin)->send(new MailRequestForm($requestDetail, true));

                return response()->json(['status' => 'true', 'message' => ' Your request has been sent.Our team will contact you soon.'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function contractUpdate(Request $request, $id)
    {
        DB::beginTransaction(); // Start transaction
        try {
             // Define validation rules
            $rules = [
                'team_comment' => 'required|string'
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

                $requestFormFetch = RequestForm::where('id', $id)->first();
                
                if (!$requestFormFetch) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Data not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $requestFormFetch->team_comment = $request->team_comment;
                $requestFormFetch->save();
                
                DB::commit(); // Commit transaction
                
                return response()->json(['status' => 'true', 'message' => 'Request Form updated successfully'], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
