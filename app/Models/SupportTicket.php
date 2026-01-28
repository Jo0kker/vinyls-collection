<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'email',
        'subject',
        'message',
        'status',
        'priority',
        'user_id',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            $ticket->reference = self::generateReference();

            // Auto-link to user if email matches
            if (!$ticket->user_id) {
                $user = User::where('email', $ticket->email)->first();
                if ($user) {
                    $ticket->user_id = $user->id;
                }
            }
        });
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $lastTicket = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $number = $lastTicket ? ((int) substr($lastTicket->reference, -5)) + 1 : 1;

        return sprintf('TICKET-%s-%05d', $year, $number);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }

    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal_note', false);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function markAsClosed(): void
    {
        $this->update([
            'status' => 'closed',
        ]);
    }

    public static function linkTicketsToUser(User $user): int
    {
        return self::where('email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}
