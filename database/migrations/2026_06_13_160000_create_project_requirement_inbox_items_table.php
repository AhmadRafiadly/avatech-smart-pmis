<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirement_inbox_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('captured_by_user_id')->nullable();
            $table->string('source', 30)->nullable();
            $table->string('channel_label', 120)->nullable();
            $table->date('occurred_on')->nullable();
            $table->text('raw_text');
            $table->text('summary')->nullable();
            $table->string('suggested_type', 40)->nullable();
            $table->string('suggested_priority', 20)->nullable();
            $table->string('status', 30)->default('new');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('converted_to', 20)->nullable();
            $table->foreignId('converted_change_request_id')->nullable();
            $table->foreignId('converted_task_id')->nullable();
            $table->foreignId('converted_mom_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status'], 'pri_project_status_idx');

            $table->foreign('project_id', 'pri_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('captured_by_user_id', 'pri_captured_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by_user_id', 'pri_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('converted_change_request_id', 'pri_conv_cr_fk')->references('id')->on('project_change_requests')->nullOnDelete();
            $table->foreign('converted_task_id', 'pri_conv_task_fk')->references('id')->on('project_tasks')->nullOnDelete();
            $table->foreign('converted_mom_id', 'pri_conv_mom_fk')->references('id')->on('project_moms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirement_inbox_items');
    }
};
