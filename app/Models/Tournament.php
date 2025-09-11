<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['tournament_id'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tournaments_users')->withTimestamps();
    }
}
