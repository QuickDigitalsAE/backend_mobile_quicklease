<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SidebarBanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SidebarBannerController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:SidebarBanners View', ['only' => ['getSideBanner','edit']]);
        $this->middleware('permission:SidebarBanners Add', ['only' => ['postSideBanner']]);
        $this->middleware('permission:SidebarBanners Edit', ['only' => ['updateSideBanner']]);
        $this->middleware('permission:SidebarBanners Delete', ['only' => ['deleteSideBanner']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSideBanner($lang = 'en', $id = "")
    {
        try {
            if(!empty($id)){
                $data = $this->edit($lang, $id);
            }else{
                $sidebarBanners = SidebarBanner::where('language',$lang)
                                    ->orderBy('sort_order')->get();

                $data = $sidebarBanners->map(function ($sidebarBanner) {
                    return [
                        'id'        => $sidebarBanner->id,
                        'title'        => $sidebarBanner->title,
                        'banner_image'        => $this->getImageUrl($sidebarBanner->image),
                        'redirect_url' => $sidebarBanner->redirect_url,
                        'status' => $sidebarBanner->status
                    ];
                });
            }
    
            return response()->json([
                'status'  => true,
                'message' => 'Sidebar banners fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getRadomBanner($lang = 'en')
    {
        try {
            
           $sidebarBanners = SidebarBanner::where('status',1)
                                ->where('language', $lang)
                                ->get();
                                
            // Fallback to English if no banners found for selected language
            if ($sidebarBanners->isEmpty() && $lang !== 'en') {
                $sidebarBanners = SidebarBanner::where('status', 1)
                    ->where('language', 'en')
                    ->get();
            }

            $data = $sidebarBanners->map(function ($sidebarBanner) {
                return [
                    'id'        => $sidebarBanner->id,
                    'title'        => $sidebarBanner->title,
                    'banner_image'        => $this->getImageUrl($sidebarBanner->image),
                    'redirect_url' => $sidebarBanner->redirect_url,
                    'status' => $sidebarBanner->status
                ];
            });
            
            return response()->json([
                'status'  => true,
                'message' => 'Sidebar banners fetched successfully',
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
    public function postSideBanner(Request $request, $lang)
    {
        DB::beginTransaction(); // Start transaction
    
        try {
            $sidebarBanner = new SidebarBanner();
            
            // Handle image uploads for primary fields
            if (!empty($request->banner_image)) {
                $sidebarBanner->image = $request->banner_image;
            }
            
            if ($request->filled('title')) {
                $sidebarBanner->title = $request->title;
            }
            
            if ($request->filled('redirect_url')) {
                $sidebarBanner->redirect_url = $request->redirect_url;
            }
            
            // Set sort_order based on the last value
            $lastOrder = SidebarBanner::max('sort_order');
            $sidebarBanner->sort_order = is_null($lastOrder) ? 0 : $lastOrder + 1;
            $sidebarBanner->language = $lang;
            $sidebarBanner->status = $request->status;
            $sidebarBanner->save(); // Save the newly created primary content
    
            DB::commit(); // Commit transaction
    
            return response()->json([
                'status'  => true,
                'message' => 'Sidebar banner saved successfully'
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
    public function edit($lang = 'en', $id)
    {
        $sidebarBanner = SidebarBanner::where('language',$lang)
                            ->find($id);

        if (!$sidebarBanner) {
            return [];
        }
        $data = [
                'id'        => $sidebarBanner->id,
                'title'        => $sidebarBanner->title,
                'banner_image'        => $this->getImageUrl($sidebarBanner->image),
                'redirect_url' => $sidebarBanner->redirect_url,
                'status' => $sidebarBanner->status
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
    public function updateSideBanner(Request $request, $lang, $id)
    {
        DB::beginTransaction();
    
        try {
            $sidebarBanner = SidebarBanner::where('language',$lang)
                                ->find($id);
    
            if (!$sidebarBanner) {
                return response()->json(['status' => false, 'message' => 'Side banner not found'], 404);
            }
            
            // Update image if provided
            if (!empty($request->banner_image)) {
                // Delete the old image if it exists
                if ($sidebarBanner->image && Storage::disk('public')->exists($sidebarBanner->image)) {
                    Storage::disk('public')->delete($sidebarBanner->image);
                }
                
                // Upload new image
                $sidebarBanner->image = $request->banner_image;
            }
            
            // Update other fields
            if ($request->filled('title')) {
                $sidebarBanner->title = $request->title;
            }
            
            if ($request->filled('redirect_url')) {
                $sidebarBanner->redirect_url = $request->redirect_url;
            }
            $sidebarBanner->status = $request->status;
            $sidebarBanner->save();
    
            DB::commit();
    
            return response()->json([
                'status'  => true,
                'message' => 'Sidebar banner updated successfully',
                'data'    => $sidebarBanner
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
    public function deleteSideBanner($id)
    {
        try {
            $sidebarBanner = SidebarBanner::find($id);
    
            if (!$sidebarBanner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Side banner not found'
                ], 404);
            }
    
            $sidebarBanner->delete(); // Soft delete
    
            return response()->json([
                'status'  => true,
                'message' => 'Sidebar banner deleted successfully'
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
