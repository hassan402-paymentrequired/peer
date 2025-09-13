<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PeerUser extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['peer_user_id'];
    }

     public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function peer()
    {
        return $this->belongsTo(Peer::class);
    }

    public function squads()
    {
        return $this->hasMany(PeerUserSquard::class);
    }
}
