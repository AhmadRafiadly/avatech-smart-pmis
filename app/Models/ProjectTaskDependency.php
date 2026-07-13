<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDependency extends Model
{
    protected $fillable = [
        'project_id',
        'predecessor_task_id',
        'successor_task_id',
        'dependency_type',
        'notes',
        'created_by',
        'task_id',
        'depends_on_task_id',
        'type',
        'created_by_user_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function predecessorTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'predecessor_task_id');
    }

    public function successorTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'successor_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
