<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerMatch extends Model
{
    use HasUlids;

    
     public function uniqueIds(): array
    {
        return ['player_match_id'];
    }


    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function statistics(): BelongsTo
    {
        return $this->belongsTo(PlayerStatistic::class, 'player_id', 'player_id')
            ->where('fixture_id', $this->fixture_id);
    }
}
