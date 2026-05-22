<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceSetting extends Model
{
    protected $fillable = [
        'workspace_name',
        'subdomain',
        'interface_language',
        'timezone',
    ];
}
