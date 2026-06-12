<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('deliverable_url')->nullable()->after('sort_order');
            $table->string('deliverable_file_path')->nullable()->after('deliverable_url');
            $table->string('deliverable_type', 40)->nullable()->after('deliverable_file_path');
            $table->timestamp('deliverable_submitted_at')->nullable()->after('deliverable_type');
            $table->boolean('is_design_deliverable')->default(false)->after('deliverable_submitted_at');

            $table->index(['project_id', 'is_design_deliverable']);
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'is_design_deliverable']);
            $table->dropColumn([
                'deliverable_url',
                'deliverable_file_path',
                'deliverable_type',
                'deliverable_submitted_at',
                'is_design_deliverable',
            ]);
        });
    }
};
