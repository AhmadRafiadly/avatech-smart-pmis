<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'code',
        'tier',
        'industry',
        'location',
        'pic_name',
        'pic_role',
        'email',
        'phone',
        'description',
        'total_engagement',
        'relationship_health',
        'last_touch_label',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
