<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->unique(
                ['course_id', 'class_name', 'schedule_date', 'start_time'],
                'schedules_identity_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropUnique('schedules_identity_unique');
        });
    }
};
