<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChangeRequest extends Model
{
    public const SOURCES    = ['whatsapp', 'meeting', 'client_call', 'internal', 'email', 'other'];
    public const TYPES      = ['new_scope', 'revision', 'bug', 'content_update', 'design_change', 'technical_adjustment', 'other'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const STATUSES   = ['draft', 'needs_review', 'approved', 'rejected', 'converted'];

    protected $fillable = [
        'project_id',
        'requested_by_user_id',
        'title',
        'description',
        'source',
        'type',
        'priority',
        'status',
        'affected_module_id',
        'estimated_hours',
        'timeline_impact_days',
        'impact_summary',
        'decision_notes',
        'approved_by_user_id',
        'approved_at',
        'converted_at',
        'created_task_id',
    ];

    protected $casts = [
        'estimated_hours'      => 'decimal:2',
        'timeline_impact_days' => 'integer',
        'approved_at'          => 'datetime',
        'converted_at'         => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function affectedModule(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'affected_module_id');
    }

    public function createdTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'created_task_id');
    }
}
