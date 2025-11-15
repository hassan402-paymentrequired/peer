<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PhonePasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasmany(Notification::class);
    }


    public function peers(): BelongsToMany
    {
        return $this->belongsToMany(Peer::class, 'peer_users')->withTimestamps();
    }

    public function my_peers(): HasMany
    {
        return $this->hasMany(Peer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function dedutBalance($amount)
    {
        $this->wallet()->decrement('balance', $amount);
    }

    public function addBalance($amount)
    {
        $this->wallet()->increment('balance', $amount);
    }

    public function match(): HasMany
    {
        return $this->hasMany(PlayerMatch::class);
    }


    public function daily_contests()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_users')->withTimestamps();
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_users')->withTimestamps();
    }


    public function hasVirtualAccount(): bool
    {
        return $this->virtualAccount()->exists();
    }


    public function hasActiveVirtualAccount(): bool
    {
        return $this->activeVirtualAccount()->exists();
    }


    public function getVirtualAccountDetails(): ?array
    {
        $virtualAccount = $this->virtualAccount;
        return $virtualAccount ? $virtualAccount->accountDetails : null;
    }


    function AlreadyJoinedTodayTournament()
    {
        return $this->tournaments()->active()->exists();
    }

    function getTournamentEntriesCount($tournamentId = null)
    {
        $query = $this->hasMany(\App\Models\TournamentUser::class);

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        } else {
            // Get active tournament entries
            $query->whereHas('tournament', function ($q) {
                $q->where('status', 'open');
            });
        }

        return $query->count();
    }

    /**
     * Route notifications for the SMS channel.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    /**
     * Check if user has verified phone number
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'phone';
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Find user by phone number for authentication
     */
    public static function findForAuth(string $phone): ?self
    {
        return static::where('phone', $phone)->first();
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PhonePasswordResetNotification($token));
    }
}
