<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_blockers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('reported_by_user_id')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('source', 40)->nullable();
            $table->string('severity', 20)->default('medium');
            $table->string('status', 30)->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'severity'], 'pb_project_status_severity_idx');
            $table->index(['project_id', 'task_id'], 'pb_project_task_idx');

            $table->foreign('project_id', 'pb_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('task_id', 'pb_task_fk')->references('id')->on('project_tasks')->nullOnDelete();
            $table->foreign('reported_by_user_id', 'pb_reported_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to_user_id', 'pb_assigned_to_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_blockers');
    }
};
