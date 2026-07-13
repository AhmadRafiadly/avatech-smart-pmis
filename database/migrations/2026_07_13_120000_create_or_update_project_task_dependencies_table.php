<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_task_dependencies')) {
            Schema::create('project_task_dependencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('predecessor_task_id')->constrained('project_tasks')->cascadeOnDelete();
                $table->foreignId('successor_task_id')->constrained('project_tasks')->cascadeOnDelete();
                $table->string('dependency_type', 40)->default('finish_to_start');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['project_id', 'predecessor_task_id', 'successor_task_id'], 'ptd_project_predecessor_successor_unique');
                $table->index(['project_id', 'successor_task_id']);
            });

            return;
        }

        $hasLegacyTaskId = Schema::hasColumn('project_task_dependencies', 'task_id');
        $hasLegacyDependsOnTaskId = Schema::hasColumn('project_task_dependencies', 'depends_on_task_id');
        $hasLegacyType = Schema::hasColumn('project_task_dependencies', 'type');
        $hasLegacyCreatedBy = Schema::hasColumn('project_task_dependencies', 'created_by_user_id');

        Schema::table('project_task_dependencies', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_dependencies', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('project_task_dependencies', 'predecessor_task_id')) {
                $table->foreignId('predecessor_task_id')->nullable()->after('project_id')->constrained('project_tasks')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('project_task_dependencies', 'successor_task_id')) {
                $table->foreignId('successor_task_id')->nullable()->after('predecessor_task_id')->constrained('project_tasks')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('project_task_dependencies', 'dependency_type')) {
                $table->string('dependency_type', 40)->default('finish_to_start')->after('successor_task_id');
            }

            if (! Schema::hasColumn('project_task_dependencies', 'notes')) {
                $table->text('notes')->nullable()->after('dependency_type');
            }

            if (! Schema::hasColumn('project_task_dependencies', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('project_task_dependencies', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('project_task_dependencies', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if ($hasLegacyTaskId && $hasLegacyDependsOnTaskId) {
            DB::table('project_task_dependencies')
                ->whereNull('successor_task_id')
                ->update(['successor_task_id' => DB::raw('task_id')]);

            DB::table('project_task_dependencies')
                ->whereNull('predecessor_task_id')
                ->update(['predecessor_task_id' => DB::raw('depends_on_task_id')]);
        }

        if ($hasLegacyType) {
            DB::table('project_task_dependencies')
                ->whereNull('dependency_type')
                ->update(['dependency_type' => DB::raw('type')]);
        }

        if ($hasLegacyCreatedBy) {
            DB::table('project_task_dependencies')
                ->whereNull('created_by')
                ->update(['created_by' => DB::raw('created_by_user_id')]);
        }
    }

    public function down(): void
    {
    }
};
