<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ForumPost;
use App\Models\ForumThread;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

class ForumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind our custom models when the base models are requested
        $this->app->bind(Post::class, ForumPost::class);
        $this->app->bind(Thread::class, ForumThread::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}