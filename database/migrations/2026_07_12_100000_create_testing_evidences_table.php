<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testing_evidences', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->string('title', 255);
            $table->integer('total_scenarios')->default(0);
            $table->integer('passed_scenarios')->default(0);
            $table->integer('failed_scenarios')->default(0);
            $table->string('result_status', 50);
            $table->date('tested_at');
            $table->text('notes')->nullable();
            $table->string('evidence_file_path', 500)->nullable();
            $table->string('evidence_url', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_evidences');
    }
};