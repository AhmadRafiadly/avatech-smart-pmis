<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id',
        'project_module_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'estimate_hours',
        'sort_order',
        'deliverable_url',
        'deliverable_file_path',
        'deliverable_type',
        'deliverable_submitted_at',
        'is_design_deliverable',
    ];

    protected $casts = [
        'due_date' => 'date',
        'estimate_hours' => 'integer',
        'sort_order' => 'integer',
        'deliverable_submitted_at' => 'datetime',
        'is_design_deliverable' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'project_module_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function designDeliverables(): HasMany
    {
        return $this->hasMany(ProjectTaskDesignDeliverable::class);
    }
}
