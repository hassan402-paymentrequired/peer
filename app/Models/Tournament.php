<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'is_active',
        'amount',
        'date',
        'status',
        'scoring_calculated',
        'scoring_calculated_at'
    ];

    public function uniqueIds(): array
    {
        return ['tournament_id'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tournament_users')->withTimestamps();
    }

    public function scopeActive($query)
    {
        $query->where('is_active', true);
    }
}
