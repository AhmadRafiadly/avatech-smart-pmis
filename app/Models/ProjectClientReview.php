<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClientReview extends Model
{
    public const STATUSES = ['draft', 'active', 'approved', 'revision_requested', 'expired', 'revoked'];
    public const REVIEW_TYPES = ['mom', 'design', 'progress', 'uat', 'handover', 'general'];

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'title',
        'description',
        'token',
        'status',
        'review_type',
        'expires_at',
        'approved_at',
        'revision_requested_at',
        'client_name',
        'client_email',
        'client_feedback',
        'internal_notes',
        'last_opened_at',
        'opened_count',
        'include_mom',
        'include_design_deliverables',
        'include_progress',
        'include_qc_summary',
        'include_change_requests',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'revision_requested_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'opened_count' => 'integer',
        'include_mom' => 'boolean',
        'include_design_deliverables' => 'boolean',
        'include_progress' => 'boolean',
        'include_qc_summary' => 'boolean',
        'include_change_requests' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(ProjectSignoff::class, 'client_review_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPubliclyViewable(): bool
    {
        return in_array($this->status, ['active', 'approved', 'revision_requested'], true)
            && ! $this->isExpired();
    }

    public function canReceiveClientResponse(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }
}
