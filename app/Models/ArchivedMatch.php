<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedMatch extends Model
{
    protected $fillable = [
        'match_uuid',
        'game_slug',
        'played_at',
        'payload',
        'status',
        'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'played_at' => 'datetime',
    ];
}
