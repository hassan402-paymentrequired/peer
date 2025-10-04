<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peer extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'amount',
        'private',
        'limit',
        'sharing_ratio',
        'status',
        'winner_user_id',
        'scoring_calculated',
        'scoring_calculated_at'
    ];

    public function uniqueIds(): array
    {
        return ['peer_id'];
    }
    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }



    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'peer_users')->withTimestamps();
    }


    public function addUser(string $id): void
    {
        $this->users()->attach($id);
    }

    public function removeUser(string $id): void
    {
        $this->users()->detach($id);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function getIsOwnerAttribute()
    {
        return $this->created_by === authUser()->id();
    }
}
