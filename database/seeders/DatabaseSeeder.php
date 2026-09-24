<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        Course::query()->upsert([
            [
                'code' => 'WEB-101',
                'name' => 'Pemrograman Web',
                'lecturer' => 'Dosen Pengampu',
                'description' => 'Mata kuliah pemrograman berbasis web.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DB-201',
                'name' => 'Basis Data',
                'lecturer' => 'Dosen Pengampu',
                'description' => 'Mata kuliah perancangan dan pengelolaan basis data.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'ALG-101',
                'name' => 'Algoritma dan Pemrograman Dasar',
                'lecturer' => 'Dosen Pengampu',
                'description' => 'Mata kuliah dasar algoritma dan pemrograman.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['name', 'lecturer', 'description', 'is_active', 'updated_at']);

        Student::query()->upsert([
            [
                'npm' => '221011001',
                'name' => 'Siti Aminah',
                'class_name' => 'TI-1A',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'npm' => '221011002',
                'name' => 'Bagus Prasetyo',
                'class_name' => 'TI-1A',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'npm' => '221021001',
                'name' => 'Dewi Lestari',
                'class_name' => 'TI-1B',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'npm' => '221031001',
                'name' => 'Rizky Firmansyah',
                'class_name' => 'TI-2A',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'npm' => '221041001',
                'name' => 'Putri Maharani',
                'class_name' => 'TI-3B',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'npm' => '221051001',
                'name' => 'Andi Saputra',
                'class_name' => 'TI-4A',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['npm'], ['name', 'class_name', 'is_active', 'updated_at']);

        $webCourse = Course::query()->where('code', 'WEB-101')->firstOrFail();
        $dbCourse = Course::query()->where('code', 'DB-201')->firstOrFail();
        $algorithmCourse = Course::query()->where('code', 'ALG-101')->firstOrFail();

        $students = Student::query()->get()->keyBy('npm');
        $students['221011001']->courses()->syncWithoutDetaching([$webCourse->id]);
        $students['221011002']->courses()->syncWithoutDetaching([$webCourse->id]);
        $students['221031001']->courses()->syncWithoutDetaching([$dbCourse->id]);
        $students['221041001']->courses()->syncWithoutDetaching([$algorithmCourse->id]);
        $students['221051001']->courses()->syncWithoutDetaching([$webCourse->id]);

        $today = today()->toDateString();

        $this->createDemoSchedule($webCourse, 'TI-1A', $today, 'demo-web-ti1a');
        $this->createDemoSchedule($dbCourse, 'TI-2A', $today, 'demo-db-ti2a');
        $this->createDemoSchedule($algorithmCourse, 'TI-3B', $today, 'demo-alg-ti3b');
        $this->createDemoSchedule($webCourse, 'TI-4A', $today, 'demo-web-ti4a');
    }

    private function createDemoSchedule(Course $course, string $className, string $date, string $token): void
    {
        Schedule::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'class_name' => $className,
                'schedule_date' => $date,
            ],
            [
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'room' => 'Ruang Demo',
                'qr_token' => $token,
                'is_active' => true,
            ],
        );
    }
}
