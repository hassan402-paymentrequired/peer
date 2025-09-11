<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
        return $this->belongsToMany(Tournament::class, 'daily_contest_users')->withTimestamps();
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
        return $this->daily_contests()->whereDate('daily_contests.created_at', now()->toDateString())->exists();
    }
}
