<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('task_id');
            $table->foreignId('depends_on_task_id');
            $table->string('type', 30)->default('finish_to_start');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'task_id', 'depends_on_task_id'], 'ptd_unique_task_dependency');
            $table->index(['project_id', 'task_id'], 'ptd_project_task_idx');

            $table->foreign('project_id', 'ptd_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('task_id', 'ptd_task_fk')->references('id')->on('project_tasks')->cascadeOnDelete();
            $table->foreign('depends_on_task_id', 'ptd_depends_task_fk')->references('id')->on('project_tasks')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'ptd_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_dependencies');
    }
};
