<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    public $subscription;
    
    public function __construct() {
        $this->subscription = new Subscription();
    }
    
    // new subscription
    public function newSubscription(Request $request)
    {
        try {

            DB::beginTransaction();

            // return view('emails.subscribe');
            $email = $request["email"];
            $linkReferer = 'Link referer: ' . $request['link_refer'] . '\n' . 'Email: ' . $request["email"];
            $data = [
                'email' => $email,
                'user' => explode('@', $request["email"])[0]
            ];
            
            $subscription = $this->subscription::create(['data' => $linkReferer, 'email' => $request["email"], 'status' => 'Pending']);

            $mailerConfig = config('mail.mailers.landing');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];
            
            // Send email to client with form details
            Mail::mailer('landing')->send(
                'emails.subscribe',
                ['user' => ucfirst(explode('@', $request["email"])[0]), 'isAdmin' => false, 'created' => $subscription->created_at, 'status' => $subscription->status],
                function ($message) use ($email, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName);
                    $message->to($email)->subject('Subscription Successful');
                }
            );

            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('landing')->send(
                'emails.subscribe',
                ['user' => ucfirst(explode('@', $request["email"])[0]), 'email' => $request["email"], 'isAdmin' => true, 'created' => $subscription->created_at, 'status' => $subscription->status],
                function ($message) use ($adminAddress, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName);
                    $message->to($adminAddress)->subject('New Subscription');
                }
            );

            DB::commit();
            // if (Mail::failures()) {
            //     return response()->json(['status' => false, 'message' => 'Email not sent.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            // }

            return response()->json(['status' => true, 'message' => 'Subscription Successful!'], Response::HTTP_OK);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function all()
    {
        try {
            $data = $this->subscription::all();

            if (empty($data)) {
                return response()->json(['status' => true, 'message' => "No data found!"], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['status' => true, 'data' => $data], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateSubStatus(Request $request, $id)
    {
        try {
            $data = $this->subscription::find($id);

            // return $data;

            if (empty($data)) {
                return response()->json(['status' => true, 'message' => "No data found!"], Response::HTTP_NOT_FOUND);
            }

            $data->status = $request["status"];

            $data->update();


            $email = $data->email;
            $created_at = $data->created_at;
            $status = $data->status;

            $mailerConfig = config('mail.mailers.landing');
            $fromAddress = $mailerConfig['from']['address'];
            $fromName = $mailerConfig['from']['name'];

            Mail::mailer('landing')->send(
                'emails.subscribe',
                ['user' => ucfirst(explode('@', $email)[0]), 'isAdmin' => false, 'created' => $created_at, 'status' => $status],
                function ($message) use ($email, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName);
                    $message->to($email)->subject('Subscription Update');
                }
            );

            // Send email to Admin with review details
            $adminAddress = $mailerConfig['admin_address'];
            Mail::mailer('landing')->send(
                'emails.subscribe',
                ['user' => ucfirst(explode('@', $email)[0]), 'email' => $email, 'isAdmin' => true, 'created' => $created_at, 'status' => $status],
                function ($message) use ($adminAddress, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName);
                    $message->to($adminAddress)->subject('Subscription Update');
                }
            );

            return response()->json(['status' => true, 'message' => 'Data updated!'], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function subsriptionDetails($id)
    {
        try {
            $data = $this->subscription::find($id);
            
            if(empty($data)){
                return response()->json(['status' => true, 'message' => "No data found!"], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['status' => true, 'data' => $data], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    public function deleteSubsription($id)
    {
        try {
            $data = $this->subscription::find($id);
            
            if(empty($data)){
                return response()->json(['status' => true, 'message' => "No data found!"], Response::HTTP_NOT_FOUND);
            }


            $data->delete();

            return response()->json(['status' => true, 'message' => 'Data deleted!'], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
        }
    }
}
