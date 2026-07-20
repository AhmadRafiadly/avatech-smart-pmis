<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_requirement_inbox_items', function (Blueprint $table) {
            $table->text('summary')->nullable()->change();
            $table->string('original_filename', 255)->nullable()->after('file_path');
            $table->string('mime_type', 100)->nullable()->after('original_filename');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->char('file_sha256', 64)->nullable()->after('file_size');
            $table->longText('extracted_text')->nullable()->after('file_sha256');
            $table->string('extraction_status', 30)->default('not_applicable')->after('extracted_text');
            $table->timestamp('extracted_at')->nullable()->after('extraction_status');
        });
    }

    public function down(): void
    {
        DB::table('project_requirement_inbox_items')->whereNull('summary')->update(['summary' => '']);

        Schema::table('project_requirement_inbox_items', function (Blueprint $table) {
            $table->text('summary')->nullable(false)->change();
            $table->dropColumn([
                'original_filename',
                'mime_type',
                'file_size',
                'file_sha256',
                'extracted_text',
                'extraction_status',
                'extracted_at',
            ]);
        });
    }
};
