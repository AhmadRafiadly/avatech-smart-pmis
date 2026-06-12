<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // whatsapp | meeting | client_call | internal | email | other
            $table->string('source', 30)->nullable();
            // new_scope | revision | bug | content_update | design_change | technical_adjustment | other
            $table->string('type', 40)->nullable();
            // low | medium | high | urgent
            $table->string('priority', 20)->nullable();
            // draft | needs_review | approved | rejected | converted
            $table->string('status', 20)->default('draft');

            $table->foreignId('affected_module_id')->nullable()->constrained('project_modules')->nullOnDelete();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->integer('timeline_impact_days')->nullable();

            $table->text('impact_summary')->nullable();
            $table->text('decision_notes')->nullable();

            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('created_task_id')->nullable()->constrained('project_tasks')->nullOnDelete();

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_change_requests');
    }
};
