<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    //

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
