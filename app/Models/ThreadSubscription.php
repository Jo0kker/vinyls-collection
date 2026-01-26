<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'thread_id',
        'email_notifications',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the thread that is being subscribed to.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }
}
