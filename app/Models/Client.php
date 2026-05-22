<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['name', 'code', 'tier'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
