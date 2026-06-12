<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_design_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->string('title');
            $table->string('figma_url')->nullable();
            $table->string('pdf_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_task_id', 'submitted_at'], 'ptdd_task_submitted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_design_deliverables');
    }
};
