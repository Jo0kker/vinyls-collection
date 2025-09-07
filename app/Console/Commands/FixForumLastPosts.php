<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Models\Post;

class FixForumLastPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forum:fix-last-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix last_post_id for all threads to exclude soft-deleted posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing last_post_id for all threads...');
        
        $threads = Thread::all();
        $bar = $this->output->createProgressBar(count($threads));
        $bar->start();
        
        $fixed = 0;
        
        foreach ($threads as $thread) {
            $oldLastPostId = $thread->last_post_id;
            
            // Find the actual last non-deleted post
            $lastActivePost = Post::where('thread_id', $thread->id)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $newLastPostId = $lastActivePost ? $lastActivePost->id : null;
            
            // Only update if different
            if ($oldLastPostId != $newLastPostId) {
                $thread->update([
                    'last_post_id' => $newLastPostId
                ]);
                $fixed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Fixed {$fixed} threads with incorrect last_post_id.");
        
        return Command::SUCCESS;
    }
}