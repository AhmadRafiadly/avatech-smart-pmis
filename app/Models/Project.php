<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'code', 'color', 'name', 'description', 'client_id', 'lead_user_id',
        'phase', 'due_at', 'progress', 'status',
        'ai_wbs_generated', 'is_featured',
    ];

    protected $casts = [
        'due_at'           => 'date',
        'progress'         => 'integer',
        'ai_wbs_generated' => 'boolean',
        'is_featured'      => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }
}
