<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $guarded = [];

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
