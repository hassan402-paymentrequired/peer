<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentUser extends Model
{
    public function daily_contest()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function squads()
    {
        return $this->hasMany(TournamentUserSquard::class);
    }
}
