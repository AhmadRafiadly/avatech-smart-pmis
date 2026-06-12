<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDesignDeliverable extends Model
{
    protected $fillable = [
        'project_task_id',
        'title',
        'figma_url',
        'pdf_file_path',
        'notes',
        'submitted_at',
        'created_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
