<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_client_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('token', 96)->unique();
            $table->string('status', 32)->default('draft');
            $table->string('review_type', 32)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revision_requested_at')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->text('client_feedback')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->unsignedInteger('opened_count')->default(0);
            $table->boolean('include_mom')->default(true);
            $table->boolean('include_design_deliverables')->default(true);
            $table->boolean('include_progress')->default(true);
            $table->boolean('include_qc_summary')->default(false);
            $table->boolean('include_change_requests')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'status'], 'pcr_project_status_idx');
            $table->index(['status', 'expires_at'], 'pcr_status_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_client_reviews');
    }
};
