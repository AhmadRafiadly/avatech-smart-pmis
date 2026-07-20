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
        'original_filename',
        'mime_type',
        'file_size',
        'file_sha256',
        'extracted_text',
        'extraction_status',
        'extracted_at',
        'external_url',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'extracted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
