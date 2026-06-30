<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request, $per_page = 10)
    {
        try {
            $customerQuery = Customer::orderBy('created_at', 'DESC');

            if ($request->filled('search')) {
                $search = $request->search;

                $customerQuery->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $customerQuery->where('is_active', $request->is_active);
            }

            $perPage = $request->input('per_page', $per_page);

            if ($perPage == 0) {
                $customers = $customerQuery->get();

                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $customers->count(),
                    'total' => $customers->count(),
                ];
            } else {
                $customers = $customerQuery->paginate($perPage);

                $pagination = [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ];
            }

            if ($customers->isEmpty()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Customers not found'
                ], Response::HTTP_OK);
            }

            $customersData = $customers->map(function ($customer) {
                return $this->customerResponse($customer);
            });

            return response()->json([
                'status' => 'true',
                'data' => $customersData,
                'pagination' => $pagination
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'phone' => 'nullable|string|max:255',
                'password' => 'required|string|min:6',
                'profile_image' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
                'fcm_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'profile_image' => $request->profile_image,
                'password' => Hash::make($request->password),
                'is_active' => $request->is_active ?? 1,
                'fcm_token' => $request->fcm_token,
            ]);

            return response()->json([
                'status' => 'true',
                'message' => 'Customer created successfully',
                'data' => $this->customerResponse($customer)
            ], Response::HTTP_CREATED);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Customer not found'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => 'true',
                'data' => $this->customerResponse($customer)
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Customer not found'
                ], Response::HTTP_OK);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:customers,email,' . $customer->id,
                'phone' => 'nullable|string|max:255',
                'password' => 'nullable|string|min:6',
                'profile_image' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
                'fcm_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $customer->name = $request->name ?? $customer->name;
            $customer->email = $request->email ?? $customer->email;
            $customer->phone = $request->phone ?? $customer->phone;

            if ($request->has('profile_image')) {
                $customer->profile_image = $request->profile_image;
            }

            if ($request->has('is_active')) {
                $customer->is_active = $request->is_active;
            }

            if ($request->has('fcm_token')) {
                $customer->fcm_token = $request->fcm_token;
            }

            if ($request->filled('password')) {
                $customer->password = Hash::make($request->password);
            }

            $customer->save();

            return response()->json([
                'status' => 'true',
                'message' => 'Customer updated successfully',
                'data' => $this->customerResponse($customer)
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Customer not found'
                ], Response::HTTP_OK);
            }

            $customer->delete();

            return response()->json([
                'status' => 'true',
                'message' => 'Customer deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Customer not found'
                ], Response::HTTP_OK);
            }

            $customer->is_active = $request->is_active;
            $customer->save();

            return response()->json([
                'status' => 'true',
                'message' => 'Customer status updated successfully',
                'data' => $this->customerResponse($customer)
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function customerResponse($customer)
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,

            'profile_image' => $customer->profile_image
                ? asset('storage/' . $customer->profile_image)
                : null,

            'profile_image_path' => $customer->profile_image,

            'is_active' => (int) $customer->is_active,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
        ];
    }
}