<?php

namespace App\Jobs;

use App\Models\Blog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;


class UploadBlogImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $blogId;
    protected $blog_image;

    /**
     * Create a new job instance.
     *
     * @return void
     */
      public function __construct($blogId, $blog_image)
    {
        $this->blogId = $blogId;
        $this->blog_image = $blog_image;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Find the blog entry
            $blog = Blog::find($this->blogId);
    
            if ($blog) {
            
                $imagePath = $this->blog_image->store('blogs_images', 'public');
                $blog->image = $imagePath;
                $blog->save();
            }
            
            // return response()->json(['status' => 'false', 'message' => 'Blogs not found'], 200);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Image upload failed: ' . $e->getMessage());
        }
    }
}
