<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamWorkload extends Model
{
    protected $fillable = ['user_id', 'load_pct', 'active_tasks', 'is_sim'];

    protected $casts = [
        'load_pct'     => 'integer',
        'active_tasks' => 'integer',
        'is_sim'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
