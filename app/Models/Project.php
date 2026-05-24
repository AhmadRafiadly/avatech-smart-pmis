<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'code', 'color', 'name', 'description', 'client_id', 'lead_user_id',
        'phase', 'due_at', 'progress', 'status',
        'ai_wbs_generated', 'is_featured', 'archived_at',
    ];

    protected $casts = [
        'due_at'           => 'date',
        'archived_at'      => 'datetime',
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

    public function modules(): HasMany
    {
        return $this->hasMany(ProjectModule::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function moms(): HasMany
    {
        return $this->hasMany(ProjectMom::class);
    }
}
