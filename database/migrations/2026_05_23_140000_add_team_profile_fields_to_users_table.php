<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('email');
            $table->string('position')->nullable()->after('phone');
            $table->string('department')->nullable()->after('position');
            $table->string('level', 40)->nullable()->after('department');
            $table->json('skills')->nullable()->after('level');
            $table->string('avatar_color', 9)->nullable()->after('skills');
            $table->timestamp('archived_at')->nullable()->index()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn([
                'phone',
                'position',
                'department',
                'level',
                'skills',
                'avatar_color',
                'archived_at',
            ]);
        });
    }
};
