<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('tier');
            $table->string('location')->nullable()->after('industry');
            $table->string('pic_name')->nullable()->after('location');
            $table->string('pic_role')->nullable()->after('pic_name');
            $table->string('email')->nullable()->after('pic_role');
            $table->string('phone')->nullable()->after('email');
            $table->text('description')->nullable()->after('phone');
            $table->unsignedInteger('total_engagement')->default(0)->after('description');
            $table->unsignedTinyInteger('relationship_health')->default(50)->after('total_engagement');
            $table->string('last_touch_label')->nullable()->after('relationship_health');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'industry',
                'location',
                'pic_name',
                'pic_role',
                'email',
                'phone',
                'description',
                'total_engagement',
                'relationship_health',
                'last_touch_label',
            ]);
        });
    }
};
