<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_qc_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_module_id')->nullable()->constrained('project_modules')->nullOnDelete();
            $table->foreignId('project_task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('scenario');
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->string('status', 30)->default('pending');     // pending|passed|failed|retest
            $table->string('priority', 30)->default('medium');    // low|medium|high
            $table->timestamp('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_module_id']);
            $table->index(['project_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_qc_tests');
    }
};
