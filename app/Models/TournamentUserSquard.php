<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentUserSquard extends Model
{
    public function contestsUser()
    {
        return $this->belongsTo(TournamentUser::class);
    }

    public function mainPlayer()
    {
        return $this->belongsTo(\App\Models\Player::class, 'main_player_id');
    }

    public function subPlayer()
    {
        return $this->belongsTo(\App\Models\Player::class, 'sub_player_id');
    }
}
