<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_settings', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_name')->default('PT Ava Teknologi Nusantara');
            $table->string('subdomain')->default('avatech');
            $table->string('interface_language', 8)->default('id');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_settings');
    }
};
