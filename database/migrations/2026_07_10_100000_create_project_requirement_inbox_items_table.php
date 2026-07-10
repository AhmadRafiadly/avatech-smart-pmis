<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A leftover table with the abandoned full-branch schema (469f18b:
        // source/raw_text/converted_*) may exist in dev databases. It only ever
        // held demo data, so drop it and recreate with the sidang-final schema.
        Schema::dropIfExists('project_requirement_inbox_items');

        Schema::create('project_requirement_inbox_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('created_by')->nullable();
            $table->string('title', 255);
            $table->string('source_type', 40)->default('other');
            $table->string('priority', 20)->default('should');
            $table->string('status', 20)->default('draft');
            $table->text('summary');
            $table->string('file_path', 500)->nullable();
            $table->string('external_url', 1000)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status'], 'pri_project_status_idx');

            $table->foreign('project_id', 'pri_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('created_by', 'pri_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirement_inbox_items');
    }
};
