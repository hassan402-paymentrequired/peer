<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    public function players()
    {
        return $this->hasMany(Player::class, 'team_id', 'external_id');
    }


    public function match(): HasMany
    {
        return $this->hasMany(PlayerMatch::class);
    }
}
