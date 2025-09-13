<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerUserSquard extends Model
{
    public function peerUser()
    {
        return $this->belongsTo(PeerUser::class);
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
