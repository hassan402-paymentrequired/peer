<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fixture extends Model
{
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function playerMatches()
    {
        return $this->hasMany(PlayerMatch::class);
    }
}
