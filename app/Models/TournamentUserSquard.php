<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentUserSquard extends Model
{
    protected $fillable = [
        'tournament_user_id',
        'star_rating',
        'main_player_id',
        'sub_player_id',
        'main_player_match_id',
        'sub_player_match_id'
    ];
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
