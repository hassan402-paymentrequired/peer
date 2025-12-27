<?php

namespace App\Models;

use App\Enum\FixtureStatusEnum;
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

    public function lineups()
    {
        return $this->hasMany(FixtureLineup::class);
    }

    // Fixture.php
    public function scopeActive($query)
    {
        return $query
            ->whereIn('status', FixtureStatusEnum::getValues())
            ->whereBetween('date', [
                now()->subHours(5),
                now()->addHours(5),
            ])
            ->where(function ($query) {
                $query->whereHas('playerMatches.tournamentSquads')
                    ->orWhereHas('playerMatches.peerSquads');
            });
    }
}
