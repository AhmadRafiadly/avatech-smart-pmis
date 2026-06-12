<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSignoff extends Model
{
    public const TYPES = ['uat', 'handover', 'final'];
    public const STATUSES = ['draft', 'ready', 'signed', 'revision_requested', 'revoked'];

    protected $fillable = [
        'project_id',
        'type',
        'status',
        'signed_by_name',
        'signed_by_email',
        'signed_by_role',
        'signed_at',
        'client_review_id',
        'notes',
        'handover_summary',
        'created_by_user_id',
        'approved_by_user_id',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function clientReview(): BelongsTo
    {
        return $this->belongsTo(ProjectClientReview::class, 'client_review_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
