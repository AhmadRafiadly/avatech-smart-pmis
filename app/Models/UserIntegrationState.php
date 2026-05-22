<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIntegrationState extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'connected',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'connected'       => 'boolean',
        'connected_at'    => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
