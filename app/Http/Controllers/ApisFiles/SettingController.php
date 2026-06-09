<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApisFiles\WebContentController;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Catalog;
use App\Models\WebContent;
use App\Models\Product;
use App\Models\Partner;
use App\Models\Promotion;
use App\Models\PromotionTranslation;
use App\Models\PartnerTranslation;
use App\Models\ProductTranslation;
use App\Models\WebContentTranslation;
use App\Models\BlogTranslation;
use App\Models\CatalogTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SettingController extends Controller
{
    public function fetchRssFeeds($lang, $per_page = 6)
    {
        try {
            $currentDateTime = Carbon::now('Asia/Karachi');
    
            $blogQuery = Blog::query()
                ->where('blog_status', 1)
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('blog_schedule', '<=', $currentDateTime)
                        ->orWhereNull('blog_schedule');
                })
                ->orderBy('blog_schedule', 'DESC');
    
            $perPage = request()->input('per_page', $per_page);
    
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
    
            $webContentController = new WebContentController();
            $blogsMeta = $webContentController->getWebMetaDeta('blogs', $lang);
            $metaData = $blogsMeta->original['data'] ?? [];
    
            $items = $blogs->map(function ($blog) use ($lang) {
                $translations = BlogTranslation::where('blog_id', $blog->id)
                    ->whereIn('language', [$lang, 'en'])
                    ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                    ->get()
                    ->keyBy('language');
    
                $translatedData = isset($translations[$lang])
                    ? json_decode($translations[$lang]->field_values, true)
                    : json_decode($translations['en']->field_values ?? '{}', true);
    
                if (!is_array($translatedData)) {
                    $translatedData = [];
                }
    
                $blogParagraph = $translatedData['blog_paragraph'] ?? '';
    
                $plainText = html_entity_decode($blogParagraph, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plainText = strip_tags($plainText);
                $plainText = preg_replace('/\s+/', ' ', $plainText);
                $plainText = trim($plainText);
    
                if (mb_strlen($plainText) > 500) {
                    $text = mb_substr($plainText, 0, 500);
                    $remaining = mb_substr($plainText, 500);
    
                    $position = mb_strpos($remaining, '.');
    
                    if ($position !== false) {
                        $text .= mb_substr($remaining, 0, $position + 1);
                    }
    
                    $description = trim($text);
                } else {
                    $description = $plainText;
                }
    
                $postUrl = $blog->slug;
    
                return [
                    'post_id' => $blog->id,
                    'title' => $translatedData['blog_title'] ?? '',
                    'link' => $postUrl,
                    'creator' => $this->getUserName($blog->created_by),
                    'pubDate' => $blog->blog_schedule
                        ? Carbon::parse($blog->blog_schedule)->toRfc2822String()
                        : Carbon::parse($blog->created_at)->toRfc2822String(),
                    'category' => $translatedData['blog_category'] ?? null,
                    'guid' => [
                        'isPermaLink' => false,
                        'value' => $postUrl,
                    ],
                    'description' => $description,
                    'image' => $blog->image ? $this->getImageUrl($blog->image) : null,
                    'slug' => $blog->slug,
                    'status' => (int) $blog->blog_status,
                    'created_at' => $blog->created_at,
                ];
            });
    
            return response()->json([
                'status' => true,
                'message' => 'Blogs RSS data retrieved successfully',
                'data' => [
                    'channel' => [
                        'title' => $metaData['meta_title'] ?? 'Quick Lease Blog',
                        'link' => url('/blogs'),
                        'description' => $metaData['meta_description'] ?? '',
                        'lastBuildDate' => Carbon::now()->toRfc2822String(),
                        'language' => $lang == 'ar' ? 'ar-AE' : 'en-GB',
                        'banner_title' => $metaData['banner_title'] ?? '',
                        'banner' => $metaData['banner'] ?? null,
                        'slug' => $metaData['slug'] ?? 'blogs',
                    ],
                    'items' => $items,
                ],
                'pagination' => $pagination,
            ], 200);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function fetchSitemap($lang = 'en')
    {
        try {
            $domain = "https://quicklease.ae/";
            $type = request()->query('type');
            $slugs = request()->query('slugs');
        
            $data = collect();
    
            if (!$type || $type === 'web_contents') {
                $skipWebContentSlugs = [
                    'home',
                    'elements',
                    'newfolder-test-1',
                    'thank-you',
                    'product-inner',
                ];
                
                $webContents = WebContent::select('id', 'slug', 'created_at', 'updated_at')
                    ->whereNotIn('slug', $skipWebContentSlugs)
                    ->get()
                    ->map(function ($webContent) use ($lang,$domain) {
                        $translation = WebContentTranslation::where('web_content_id', $webContent->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->translated_value ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'web_contents',
                            'id' => $webContent->id,
                            'slug' => $domain.$webContent->slug,
                            'title' => $fieldValues['banner_heading'] ?? $fieldValues['banner_title'] ?? null,
                            'created_at' => $webContent->created_at,
                            'updated_at' => $webContent->updated_at,
                        ];
                    });
    
                $data = $data->merge($webContents);
            }
    
            if (!$type || $type === 'products') {
                $products = Product::where('product_status', 1)
                    ->select('id', 'slug', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($product) use ($lang,$domain) {
                        $translation = ProductTranslation::where('product_id', $product->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->field_values ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'products',
                            'id' => $product->id,
                            'slug' => $domain.$product->slug,
                            'title' => $fieldValues['product_title'] ?? null,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                        ];
                    });
    
                $data = $data->merge($products);
            }
            
            if (!$type || $type === 'blogs') {
                $blogs = Blog::where('blog_status', 1)
                    ->select('id', 'slug', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($blog) use ($lang,$domain) {
                        $translation = BlogTranslation::where('blog_id', $blog->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->field_values ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'blogs',
                            'id' => $blog->id,
                            'slug' => $domain.'blog/'.$blog->slug,
                            'title' => $fieldValues['blog_title'] ?? null,
                            'created_at' => $blog->created_at,
                            'updated_at' => $blog->updated_at,
                        ];
                    });
    
                $data = $data->merge($blogs);
            }
    
            if (!$type || $type === 'catalogs') {
                $catalogs = Catalog::where('catalog_status', 1)
                    ->select('id', 'slug', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($catalog) use ($lang,$domain) {
                        $translation = CatalogTranslation::where('catalog_id', $catalog->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->field_values ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'catalogs',
                            'id' => $catalog->id,
                            'slug' => $domain.$catalog->slug,
                            'title' => $fieldValues['catalog_title'] ?? $fieldValues['title'] ?? null,
                            'created_at' => $catalog->created_at,
                            'updated_at' => $catalog->updated_at,
                        ];
                    });
    
                $data = $data->merge($catalogs);
            }
    
            if (!$type || $type === 'partners') {
                $partners = Partner::where('partner_status', 1)
                    ->select('id', 'slug', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($partner) use ($lang,$domain) {
                        $translation = PartnerTranslation::where('partner_id', $partner->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->field_values ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'partners',
                            'id' => $partner->id,
                            'slug' => $domain.'partners/'.$partner->slug,
                            'title' => $fieldValues['partner_title'] ?? null,
                            'created_at' => $partner->created_at,
                            'updated_at' => $partner->updated_at,
                        ];
                    });
    
                $data = $data->merge($partners);
            }
    
            if (!$type || $type === 'promotions') {
                $promotions = Promotion::where('promotion_status', 1)
                    ->select('id', 'slug', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($promotion) use ($lang,$domain) {
                        $translation = PromotionTranslation::where('promotion_id', $promotion->id)
                            ->whereIn('language', [$lang, 'en'])
                            ->orderByRaw("FIELD(language, ?, 'en')", [$lang])
                            ->first();
    
                        $fieldValues = json_decode($translation->field_values ?? '{}', true) ?: [];
    
                        return [
                            'type' => 'promotions',
                            'id' => $promotion->id,
                            'slug' => $domain.'promotions/'.$promotion->slug,
                            'title' => $fieldValues['promotion_title'] ?? null,
                            'created_at' => $promotion->created_at,
                            'updated_at' => $promotion->updated_at,
                        ];
                    });
    
                $data = $data->merge($promotions);
            }
            
            if ($slugs) {

                $slugList = collect(explode(',', $slugs))
                    ->map(function ($slug) {
                        return trim($slug);
                    })
                    ->filter()
                    ->map(function ($slug) use ($domain) {
                        return rtrim($domain, '/') . '/' . ltrim($slug, '/');
                    })
                    ->values()
                    ->toArray();
            
                $data = $data->filter(function ($item) use ($slugList) {
                    return in_array($item['slug'], $slugList);
                });
            
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Sitemap data retrieved successfully',
                'data' => $data->values(),
            ], 200);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $ex->getMessage(),
            ], 500);
        }
    }
    
    public function getUserName($userId)
    {
        $userName = \App\Models\User::where('id', $userId)->pluck('name')->first();
        
        return $userName;
    }
    
    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset('public'.$image_path);
        return $image_url;
    }
}
