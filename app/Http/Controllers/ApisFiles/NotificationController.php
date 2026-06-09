<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ApisFiles\FirebaseNotificationControllers;

class NotificationController extends Controller
{
    // ✅ CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'notification' => 'required|string',
            'image' => 'nullable|string',
            'data' => 'nullable|array'
        ]);

        // ✅ Save Notification
        $notification = PushNotification::create($validated);

        // ✅ Get all customers with FCM token
        $customers = Customer::whereNotNull('fcm_token')->get();

        if ($customers->isNotEmpty()) {

            $firebase = new FirebaseNotificationControllers();

            foreach ($customers as $customer) {

                if (!empty($customer->fcm_token)) {

                    $firebase->sendUserNotification(
                        $customer->fcm_token,
                        $notification->title,
                        $notification->notification,
                        $notification->image ?? '',
                        $notification->data ?? []
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification created and sent successfully.',
            'data' => $this->formatData($notification)
        ], 201);
    }

    // ✅ LIST (Pagination + Search)
    public function index(Request $request)
    {
        $query = PushNotification::query();

        // 🔍 Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('notification', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);

        $data = $query->latest()->paginate($perPage);

        // ✅ Map Loop
        $notifications = $data->getCollection()->map(function ($item) {
            return $this->formatData($item);
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total_records' => $data->total(),
                'total_pages' => $data->lastPage(),
            ]
        ]);
    }

    // ✅ ALL (No Pagination)
    public function all()
    {
        $data = PushNotification::latest()->get();

        $notifications = $data->map(function ($item) {
            return $this->formatData($item);
        });

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // ✅ SHOW
    public function show($id)
    {
        $notification = PushNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatData($notification)
        ]);
    }

    //  UPDATE
    public function update(Request $request, $id)
    {
        $notification = PushNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'notification' => 'sometimes|required|string',
            'image' => 'sometimes|nullable|string',
            'data' => 'nullable|array'
        ]);

        // ✅ Only update fields that are actually provided
        if (isset($validated['title'])) {
            $notification->title = $validated['title'];
        }

        if (isset($validated['notification'])) {
            $notification->notification = $validated['notification'];
        }

        if (isset($validated['data'])) {
            $notification->data = $validated['data'];
        }

        // ✅ Image update only if provided and not empty
        if ($request->has('image') && $request->image != '') {
            $notification->image = $request->image;
        }

        $notification->save();

        // ✅ Prepare data for sending notifications
        $title = $notification->title;
        $body = $notification->notification;
        $image = $notification->image ?? '';
        $data  = $notification->data ?? [];

        // ✅ Send FCM notification to all customers with token
        $customers = Customer::whereNotNull('fcm_token')->get();

        if ($customers->isNotEmpty()) {
            $firebase = new FirebaseNotificationControllers();

            foreach ($customers as $customer) {
                if (!empty($customer->fcm_token)) {
                    $firebase->sendUserNotification(
                        $customer->fcm_token,
                        $title,
                        $body,
                        $image,
                        $data
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification updated and sent successfully.',
            'data' => $this->formatData($notification)
        ]);
    }
    // ✅ DELETE (Hard Delete)
    public function destroy($id)
    {
        $notification = PushNotification::find($id);
    
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }
    
        $notification->delete(); // soft delete
    
        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully (soft deleted).'
        ]);
    }

    // ✅ REVOKE
    public function revoke($id)
    {
        $notification = PushNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update([
            'status' => 'revoked'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification status updated to revoked.',
            'data' => [
                'id' => $notification->id,
                'status' => $notification->status
            ]
        ]);
    }

    // ✅ FORMAT RESPONSE
    private function formatData($item)
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'notification' => $item->notification,
            'image' => $item->image ? $this->getImageUrl($item->image) : null,
            'data' => $item->data,
            'status' => $item->status,
            'created_at' => $item->created_at,
        ];
    }

    // ✅ IMAGE URL HELPER
    public function getImageUrl($image_path)
    {
        return asset('storage/' . $image_path);
    }
}