<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectQcTest extends Model
{
    public const STATUSES   = ['pending', 'passed', 'failed', 'retest'];
    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $fillable = [
        'project_id',
        'project_module_id',
        'project_task_id',
        'created_by',
        'title',
        'scenario',
        'expected_result',
        'actual_result',
        'status',
        'priority',
        'tested_at',
        'notes',
    ];

    protected $casts = [
        'tested_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'project_module_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
