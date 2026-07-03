<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\PopupBanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PopupBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:PopupBanners View', ['only' => ['index', 'show']]);
        $this->middleware('permission:PopupBanners Add', ['only' => ['store']]);
        $this->middleware('permission:PopupBanners Edit', ['only' => ['update']]);
        $this->middleware('permission:PopupBanners Delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PopupBanner::query();

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('redirect_link', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('from_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('to_date', '<=', $request->to_date);
            }

            $perPage = (int) $request->input('per_page', 10);
            $query->orderBy('created_at', 'DESC');

            if ($perPage === 0) {
                $popupBanners = $query->get();
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $popupBanners->count(),
                    'total' => $popupBanners->count(),
                ];
            } else {
                $popupBanners = $query->paginate($perPage);
                $pagination = [
                    'current_page' => $popupBanners->currentPage(),
                    'last_page' => $popupBanners->lastPage(),
                    'per_page' => $popupBanners->perPage(),
                    'total' => $popupBanners->total(),
                ];
            }

            if ($popupBanners->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Popup banners not found',
                    'data' => [],
                ], 200);
            }

            $data = $popupBanners->map(function (PopupBanner $popupBanner) {
                return $this->formatPopupBanner($popupBanner);
            });

            return response()->json([
                'status' => true,
                'message' => 'Popup banners fetched successfully',
                'data' => $data,
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function frontendPopupBanner(Request $request)
    {
        try {
            $today = Carbon::today();

            $popupBanner = PopupBanner::query()
                ->where('status', 1)
                ->whereDate('from_date', '<=', $today)
                ->whereDate('to_date', '>=', $today)
                ->orderBy('created_at', 'DESC')
                ->first();

            if (!$popupBanner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Popup banner not found',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Popup banner fetched successfully',
                'data' => $this->formatPopupBanner($popupBanner),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'status' => 'required|integer',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after:from_date',
                'redirect_link' => 'nullable|string|max:2048',
                'attachment' => 'nullable|string|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => implode("\n", $validator->errors()->all()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $popupBanner = new PopupBanner();
            $popupBanner->title = $request->title;
            $popupBanner->status = (int) $request->status;
            $popupBanner->from_date = $request->from_date ?: null;
            $popupBanner->to_date = $request->to_date ?: null;
            $popupBanner->redirect_link = $request->redirect_link ?: null;
            $popupBanner->attachment = $this->normalizeAttachmentPath($request->attachment);

            $popupBanner->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Popup banner created successfully',
                'data' => $this->formatPopupBanner($popupBanner),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $popupBanner = PopupBanner::find($id);

            if (!$popupBanner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Popup banner not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status' => true,
                'message' => 'Popup banner fetched successfully',
                'data' => $this->formatPopupBanner($popupBanner),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'status' => 'required|integer',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after:from_date',
                'redirect_link' => 'nullable|string|max:2048',
                'attachment' => 'nullable|string|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => implode("\n", $validator->errors()->all()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $popupBanner = PopupBanner::find($id);

            if (!$popupBanner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Popup banner not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->filled('attachment')) {
                $popupBanner->attachment = $this->normalizeAttachmentPath($request->attachment);
            }

            $popupBanner->title = $request->title;
            $popupBanner->status = (int) $request->status;
            $popupBanner->from_date = $request->from_date ?: null;
            $popupBanner->to_date = $request->to_date ?: null;
            $popupBanner->redirect_link = $request->redirect_link ?: null;
            $popupBanner->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Popup banner updated successfully',
                'data' => $this->formatPopupBanner($popupBanner),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $popupBanner = PopupBanner::find($id);

            if (!$popupBanner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Popup banner not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($popupBanner->attachment && Storage::disk('public')->exists($popupBanner->attachment)) {
                Storage::disk('public')->delete($popupBanner->attachment);
            }

            $popupBanner->delete();

            return response()->json([
                'status' => true,
                'message' => 'Popup banner deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function formatPopupBanner(PopupBanner $popupBanner): array
    {
        return [
            'id' => $popupBanner->id,
            'title' => $popupBanner->title,
            'status' => (int) $popupBanner->status,
            'from_date' => optional($popupBanner->from_date)->format('Y-m-d'),
            'to_date' => optional($popupBanner->to_date)->format('Y-m-d'),
            'attachment' => $popupBanner->attachment,
            'attachment_url' => $popupBanner->attachment ? $this->getAttachmentUrl($popupBanner->attachment) : null,
            'redirect_link' => $popupBanner->redirect_link,
            'created_at' => $popupBanner->created_at,
            'updated_at' => $popupBanner->updated_at,
        ];
    }

    protected function getAttachmentUrl($attachmentPath)
    {
        if (preg_match('/^https?:\\/\\//i', $attachmentPath)) {
            return $attachmentPath;
        }

        return asset(Storage::url($attachmentPath));
    }

    protected function normalizeAttachmentPath(?string $attachmentPath): ?string
    {
        if (empty($attachmentPath)) {
            return null;
        }

        return ltrim($attachmentPath, '/');
    }
}
