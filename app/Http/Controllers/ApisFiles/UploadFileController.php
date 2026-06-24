<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadFileController extends Controller
{
    public function uploadSingleImage(Request $request)
    {
        try {
            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/svg+xml',
                'image/bmp',
                'image/tiff',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/csv',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-ms-wmv',
                'video/webm',
                'video/x-matroska',
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/mp4',
                'application/octet-stream',
            ];

            $validator = Validator::make($request->all(), [
                'folder_name' => 'required|string|max:100',
                'page_type' => 'required|string|max:100',
                'image' => [
                    'required',
                    'file',
                    'max:102400',
                    function ($attribute, $value, $fail) use ($allowedMimes) {
                        if (!in_array($value->getMimeType(), $allowedMimes, true)) {
                            $fail('The selected file type is not supported.');
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!$request->hasFile('image')) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not received',
                ], 400);
            }

            $file = $request->file('image');

            if (!$file->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or corrupted file',
                ], 400);
            }

            $folderName = $this->sanitizePathSegment($request->folder_name);
            $pageType = $this->sanitizePathSegment($request->page_type);
            $directory = trim($folderName . '/' . $pageType, '/');

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeOriginalName = Str::slug($originalName) ?: 'file';
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
            $fileName = $safeOriginalName . '-' . Carbon::now()->format('YmdHis') . '-' . Str::lower(Str::random(10)) . '.' . $extension;
            $mimeType = $file->getMimeType();
            $mediaType = $this->resolveMediaType($mimeType);

            $filePath = $file->storeAs($directory, $fileName, 'public');

            if (!$filePath) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to upload file',
                ], 500);
            }

            $fileUrl = $this->getFileUrl($filePath);

            return response()->json([
                'status' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'image_path' => $filePath,
                    'image_full_path' => $fileUrl,
                    'page_type' => $request->page_type,
                ],
            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    public function getFileUrl($file_path)
    {
        $file_path = Storage::url($file_path);

        return asset($file_path);
    }

    private function sanitizePathSegment(string $value): string
    {
        return Str::slug($value) ?: 'uploads';
    }

    private function resolveMediaType(?string $mimeType): string
    {
        if (!$mimeType) {
            return 'file';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/csv',
        ], true)) {
            return 'document';
        }

        if (in_array($mimeType, [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ], true)) {
            return 'archive';
        }

        return 'file';
    }
}
