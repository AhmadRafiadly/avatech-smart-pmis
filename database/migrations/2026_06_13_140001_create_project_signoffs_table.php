<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32)->default('draft');
            $table->string('signed_by_name')->nullable();
            $table->string('signed_by_email')->nullable();
            $table->string('signed_by_role')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('client_review_id')->nullable()->constrained('project_client_reviews')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('handover_summary')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'type'], 'ps_project_type_unique');
            $table->index(['project_id', 'status'], 'ps_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_signoffs');
    }
};
