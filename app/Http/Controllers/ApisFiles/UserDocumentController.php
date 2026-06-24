<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UserDocumentController extends Controller
{
    public function mobileIndex(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
                'data' => null
            ], 200);
        }

        return $this->listDocuments(
            UserDocument::with('user:id,name,email,phone')->where('customer_id', $user->id),
            $request,
            true
        );
    }

    public function mobileStore(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), $this->documentRules(false));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $document = UserDocument::create([
            'customer_id' => $user->id,
            'title' => $request->title,
            'status' => $request->has('status') ? (int) $request->boolean('status') : 1,
            'expiry_date' => $request->expiry_date,
            'attachment' => $request->attachment,
            'comment' => $request->comment,
            'type' => $request->type,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User document created successfully!',
            'data' => $this->formatDocument($document->load('user:id,name,email,phone'), true),
        ], 200);
    }

    public function mobileShow(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
                'data' => null
            ], 200);
        }

        $document = UserDocument::with('user:id,name,email,phone')
            ->where('customer_id', $user->id)
            ->find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'User document fetched successfully!',
            'data' => $this->formatDocument($document, true),
        ], 200);
    }

    public function mobileUpdate(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
                'data' => null
            ], 200);
        }

        $document = UserDocument::where('customer_id', $user->id)->find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), $this->documentRules(true));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $document->title = $request->has('title') ? $request->title : $document->title;
        if ($request->has('status')) {
            $document->status = (int) $request->boolean('status');
        }
        $document->expiry_date = $request->has('expiry_date') ? $request->expiry_date : $document->expiry_date;
        $document->attachment = $request->has('attachment') ? $request->attachment : $document->attachment;
        $document->comment = $request->has('comment') ? $request->comment : $document->comment;
        $document->type = $request->has('type') ? $request->type : $document->type;
        $document->save();

        return response()->json([
            'status' => true,
            'message' => 'User document updated successfully!',
            'data' => $this->formatDocument($document->load('user:id,name,email,phone'), true),
        ], 200);
    }

    public function mobileDelete(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
                'data' => null
            ], 200);
        }

        $document = UserDocument::where('customer_id', $user->id)->find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        $document->delete();

        return response()->json([
            'status' => true,
            'message' => 'User document deleted successfully!',
            'data' => null
        ], 200);
    }

    public function adminIndex(Request $request)
    {
        $query = UserDocument::with('user:id,name,email,phone')->orderBy('created_at', 'DESC');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . $request->type . '%');
        }

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        return $this->listDocuments($query, $request, true);
    }

    public function adminStore(Request $request)
    {
        $validator = Validator::make($request->all(), $this->documentRules(false, true));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $document = UserDocument::create([
            'customer_id' => $request->customer_id,
            'title' => $request->title,
            'status' => $request->has('status') ? (int) $request->boolean('status') : 1,
            'expiry_date' => $request->expiry_date,
            'attachment' => $request->attachment,
            'comment' => $request->comment,
            'type' => $request->type,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User document created successfully!',
            'data' => $this->formatDocument($document->load('user:id,name,email,phone'), true),
        ], 200);
    }

    public function adminShow($id)
    {
        $document = UserDocument::with('user:id,name,email,phone')->find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'User document fetched successfully!',
            'data' => $this->formatDocument($document, true),
        ], 200);
    }

    public function adminUpdate(Request $request, $id)
    {
        $document = UserDocument::find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), $this->documentRules(true, true));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        if ($request->has('customer_id')) {
            $document->customer_id = $request->customer_id;
        }
        if ($request->has('title')) {
            $document->title = $request->title;
        }
        if ($request->has('status')) {
            $document->status = (int) $request->boolean('status');
        }
        if ($request->has('expiry_date')) {
            $document->expiry_date = $request->expiry_date;
        }
        if ($request->has('attachment')) {
            $document->attachment = $request->attachment;
        }
        if ($request->has('comment')) {
            $document->comment = $request->comment;
        }
        if ($request->has('type')) {
            $document->type = $request->type;
        }

        $document->save();

        return response()->json([
            'status' => true,
            'message' => 'User document updated successfully!',
            'data' => $this->formatDocument($document->load('user:id,name,email,phone'), true),
        ], 200);
    }

    public function adminDelete($id)
    {
        $document = UserDocument::find($id);

        if (!$document) {
            return response()->json([
                'status' => false,
                'message' => 'User document not found!',
                'data' => null
            ], 200);
        }

        $document->delete();

        return response()->json([
            'status' => true,
            'message' => 'User document deleted successfully!',
            'data' => null
        ], 200);
    }

    private function documentRules(bool $isUpdate = false, bool $isAdmin = false): array
    {
        $rules = [
            'title' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'status' => 'nullable|boolean',
            'expiry_date' => 'nullable|date',
            'attachment' => 'nullable|string',
            'comment' => 'nullable|string',
            'type' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
        ];

        if ($isAdmin) {
            $rules['customer_id'] = $isUpdate ? 'sometimes|required|exists:users,id' : 'required|exists:users,id';
        }

        return $rules;
    }

    private function listDocuments($query, Request $request, bool $includeUser = false)
    {
        $perPage = (int) $request->input('per_page', 10);

        if ($perPage === 0) {
            $documents = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'User documents retrieved successfully!',
                'data' => $documents->map(fn ($document) => $this->formatDocument($document, $includeUser)),
                'pagination' => [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $documents->count(),
                    'total' => $documents->count(),
                ],
            ], 200);
        }

        $documents = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'User documents retrieved successfully!',
            'data' => $documents->getCollection()->map(fn ($document) => $this->formatDocument($document, $includeUser)),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ], 200);
    }

    private function formatDocument(UserDocument $document, bool $includeUser = false): array
    {

        $data = [
            'id' => $document->id,
            'customer_id' => $document->customer_id,
            'title' => $document->title,
            'status' => (int) $document->status,
            'expiry_date' => $document->expiry_date ? $document->expiry_date->format('Y-m-d') : null,
            'attachment' => $this->getFileUrl($document->attachment),
            'comment' => $document->comment,
            'type' => $document->type,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];

        if ($includeUser && $document->relationLoaded('user') && $document->user) {
            $data['user'] = [
                'id' => $document->user->id,
                'name' => $document->user->name,
                'email' => $document->user->email,
                'phone' => $document->user->phone ?? null,
            ];
        }

        return $data;
    }

    public function getFileUrl($file_path)
    {
        $file_path = Storage::url($file_path);

        return asset($file_path);
    }
}
