<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMom extends Model
{
    protected $fillable = [
        'project_id',
        'created_by',
        'meeting_date',
        'notes',
        'summary',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'date',
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
