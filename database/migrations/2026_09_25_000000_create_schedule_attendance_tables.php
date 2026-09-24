<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('lecturer', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('course_student', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'student_id']);
        });

        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('class_name', 20)->index();
            $table->date('schedule_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->string('qr_token', 80)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['schedule_date', 'class_name', 'is_active'], 'schedules_date_class_active_index');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignId('schedule_id')
                ->nullable()
                ->after('student_id')
                ->constrained('schedules')
                ->nullOnDelete();
        });

        $this->replaceAttendanceUniqueIndex();
    }

    public function down(): void
    {
        $this->dropScheduleUniqueIndex();

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');
        });

        Schema::dropIfExists('schedules');
        Schema::dropIfExists('course_student');
        Schema::dropIfExists('courses');
    }

    private function replaceAttendanceUniqueIndex(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "attendances_student_id_attendance_date_unique"');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "attendance_schedule_student_unique" ON "attendances" ("schedule_id", "student_id")');

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE `attendances` DROP INDEX `attendances_student_id_attendance_date_unique`');
            DB::statement('ALTER TABLE `attendances` ADD UNIQUE INDEX `attendance_schedule_student_unique` (`schedule_id`, `student_id`)');

            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropUnique('attendances_student_id_attendance_date_unique');
            $table->unique(['schedule_id', 'student_id'], 'attendance_schedule_student_unique');
        });
    }

    private function dropScheduleUniqueIndex(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "attendance_schedule_student_unique"');

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE `attendances` DROP INDEX `attendance_schedule_student_unique`');

            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropUnique('attendance_schedule_student_unique');
        });
    }
};
