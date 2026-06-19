<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'seen_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
        'sog' => 'float',
        'cog' => 'float',
    ];
}
