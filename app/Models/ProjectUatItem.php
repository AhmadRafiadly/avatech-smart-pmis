<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUatItem extends Model
{
    public const CATEGORIES = ['functional', 'content', 'design', 'performance', 'security', 'integration', 'deployment', 'other'];
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['pending', 'passed', 'failed', 'blocked', 'revision_needed'];

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'tested_by_user_id',
        'tested_at',
        'notes',
        'evidence_url',
        'sort_order',
    ];

    protected $casts = [
        'tested_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by_user_id');
    }
}
