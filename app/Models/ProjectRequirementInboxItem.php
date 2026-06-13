<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRequirementInboxItem extends Model
{
    public const SOURCES = ['whatsapp', 'meeting', 'client_call', 'email', 'internal', 'other'];
    public const STATUSES = ['new', 'reviewed', 'converted', 'dismissed'];

    protected $fillable = [
        'project_id',
        'captured_by_user_id',
        'source',
        'channel_label',
        'occurred_on',
        'raw_text',
        'summary',
        'suggested_type',
        'suggested_priority',
        'status',
        'notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'converted_to',
        'converted_change_request_id',
        'converted_task_id',
        'converted_mom_id',
        'converted_at',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'reviewed_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectChangeRequest::class, 'converted_change_request_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'converted_task_id');
    }

    public function mom(): BelongsTo
    {
        return $this->belongsTo(ProjectMom::class, 'converted_mom_id');
    }
}
