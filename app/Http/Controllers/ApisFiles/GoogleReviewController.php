<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoogleReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GoogleReviewController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Reviews View', ['only' => ['getReview','edit']]);
        $this->middleware('permission:Reviews Add', ['only' => ['postReview']]);
        $this->middleware('permission:Reviews Edit', ['only' => ['patchReview']]);
        $this->middleware('permission:Reviews Delete', ['only' => ['deleteReview']]);
    }
    
    public function getReview($id = "")
    {
        try {
            if(!empty($id)){
                $data = $this->edit($id);
            }else{
                $reviews = GoogleReview::all();
        
                $data = $reviews->map(function ($review) {
                    return [
                        'id'        => $review->id,
                        'rating'        => $review->rating,
                        'image'        => $this->getImageUrl($review->image),
                        'redirect_url' => $review->redirect_url
                    ];
                });
            }
    
            return response()->json([
                'status'  => true,
                'message' => 'Reviews fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postReview(Request $request)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $googleReview = new GoogleReview();
    
            // Handle image uploads for primary fields
            if (!empty($request->review_image)) {
                $googleReview->image = $request->review_image;
            }
    
            $googleReview->rating        = $request->rating;
            $googleReview->redirect_url = $request->redirect_url;
            $googleReview->save(); // Save the newly created primary content
    
            DB::commit(); // Commit transaction
    
            return response()->json([
                'status'  => true,
                'message' => 'Review saved successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $singleReview = GoogleReview::find($id);

        if (!$singleReview) {
            return [];
        }
        $data = [
                'id'        => $singleReview->id,
                'rating'        => $singleReview->rating,
                'image'        => $this->getImageUrl($singleReview->image),
                'redirect_url' => $singleReview->redirect_url
            ];
        return $data;
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function patchReview(Request $request, $id)
    {
        DB::beginTransaction();
    
        try {
            $review = GoogleReview::find($id);
    
            if (!$review) {
                return response()->json(['status' => false, 'message' => 'Review not found'], 404);
            }
    
            // Update image if provided
            if (!empty($request->review_image)) {
                // Delete the old image if it exists
                if ($review->image && Storage::disk('public')->exists($review->image)) {
                    Storage::disk('public')->delete($review->image);
                }
                
                // Upload new image
                $review->image = $request->review_image;
            }
            
            // Update other fields
            if ($request->filled('rating')) {
                $review->rating = $request->rating;
            }
            
            if ($request->filled('redirect_url')) {
                $review->redirect_url = $request->redirect_url;
            }

            $review->save();
    
            DB::commit();
    
            return response()->json([
                'status'  => true,
                'message' => 'Review updated successfully',
                'data'    => $review
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteReview($id)
    {
        try {
            $review = GoogleReview::find($id);
    
            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found'
                ], 404);
            }
    
            $review->delete(); // Soft delete
    
            return response()->json([
                'status'  => true,
                'message' => 'Review deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
