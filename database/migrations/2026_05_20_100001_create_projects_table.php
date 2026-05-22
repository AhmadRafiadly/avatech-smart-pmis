<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4);
            $table->string('color', 9)->default('#7C3AED');
            $table->string('name');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('lead_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phase')->default('Discovery');
            $table->date('due_at')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('status', ['on-track', 'attention', 'critical'])->default('on-track');
            $table->boolean('ai_wbs_generated')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
