<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required|string',   // email OR phone
            'password' => 'required|string',
            'fcmToken' => 'nullable|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }
    
        $input = $request->email_or_phone;
    
        // Find customer by email OR phone
        $customer = Customer::where('email', $input)
                            ->orWhere('phone', $input)
                            ->first();
    
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found!',
                'data' => null
            ], 200);
        }
        
        // Check if customer is active
        if ($customer->is_active == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'data' => null
            ], 200);
        }
    
        // Check password
        if (!Hash::check($request->password, $customer->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password!',
                'data' => null
            ], 200);
        }

        if($request->has('fcmToken') && !empty($request->fcmToken)){
            $fcmToken = $request->fcmToken;
            // Update the FCM token of the authenticated user
            $customer->fcm_token = $fcmToken;
            $customer->save();
        }
    
        // Create Sanctum token
        $token = $customer->createToken('customerToken')->plainTextToken;
        $profile_image = $customer->profile_image ? $this->getImageUrl($customer->profile_image) : null;
    
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'profile_image' => $profile_image,
                'api_token' => $token,
                'is_active' => $customer->is_active
            ]
        ], 200);
    }

    public function loginViaOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'nullable|digits:6',
            'fcmToken' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found!',
                'data' => null
            ], 200);
        }

        if ($customer->is_active == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'data' => null
            ], 200);
        }

        if($request->has('fcmToken') && !empty($request->fcmToken)){
            $fcmToken = $request->fcmToken;
            // Update the FCM token of the authenticated user
            $customer->fcm_token = $fcmToken;
            $customer->save();
        }

        // -------------------------------
        // STEP 1: Only email → send OTP
        // -------------------------------
        if (empty($request->otp)) {

            $otp = rand(100000, 999999);

            CustomerPasswordReset::where('email', $request->email)->delete();

            CustomerPasswordReset::create([
                'email' => $request->email,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5)
            ]);

            Mail::raw("Your login OTP is: $otp", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Login OTP');
            });

            return response()->json([
                'status' => true,
                'message' => 'OTP sent to your email',
                'data' => null
            ]);
        }

        // ---------------------------------
        // STEP 2: Email + OTP → Verify OTP
        // ---------------------------------

        $otpRecord = CustomerPasswordReset::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
                'data' => null
            ], 200);
        }

        if (Carbon::now()->gt($otpRecord->expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'data' => null
            ], 200);
        }

        // Delete OTP after use
        $otpRecord->delete();

        // Create token
        $token = $customer->createToken('customerToken')->plainTextToken;

        $profile_image = $customer->profile_image ? $this->getImageUrl($customer->profile_image) : null;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'profile_image' => $profile_image,
                'api_token' => $token,
                'is_active' => $customer->is_active
            ]
        ], 200);
    }

    public function register(Request $request)
    {
        $passwordRule = $request->filled('password')
            ? 'string|min:6|confirmed'
            : 'sometimes|nullable';

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:customers',
            'phone'     => 'nullable|string',
            'password' => $passwordRule,
            'profile_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Handle profile image upload
        $profile_image = null;
        if (!empty($request->profile_image)) {
            $profile_image = $request->profile_image;
        }

        $isPasswordGenerated = false;
        $plainPassword = $request->password;

        if (blank($plainPassword)) {
            $plainPassword = Str::random(12);
            $isPasswordGenerated = true;
        }
      
        // Create the Customer
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($plainPassword),
            'profile_image' => $profile_image
        ]);

        // Generate an API token (if using Sanctum)
        $token = $customer->createToken('authToken')->plainTextToken;
        
        // Return a response
        return response()->json([
            'status' => true,
            'message' => 'User registered successfully!',
            'data' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'api_token' => $token,
                'password_generated' => $isPasswordGenerated,
                'generated_password' => $isPasswordGenerated ? $plainPassword : null
            ],
        ], 200); // HTTP 201 Created
    }
    
    public function getProfile(Request $request)
    {
        // Get the authenticated customer
        $customer = $request->user('customer'); 

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not authenticated!',
                'data' => null
            ], 200);
        }

        // Get user profile image
        $profile_image = $customer->profile_image ? $this->getImageUrl($customer->profile_image) : null;
        
        return response()->json([
            'status' => true,
            'message' => 'Customer profile record fetched.',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'profile_image' => $profile_image,
                'is_active' => $customer->is_active
            ],
        ], 200);
    }
    
    public function updateProfile(Request $request)
    {
        $customer = $request->user('customer'); // Authenticated customer
    
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not authenticated!',
                'data' => null
            ], 200);
        }
    
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|unique:customers,phone,' . $customer->id,
            'profile_image' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        if (!empty($request->profile_image)) {
            $customer->profile_image = $request->profile_image;
        }
    
        // Update other fields
        $customer->name = $request->name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->save();
    
        // Prepare profile image URL
        $profile_image = $customer->profile_image ? asset('storage/' . $customer->profile_image) : null;
    
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'profile_image' => $profile_image,
            ],
        ], 200);
    }
    
    public function deleteAccount(Request $request)
    {
        $customer = $request->user('customer'); // Authenticated customer
    
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not authenticated!',
                'data' => null
            ], 200);
        }
    
        // Delete profile image from storage if exists
        if ($customer->profile_image && Storage::exists($customer->profile_image)) {
            Storage::delete($customer->profile_image);
        }
    
        // Delete the customer
        $customer->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Your account has been deleted successfully.',
            'data' => null
        ], 200);
    }

    public function logout(Request $request)
    {
        $customer = $request->user('customer'); // use customer guard
        
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not authenticated!',
                'data' => null
            ], 200);
        }
    
        $customer->currentAccessToken()->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out',
            'data' => null
        ], 200);
    }
    
    // Send OTP
    public function forgotPassword(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $customer = Customer::where('email', $request->email)->first();
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Email not found',
                'data' => null
            ], 404);
        }

        $otp = rand(100000, 999999);

        // Delete old OTP
        CustomerPasswordReset::where('email', $request->email)->delete();

        // Store new OTP
        CustomerPasswordReset::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        // Send OTP Email
        Mail::raw("Your OTP for password reset is: $otp", function ($message) use ($request) {
            $message->to($request->email)->subject('Password Reset OTP');
        });

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email',
            'data' => null
        ]);
    }

    // Reset Password
    public function resetPassword(Request $request)
    {
        // Validate email, OTP, password and confirm password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|min:6|confirmed' // adds password_confirmation check
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }
        
        // Check OTP
        $record = CustomerPasswordReset::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
                'data' => null
            ], 400);
        }

        if ($record->isExpired()) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'data' => null
            ], 400);
        }

        // Update user password
        $customer = Customer::where('email', $request->email)->first();
        $customer->password = Hash::make($request->password);
        $customer->save();

        // Delete OTP after reset
        $record->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully',
            'data' => null
        ]);
    }

    // Change Password
    public function changePassword(Request $request)
    {
        // Get the authenticated customer
        $customer = $request->user('customer'); 

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // must have new_password_confirmation
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect',
                'data' => null
            ], 400);
        }

        // Update to new password
        $customer->password = Hash::make($request->new_password);
        $customer->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
            'data' => null
        ]);
    }

    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
