<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortCondition extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
