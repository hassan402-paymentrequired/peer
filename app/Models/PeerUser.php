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
}
