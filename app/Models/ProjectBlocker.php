<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBlocker extends Model
{
    public const SOURCES = ['client', 'internal', 'technical', 'design', 'content', 'deployment', 'dependency', 'access', 'other'];
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['open', 'in_progress', 'resolved', 'cancelled'];

    protected $fillable = [
        'project_id',
        'task_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'source',
        'severity',
        'status',
        'due_date',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
