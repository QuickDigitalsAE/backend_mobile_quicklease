<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UploadImageController extends Controller
{
     /* Image insertion part POST */
    public function uploadSingleImage(Request $request)
    {
        try {

            $rules = [
                'folder_name' => 'required|string',
                'page_type'   => 'required|string',
                'image' => 'required|file|mimes:jpg,png,jpeg,webp,svg,mp4,mov,avi,webm|max:51200'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // ❌ Image key exists but upload failed (PHP level)
            if (!$request->hasFile('image')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Image file not received'
                ], 400);
            }

            $image = $request->file('image');

            // ❌ File exists but corrupted / invalid
            if (!$image->isValid()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid or corrupted image file'
                ], 400);
            }

            $imagePath = $image->store($request->folder_name, 'public');

            // ❌ Storage failure
            if (!$imagePath) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to upload image'
                ], 500);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Image uploaded successfully',
                'data'    => [
                    'image_path'      => $imagePath,
                    'image_full_path' => $this->getImageUrl($imagePath),
                    'page_type'       => $request->page_type
                ]
            ], 200);

        } catch (\Throwable $ex) {
            return response()->json([
                'status'  => false,
                'message' => 'Server error',
                'error'   => $ex->getMessage()
            ], 500);
        }
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
