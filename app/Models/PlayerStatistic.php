<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerStatistic extends Model
{
    public function getPointsAttribute()
    {
        return
            $this->goals * config('point.goal') +
            $this->assists * config('point.assist') +
            $this->shots * config('point.shot') +
            $this->shots_on_target * config('point.shot_on_target') +
            $this->yellow_cards * config('point.yellow_card');
    }
}
