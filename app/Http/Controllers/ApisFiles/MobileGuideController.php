<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileGuide;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MobileGuideController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getGuide($id = "")
    {
        try {
            if(!empty($id)){
                $data = $this->edit($id);
            }else{
                $guides = MobileGuide::where('status', 1)->get();
        
                $data = $guides->map(function ($guide) {
                    return [
                        'id'        => $guide->id,
                        'title'        => $guide->title,
                        'description'  => $guide->description,
                        'image'        => $this->getImageUrl($guide->image),
                        'button_text'  => $guide->button_text,
                        'redirect_url' => $guide->redirect_url,
                        'status' => $guide->status
                    ];
                });
            }
    
            return response()->json([
                'status'  => true,
                'message' => 'Guide fetched successfully',
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
    public function postGuide(Request $request)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $mobileGuide = new MobileGuide();
    
            // Handle image uploads for primary fields
            if ($request->hasFile('guide_image')) {
                $guide_image = $request->file('guide_image'); 
                $imagePath = $guide_image->store('guide_images', 'public');
    
                // Save image path
                $mobileGuide->image = $imagePath;
            }
    
            $mobileGuide->title        = $request->title;
            $mobileGuide->description  = $request->description;
            $mobileGuide->button_text  = $request->button_text;
            $mobileGuide->redirect_url = $request->redirect_url;
            $mobileGuide->status       = $request->status;
            $mobileGuide->save(); // Save the newly created primary content
    
            DB::commit(); // Commit transaction
    
            return response()->json([
                'status'  => true,
                'message' => 'App guide content saved successfully'
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
        $guide = MobileGuide::find($id);

        if (!$guide) {
            return [];
        }
        $data = [
                'id'        => $guide->id,
                'title'        => $guide->title,
                'description'  => $guide->description,
                'image'        => $this->getImageUrl($guide->image),
                'button_text'  => $guide->button_text,
                'redirect_url' => $guide->redirect_url,
                'status'       => $guide->status
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
    public function patchGuide(Request $request, $id)
    {
        DB::beginTransaction();
    
        try {
            $guide = MobileGuide::find($id);
    
            if (!$guide) {
                return response()->json(['status' => false, 'message' => 'Guide not found'], 404);
            }
    
            // Handle updated image
            if ($request->hasFile('guide_image')) {
                // Delete old image
                if ($guide->image && Storage::disk('public')->exists($guide->image)) {
                    Storage::disk('public')->delete($guide->image);
                }
    
                $guide_image = $request->file('guide_image');
                $imagePath = $guide_image->store('guide_images', 'public');
                $guide->image = $imagePath;
            }
            
            // Update other fields
            if ($request->filled('title')) {
                $guide->title = $request->title;
            }
            
            if ($request->filled('description')) {
                $guide->description = $request->description;
            }
            
            if ($request->filled('button_text')) {
                $guide->button_text = $request->button_text;
            }
            
            if ($request->filled('redirect_url')) {
                $guide->redirect_url = $request->redirect_url;
            }

            if ($request->filled('status')) {
                $guide->status = $request->status;
            }

            $guide->save();
    
            DB::commit();
    
            return response()->json([
                'status'  => true,
                'message' => 'Guide updated successfully',
                'data'    => $guide
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
    public function deleteGuide($id)
    {
        try {
            $guide = MobileGuide::find($id);
    
            if (!$guide) {
                return response()->json([
                    'status' => false,
                    'message' => 'Guide not found'
                ], 404);
            }
    
            $guide->delete(); // Soft delete
    
            return response()->json([
                'status'  => true,
                'message' => 'Guide deleted successfully'
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
