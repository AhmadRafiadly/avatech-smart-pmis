<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_uat_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 40)->nullable();
            $table->string('priority', 20)->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignId('tested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('evidence_url')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'title'], 'pui_project_title_unique');
            $table->index(['project_id', 'status'], 'pui_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_uat_items');
    }
};
