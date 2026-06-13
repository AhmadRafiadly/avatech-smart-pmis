<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_handover_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            // draft | generated | finalized | revoked
            $table->string('status', 20)->default('draft');

            $table->text('summary')->nullable();

            // Access handover — URLs only, never secrets/passwords.
            $table->string('deployment_url')->nullable();
            $table->string('staging_url')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('admin_url')->nullable();
            // not_required | pending | handed_over  (status only — never the credential itself)
            $table->string('credential_handover_status', 20)->nullable();

            $table->text('maintenance_notes')->nullable();
            $table->text('client_notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_handover_packs');
    }
};
