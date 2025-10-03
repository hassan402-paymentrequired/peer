<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentUser extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'total_points',
        'is_winner'
    ];
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
