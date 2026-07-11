<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestingEvidence extends Model
{
    protected $table = 'testing_evidences';

    protected $fillable = [
        'category',
        'title',
        'total_scenarios',
        'passed_scenarios',
        'failed_scenarios',
        'result_status',
        'tested_at',
        'notes',
        'evidence_file_path',
        'evidence_url',
    ];

    protected $casts = [
        'tested_at' => 'date',
        'total_scenarios' => 'integer',
        'passed_scenarios' => 'integer',
        'failed_scenarios' => 'integer',
    ];
}