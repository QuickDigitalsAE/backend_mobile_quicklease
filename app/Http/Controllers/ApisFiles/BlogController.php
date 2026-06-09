<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\SidebarBannerController;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\BlogTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BlogController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Blogs View', ['only' => ['blogsList','blogSingleDetail','searchBlogsList']]);
        $this->middleware('permission:Blogs Add', ['only' => ['storeBlog']]);
        $this->middleware('permission:Blogs Edit', ['only' => ['updateBlog']]);
        $this->middleware('permission:Blogs Delete', ['only' => ['deleteBlog']]);
    }

    // Get all blogs list
    public function blogsList($lang, $per_page=6)
    {
        try {
            $blogQuery = Blog::orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            // Check if full blog list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $blogs = $blogQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $blogs->count(), // All items in one "page"
                    'total' => $blogs->count(),
                ];
            } else {
                // Paginate the remaining blogs
                $blogs = $blogQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'per_page' => $blogs->perPage(),
                    'total' => $blogs->total(),
                ];
            }
            
            if($blogs->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'Blogs not found'], 200);
            }
            
            $blogs_translations = $blogs->map(function($blog) use ($lang) {
                $id = $blog->id;
                $created_by = $blog->created_by;
                $updated_by = $blog->updated_by;
                $created_at = $blog->created_at;
                $updated_at = $blog->updated_at;
                
                 // Get translation based on language or default 'en' based
                $translations = BlogTranslation::where('blog_id', $id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, '$lang', 'en')")
                    ->get()
                    ->keyBy('language');
                
                // Decode JSON translations
                $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
                
                $created_by_name = $this->getUserName($created_by);
                $updated_by_name = $this->getUserName($updated_by);
                
                // Handle image URLs for primary fields
                $translatedData['id'] = $id;
                $translatedData['created_by'] = $created_by_name;
                $translatedData['updated_by'] = $updated_by_name;
                $translatedData['created_at'] = $created_at;
                $translatedData['updated_at'] = $updated_at;
                $translatedData['blog_status'] = (int) $blog->blog_status;
                $translatedData['table_of_content'] = (int) $blog->table_of_content;
                $translatedData['blog_schedule'] = $blog->blog_schedule;
                $translatedData['blog_date_time'] = $blog->blog_schedule ? Carbon::parse($blog->blog_schedule)->format('l, d M Y') : Carbon::parse($blog->created_at)->format('l, d M Y');
                $translatedData['blog_slug'] = $blog->slug;
                $translatedData['blog_image'] = $blog->image ? $this->getImageUrl($blog->image) : null;
               
                return $translatedData;
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $blogs_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Get all blogs frontend list
    public function blogsFrontendList($lang, $per_page = 6)
    {
        try {
            $currentDateTime = Carbon::now('Asia/Karachi');
            $search = request()->input('search');
    
            /*
            |--------------------------------------------------------------------------
            | Recent Blog - Search se affect nahi hoga
            |--------------------------------------------------------------------------
            */
            $recentBlog = Blog::where('blog_status', 1)
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('blog_schedule', '<=', $currentDateTime)
                        ->orWhereNull('blog_schedule');
                })
                ->orderBy('blog_schedule', 'DESC')
                ->first();
    
            if (!$recentBlog) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Blogs not found'
                ], 200);
            }
    
            $translations = BlogTranslation::where('blog_id', $recentBlog->id)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                ->get()
                ->keyBy('language');
    
            $recentTranslatedData = isset($translations[$lang])
                ? json_decode($translations[$lang]->field_values, true)
                : json_decode($translations['en']->field_values ?? '{}', true);
    
            $recentTranslatedData['id'] = $recentBlog->id;
            $recentTranslatedData['blog_status'] = (int) $recentBlog->blog_status;
            $recentTranslatedData['table_of_content'] = (int) $recentBlog->table_of_content;
            $recentTranslatedData['blog_schedule'] = $recentBlog->blog_schedule;
            $recentTranslatedData['blog_date_time'] = $recentBlog->blog_schedule
                ? Carbon::parse($recentBlog->blog_schedule)->format('l, d M Y')
                : Carbon::parse($recentBlog->created_at)->format('l, d M Y');
            $recentTranslatedData['blog_slug'] = $recentBlog->slug;
            $recentTranslatedData['blog_image'] = $recentBlog->image ? $this->getImageUrl($recentBlog->image) : null;
    
            /*
            |--------------------------------------------------------------------------
            | All Blogs - Search sirf yahan chalega
            |--------------------------------------------------------------------------
            */
            $blogQuery = Blog::query();
    
            // Search empty ho to recent blog all_blogs se exclude hoga
            // Search ho to recent blog bhi search result me aa sakta hai
            if (empty($search)) {
                $blogQuery->where('id', '!=', $recentBlog->id);
            }
    
            $blogQuery->where('blog_status', 1)
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('blog_schedule', '<=', $currentDateTime)
                        ->orWhereNull('blog_schedule');
                })
                ->orderBy('blog_schedule', 'DESC');
    
            $perPage = request()->input('per_page', $per_page);
    
            /*
            |--------------------------------------------------------------------------
            | If search exists, get all blogs first, then search after removing HTML tags
            |--------------------------------------------------------------------------
            */
            if (!empty($search)) {
                $searchText = strtolower(trim(strip_tags(html_entity_decode($search, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
                $searchText = preg_replace('/\s+/', ' ', $searchText);
    
                $allSearchBlogs = $blogQuery->get();
    
                $filteredBlogs = $allSearchBlogs->filter(function ($blog) use ($lang, $searchText) {
                    $translations = BlogTranslation::where('blog_id', $blog->id)
                        ->whereIn('language', [$lang, 'en'])
                        ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                        ->get()
                        ->keyBy('language');
    
                    $translatedData = isset($translations[$lang])
                        ? json_decode($translations[$lang]->field_values, true)
                        : json_decode($translations['en']->field_values ?? '{}', true);
    
                    $content = implode(' ', [
                        $translatedData['meta_title'] ?? '',
                        $translatedData['meta_description'] ?? '',
                        $translatedData['blog_title'] ?? '',
                        $translatedData['blog_paragraph'] ?? '',
                        $blog->slug ?? '',
                    ]);
    
                    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $content = strip_tags($content);
                    $content = preg_replace('/\s+/', ' ', $content);
                    $content = strtolower(trim($content));
    
                    return str_contains($content, $searchText);
                })->values();
    
                $total = $filteredBlogs->count();
    
                if ($perPage == 0) {
                    $blogs = $filteredBlogs;
    
                    $pagination = [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $blogs->count(),
                        'total' => $total,
                    ];
                } else {
                    $currentPage = (int) request()->input('page', 1);
                    $offset = ($currentPage - 1) * $perPage;
    
                    $blogs = $filteredBlogs->slice($offset, $perPage)->values();
    
                    $pagination = [
                        'current_page' => $currentPage,
                        'last_page' => (int) ceil($total / $perPage),
                        'per_page' => (int) $perPage,
                        'total' => $total,
                    ];
                }
            } else {
                if ($perPage == 0) {
                    $blogs = $blogQuery->get();
    
                    $pagination = [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $blogs->count(),
                        'total' => $blogs->count(),
                    ];
                } else {
                    $blogs = $blogQuery->paginate($perPage);
    
                    $pagination = [
                        'current_page' => $blogs->currentPage(),
                        'last_page' => $blogs->lastPage(),
                        'per_page' => $blogs->perPage(),
                        'total' => $blogs->total(),
                    ];
                }
            }
    
            $allBlogs = $blogs->map(function ($blog) use ($lang) {
                $translations = BlogTranslation::where('blog_id', $blog->id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->get()
                    ->keyBy('language');
    
                $translatedData = isset($translations[$lang])
                    ? json_decode($translations[$lang]->field_values, true)
                    : json_decode($translations['en']->field_values ?? '{}', true);
    
                $translatedData['id'] = $blog->id;
                $translatedData['created_by'] = $this->getUserName($blog->created_by);
                $translatedData['updated_by'] = $this->getUserName($blog->updated_by);
                $translatedData['created_at'] = $blog->created_at;
                $translatedData['updated_at'] = $blog->updated_at;
                $translatedData['blog_status'] = (int) $blog->blog_status;
                $translatedData['table_of_content'] = (int) $blog->table_of_content;
                $translatedData['blog_schedule'] = $blog->blog_schedule;
                $translatedData['blog_date_time'] = $blog->blog_schedule
                    ? Carbon::parse($blog->blog_schedule)->format('l, d M Y')
                    : Carbon::parse($blog->created_at)->format('l, d M Y');
                $translatedData['blog_slug'] = $blog->slug;
                $translatedData['blog_image'] = $blog->image ? $this->getImageUrl($blog->image) : null;
    
                return $translatedData;
            });
    
            $webContentController = new WebContentController();
    
            $blogsMeta = $webContentController->getWebMetaDeta('blogs', $lang);
            $metaData = $blogsMeta->original['data'] ?? [];
    
            return response()->json([
                'status' => 'true',
                'message' => 'Blogs retrieved successfully',
                'data' => [
                    'recent_blog' => $recentTranslatedData,
                    'all_blogs' => $allBlogs,
                    'meta' => $metaData
                ],
                'pagination' => $pagination
            ], 200);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Blog data insertion part POST/{lang} */
    public function storeBlog(Request $request, $lang)
    {
        try {
             // Define validation rules
            $rules = [
                'blog_slug'  => 'required|string|unique:blogs,slug',
                'blog_status' => 'required|numeric',
                'table_of_content' => 'required|numeric',
                'blog_schedule' => 'nullable|date_format:Y-m-d H:i', 
                'translation' => 'array'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }else{
                
                $user = Auth::user();
                $userId = $user->id;
                
                $blogSlug = $request->input('blog_slug');
                $blogStatus = $request->input('blog_status');
                $blogSchedule = $request->input('blog_schedule');
                $tableOfContent = $request->input('table_of_content');
    
                $blog = new Blog();
                // Handle image uploads for primary fields
                if (!empty($request->blog_image)) {
                    $blog->image = $request->blog_image;
                }
                
                $blog->slug = $blogSlug;
                $blog->blog_status = $blogStatus;
                $blog->blog_schedule = $blogSchedule ?? null;
                $blog->table_of_content = $tableOfContent;
                $blog->created_by = $userId;
                $blog->save();
        
                $blogId = $blog->id;
                $translations = $request->input('translation', []);
                
                $blog_translation = new BlogTranslation();
                $blog_translation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $blog_translation->language = $lang;
                $blog_translation->blog_id = $blogId;
                $blog_translation->save();

                return response()->json([
                    'status' => 'true',
                    'message' => 'Blog created successfully',
                    'data' => [
                        'blog_id' => $blogId,
                        ]
                    ], 200);

            }

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Blog tab data update part PUT/{id}/{lang} */
    public function updateBlog(Request $request, $id, $lang)
    {
        try {
            // Define validation rules
            $rules = [
                'blog_slug' => 'required|string|unique:blogs,slug,' . $id . ',id',
                'blog_status' => 'required|numeric',
                'table_of_content' => 'nullable|numeric',
                'blog_schedule' => 'nullable|date_format:Y-m-d H:i', 
                'translation' => 'array'
            ];

    
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $user = Auth::user();
                $userId = $user->id;
                
                $blogSlug = $request->input('blog_slug');
                $blogStatus = $request->input('blog_status');
                $blogSchedule = $request->input('blog_schedule');
                $tableOfContent = $request->input('table_of_content');
    
                $blog = Blog::find($id);
                if (!$blog) {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Blog not found'
                    ], Response::HTTP_NOT_FOUND);
                }
    
                // Update image if provided
                if (!empty($request->blog_image)) {
                    // Delete the old image if it exists
                    if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                        Storage::disk('public')->delete($blog->image);
                    }
                    
                    // Upload new image
                    $blog->image = $request->blog_image;
                }
    
                $blog->slug = $blogSlug;
                $blog->blog_status = $blogStatus;
                $blog->blog_schedule = $blogSchedule ?? null;
                $blog->table_of_content = $tableOfContent;
                $blog->updated_by = $userId;
                $blog->save();
    
                $translations = $request->input('translation', []);
    
    
                // Update translations
                $blogTranslation = BlogTranslation::where('blog_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                if (!$blogTranslation) {
                    $blogTranslation = new BlogTranslation();
                    $blogTranslation->blog_id = $id;
                    $blogTranslation->language = $lang;
                }
    
                $blogTranslation->field_values = json_encode($translations, JSON_UNESCAPED_UNICODE);
                $blogTranslation->save();
    
                return response()->json(['status' => 'true', 'message' => 'Blog updated successfully'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* blogs tab data fetch part GET/{id}/{lang} */
    public function blogSingleDetail($id, $lang)
    {
        try {
            $blog = Blog::where('id',$id)->first();

            if(!$blog){
                return response()->json(['status' => 'false', 'message' => 'Blog not found'], Response::HTTP_NOT_FOUND);
            }
            
            $blogId = $blog->id;
            
            // Get translation based on language or default 'en' based
            $translations = BlogTranslation::where('blog_id', $blogId)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $blog->id;
            $translatedData['blog_status'] = (int) $blog->blog_status;
            $translatedData['table_of_content'] = (int) $blog->table_of_content;
            $translatedData['blog_schedule'] = $blog->blog_schedule;
            $translatedData['blog_slug'] = $blog->slug;
            $translatedData['blog_image'] = $blog->image ? $this->getImageUrl($blog->image) : null;
                
            return response()->json([
                'status' => 'true',
                'data' => $translatedData
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function blogFetchDetail($slug, $lang)
    {
        try {
            $blog = Blog::where('blog_status', '=', 1)
                        ->where('slug',$slug)->first();

            if(!$blog){
                return response()->json(['status' => 'false', 'message' => 'Blog not found'], Response::HTTP_NOT_FOUND);
            }
            
            $blogId = $blog->id;
            
            // Get translation based on language or default 'en' based
            $translations = BlogTranslation::where('blog_id', $blogId)
                ->whereIn('language', [$lang, 'en'])
                ->orderByRaw("FIELD(language, '$lang', 'en')")
                ->get()
                ->keyBy('language');
            
            // Decode JSON translations
            $translatedData = isset($translations[$lang]) ? json_decode($translations[$lang]->field_values, true) : json_decode($translations['en']->field_values, true);
            
            
            // Handle image URLs for primary fields
            $translatedData['id'] = $blog->id;
            $translatedData['blog_status'] = (int) $blog->blog_status;
            $translatedData['table_of_content'] = (int) $blog->table_of_content;
            $translatedData['blog_date_time'] = $blog->blog_schedule ? Carbon::parse($blog->blog_schedule)->format('l, d M Y') : Carbon::parse($blog->created_at)->format('l, d M Y');
            $translatedData['blog_slug'] = $blog->slug;
            $translatedData['blog_image'] = $blog->image ? $this->getImageUrl($blog->image) : null;
            
            // Fetch blogs meta
            $webContentController = new WebContentController();
            $blogsMeta =  $webContentController->getWebMetaDeta('blogs',$lang);
            
            if($blogsMeta->original['data']){
                $metaData = $blogsMeta->original['data'];
                
                $translatedData['blog_banner_image'] = $metaData['banner'];
            }
                
            $relatedBlogs = $this->relatedBlogs($blog, $lang);
            $afterMergeArray = array_merge($translatedData,$relatedBlogs);
            
            // Fetch blogs meta
            $sidebarBannerController = new SidebarBannerController();
            $blogSideBanners =  $sidebarBannerController->getRadomBanner($lang);
            
            if($blogSideBanners->original['data']){
                $sidebar_banners = $blogSideBanners->original['data'];
            }else{
                $sidebar_banners = [];
            }
            
            return response()->json([
                'status' => 'true',
                'data' => $afterMergeArray,
                'sidebar_banners' => $sidebar_banners
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    protected function relatedBlogs($blog, $lang)
    {
        
        // Fetch the previous blog
        $previousBlog = Blog::where('created_at', '<', $blog->created_at)
                            ->where('blog_status','=',1)    
                            ->orderBy('created_at', 'DESC')
                            ->first();
        $blogsData = array();
        $previousData = null;
        if ($previousBlog) {
            $prevTranslation = BlogTranslation::where('blog_id', $previousBlog->id)
                ->where('language', $lang)
                ->first();
                
            $prevTranslatedData = [];
            if (!empty($prevTranslation)) {
                // Decode the JSON translation data
                $prevTranslatedData = json_decode($prevTranslation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = BlogTranslation::where('blog_id', $previousBlog->id)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $prevTranslatedData = json_decode($defaultData->field_values, true);
                }
            }
            
            $previousData = [
                'slug'  => $previousBlog->slug,
                'title' => $prevTranslatedData ? $prevTranslatedData['blog_title'] ?? '' : ''
            ];
        }

        // Fetch the next blog
        $nextBlog = Blog::where('created_at', '>', $blog->created_at)
                        ->where('blog_status','=',1)
                        ->orderBy('created_at', 'ASC')
                        ->first();

        $nextData = null;
        if ($nextBlog) {
            $nextTranslation = BlogTranslation::where('blog_id', $nextBlog->id)
                ->where('language', $lang)
                ->first();
                
            $nextTranslatedData = [];
            if (!empty($nextTranslation)) {
                // Decode the JSON translation data
                $nextTranslatedData = json_decode($nextTranslation->field_values, true);
            } else {
                // Fetch default language data if translation not found
                $defaultData = BlogTranslation::where('blog_id', $nextBlog->id)
                    ->where('language', 'en')
                    ->first();
    
                if (!empty($defaultData)) {
                    $nextTranslatedData = json_decode($defaultData->field_values, true);
                }
            }    
            
            $nextData = [
                'slug' => $nextBlog->slug,
                'title' => $nextTranslatedData ? $nextTranslatedData['blog_title'] ?? '' : ''
            ];
        }

        // Include previous and next blog data
        $blogsData['previous_blog'] = $previousData;
        $blogsData['next_blog'] = $nextData;
        
        return $blogsData;
    }

    /* Blogs data fetch part DELETE/{id} */
    public function deleteBlog($id)
    {
        try {
            $blog = Blog::find($id);
    
            if (!$blog) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Blog not found'
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Delete blog image if it exists
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
    
            // Delete associated translations
            BlogTranslation::where('blog_id', $id)->delete();
    
            // Delete the blog record
            $blog->delete();
    
            return response()->json(['status' => 'true', 'message' => 'Blog deleted successfully'], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Blogs list search function
    public function searchBlogsList($lang, $per_page = 6)
    {
        try {
            $searchQuery = request()->input('search_query', null);
            $blogQuery = Blog::query()
            ->join('blog_translations', function ($join) use ($lang) {
                $join->on('blogs.id', '=', 'blog_translations.blog_id')
                    ->where('blog_translations.language', '=', $lang);
            })
            ->select('blogs.*', 'blog_translations.field_values');

            // Apply search filters for both slug and blog_title
            if (!empty($searchQuery)) {
                $blogQuery->where(function ($query) use ($searchQuery) {
                    $query->where('blogs.slug', 'LIKE', "%{$searchQuery}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(blog_translations.field_values, '$.blog_title')) LIKE ?", ["%{$searchQuery}%"]);
                });
            }
    
            $blogQuery->orderBy('created_at', 'DESC');
            $perPage = request()->input('per_page', $per_page);
            
            
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $blogs = $blogQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $blogs->count(), // All items in one "page"
                    'total' => $blogs->count(),
                ];
            } else {
                // Paginate the remaining blogs
                $blogs = $blogQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'per_page' => $blogs->perPage(),
                    'total' => $blogs->total(),
                ];
            }
        
            if ($blogs->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No blogs found'], 200);
            }
    
            $blogs_translations = $blogs->map(function ($blog) use ($lang) {
                $id = $blog->id;
                $translation = BlogTranslation::where('blog_id', $id)
                    ->where('language', $lang)
                    ->first();
    
                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->field_values, true);
                } else {
                    $defaultData = BlogTranslation::where('blog_id', $id)
                        ->where('language', 'en')
                        ->first();
    
                    if (!empty($defaultData)) {
                        $translatedData = json_decode($defaultData->field_values, true);
                    }
                }
    
                $translatedData['id'] = $id;
                $translatedData['blog_status'] = (int) $blog->blog_status;
                $translatedData['table_of_content'] = (int) $blog->table_of_content;
                $translatedData['blog_schedule'] = $blog->blog_schedule;
                $translatedData['blog_slug'] = $blog->slug;
                $translatedData['blog_image'] = $blog->image ? $this->getImageUrl($blog->image) : null;

                return $translatedData;
            });
    
            return response()->json([
                'status' => 'true',
                'data' => $blogs_translations,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    protected function getOldSocialImagePath($lang, $blogId, $index, $section)
    {
        // Retrieve the web content translation entry for the current language and web content ID
        $blogTranslation = BlogTranslation::where('language', $lang)
            ->where('blog_id', $blogId)
            ->first();
    
        // Check if the translation exists
        if (!$blogTranslation) {
            return null; // or handle the case where the translation is not found
        }
    
        // Decode the JSON stored in the 'translated_value' field
        $oldTranslation = json_decode($blogTranslation->field_values, true);
    
        // Check if the section exists and return the appropriate path
        if (isset($oldTranslation[$section])) {
            // Handle sec_six and similar sections where the image is nested in an array of objects
            if (isset($oldTranslation[$section][$index])) {
                return $oldTranslation[$section][$index]['image'] ?? null;
            }
        }
    
        return null;
    }
    
    public function getUserName($userId)
    {
        $userName = \App\Models\User::where('id', $userId)->pluck('name')->first();
        
        return $userName;
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
