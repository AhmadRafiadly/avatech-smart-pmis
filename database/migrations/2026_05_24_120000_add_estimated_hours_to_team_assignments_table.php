<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('team_assignments', 'estimated_hours')) {
                $table->unsignedSmallInteger('estimated_hours')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('team_assignments', 'estimated_hours')) {
                $table->dropColumn('estimated_hours');
            }
        });
    }
};
