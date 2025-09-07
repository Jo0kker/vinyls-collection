<?php

namespace App\Models;

use TeamTeaTime\Forum\Models\Post as BasePost;
use TeamTeaTime\Forum\Models\Thread;

class ForumPost extends BasePost
{
    /**
     * Get the page number for this post, excluding soft-deleted posts from calculation
     * 
     * @return int
     */
    public function getPage(): int
    {
        // Get the thread with fresh data
        $thread = Thread::find($this->thread_id);
        
        if (!$thread) {
            return 1;
        }
        
        // Count only non-deleted posts before this one
        $positionInThread = $thread->posts()
            ->where(function($query) {
                $query->where('created_at', '<', $this->created_at)
                      ->orWhere(function($q) {
                          $q->where('created_at', '=', $this->created_at)
                            ->where('id', '<', $this->id);
                      });
            })
            ->count() + 1;
        
        return ceil($positionInThread / $this->getPerPage());
    }
}