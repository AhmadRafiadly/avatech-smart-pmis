<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'type',
        'status',
        'estimated_hours',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'estimated_hours' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
