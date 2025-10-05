<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
     /**
     * Scope for unread notifications
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
