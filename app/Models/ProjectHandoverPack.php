<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHandoverPack extends Model
{
    public const STATUSES = ['draft', 'generated', 'finalized', 'revoked'];
    public const CREDENTIAL_STATUSES = ['not_required', 'pending', 'handed_over'];

    protected $fillable = [
        'project_id',
        'generated_by_user_id',
        'finalized_by_user_id',
        'title',
        'version',
        'status',
        'summary',
        'deployment_url',
        'staging_url',
        'repository_url',
        'admin_url',
        'credential_handover_status',
        'maintenance_notes',
        'client_notes',
        'internal_notes',
        'pdf_path',
        'generated_at',
        'finalized_at',
    ];

    protected $casts = [
        'version'      => 'integer',
        'generated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }
}
