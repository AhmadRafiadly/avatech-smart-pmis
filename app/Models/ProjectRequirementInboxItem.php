<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRequirementInboxItem extends Model
{
    public const SOURCE_TYPES = ['prd', 'process_document', 'meeting_note', 'client_brief', 'google_drive_link', 'other'];
    public const PRIORITIES   = ['must', 'should', 'could', 'wont'];
    public const STATUSES     = ['draft', 'reviewed', 'used'];

    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'source_type',
        'priority',
        'status',
        'summary',
        'file_path',
        'external_url',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
