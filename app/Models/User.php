<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'bio',
        'tagline',
        'old_id',
        'profile_public',
        'location',
        'avatar',
        'social_links',
        'discogs_username',
        'discogs_oauth_token',
        'discogs_oauth_token_secret',
        'discogs_connected_at',
        'banned_at',
        'banned_reason',
        'banned_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'discogs_oauth_token',
        'discogs_oauth_token_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_public' => 'boolean',
            'social_links' => 'array',
            'discogs_connected_at' => 'datetime',
            'banned_at' => 'datetime',
        ];
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function forumPosts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function forumThreads()
    {
        return $this->hasMany(Thread::class, 'author_id');
    }

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function publicCollections()
    {
        return $this->hasMany(Collection::class)->where('visibility', 'public');
    }

    public function vinyls()
    {
        // Relation many-to-many avec la table pivot collection_vinyls (ou CollectionVinyl)
        return $this->belongsToMany(Vinyl::class, 'collection_vinyls', 'user_id', 'vinyl_id')
            ->withPivot([
                'collection_id',
                'prix_achat',
                'provenance',
                'annee_achat',
                'vente',
                'commentaires',
                'note',
                'date_ajout',
                'created_at',
                'updated_at',
            ]);
    }

    public function vinylCollections()
    {
        // Accès direct aux associations (pivot)
        return $this->hasMany(CollectionVinyl::class, 'user_id');
    }

    // Si tu veux les vinyles ajoutés/créés par l'utilisateur (optionnel, à adapter)
    public function addedVinyls()
    {
        return $this->hasMany(Vinyl::class, 'created_by');
    }

    // Messaging relations
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'is_admin', 'notifications_enabled'])
            ->withTimestamps()
            ->orderByDesc('updated_at');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversationParticipants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }
}
