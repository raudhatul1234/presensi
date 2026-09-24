<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_dashboard_and_master_data_pages_are_available(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Scan QR Jadwal')
            ->assertSee('Buat QR Jadwal')
            ->assertSee('Modal Token QR Jadwal', false);

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Data Absensi Jadwal');

        $this->get(route('master.index'))
            ->assertOk()
            ->assertSee('Menu Master Data')
            ->assertSee('Daftarkan Mahasiswa')
            ->assertSee('Buat Jadwal & QR', false);
    }

    public function test_registered_student_can_be_checked_by_npm(): void
    {
        $student = $this->createStudent();

        $this->getJson(route('students.check', ['npm' => $student->npm]))
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('student.nama', $student->name)
            ->assertJsonPath('student.kelas', $student->class_name);
    }

    public function test_schedule_qr_token_can_be_looked_up(): void
    {
        $course = $this->createCourse();
        $schedule = $this->createSchedule($course);

        $this->getJson(route('schedules.lookup', ['token' => $schedule->qr_token]))
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('schedule.id', $schedule->id)
            ->assertJsonPath('schedule.course', $course->name);
    }

    public function test_student_scan_records_attendance_for_the_schedule(): void
    {
        $course = $this->createCourse();
        $student = $this->createStudent(courseIds: [$course->id]);
        $schedule = $this->createSchedule($course);

        $this->postJson(route('attendance.scan'), [
            'npm' => $student->npm,
            'nama' => $student->name,
            'schedule_token' => $schedule->qr_token,
        ])->assertCreated()
            ->assertJsonPath('message', 'Absensi berhasil dicatat sebagai hadir.')
            ->assertJsonPath('attendance.status', AttendanceStatus::Hadir->value)
            ->assertJsonPath('attendance.course', $course->name);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => today()->toDateString(),
            'status' => AttendanceStatus::Hadir->value,
            'source' => 'scanner',
        ]);
    }

    public function test_student_cannot_scan_the_same_schedule_twice(): void
    {
        $course = $this->createCourse();
        $student = $this->createStudent(courseIds: [$course->id]);
        $schedule = $this->createSchedule($course);
        $payload = [
            'npm' => $student->npm,
            'nama' => $student->name,
            'schedule_token' => $schedule->qr_token,
        ];

        $this->postJson(route('attendance.scan'), $payload)->assertCreated();
        $this->postJson(route('attendance.scan'), $payload)
            ->assertStatus(409)
            ->assertJsonPath('valid', false);

        $this->assertSame(1, Attendance::query()->count());
    }

    public function test_scan_rejects_invalid_class_unenrolled_student_and_closed_schedule(): void
    {
        $course = $this->createCourse();
        $unregisteredForCourse = $this->createStudent([
            'npm' => '221011009',
            'name' => 'Belum Mengikuti',
        ]);
        $wrongClass = $this->createStudent([
            'npm' => '221011010',
            'name' => 'Kelas Salah',
            'class_name' => 'TI-2B',
        ], [$course->id]);
        $schedule = $this->createSchedule($course, ['class_name' => 'TI-1A']);
        $closedSchedule = $this->createSchedule($course, [
            'schedule_date' => today()->subDay()->toDateString(),
            'qr_token' => 'closed-schedule-token',
        ]);

        $this->postJson(route('attendance.scan'), [
            'npm' => $wrongClass->npm,
            'nama' => 'Nama Tidak Sesuai',
            'schedule_token' => $schedule->qr_token,
        ])->assertUnprocessable()->assertJsonValidationErrors(['nama']);

        $this->postJson(route('attendance.scan'), [
            'npm' => $unregisteredForCourse->npm,
            'nama' => $unregisteredForCourse->name,
            'schedule_token' => $schedule->qr_token,
        ])->assertUnprocessable()->assertJsonValidationErrors(['mata_kuliah']);

        $this->postJson(route('attendance.scan'), [
            'npm' => $wrongClass->npm,
            'nama' => $wrongClass->name,
            'schedule_token' => $schedule->qr_token,
        ])->assertUnprocessable()->assertJsonValidationErrors(['kelas']);

        $enrolledStudent = $this->createStudent([
            'npm' => '221011011',
            'name' => 'Mahasiswa Terdaftar',
        ], [$course->id]);

        $this->postJson(route('attendance.scan'), [
            'npm' => $enrolledStudent->npm,
            'nama' => $enrolledStudent->name,
            'schedule_token' => $closedSchedule->qr_token,
        ])->assertUnprocessable()->assertJsonValidationErrors(['jadwal']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_data_can_be_filtered_by_schedule_and_course(): void
    {
        $course = $this->createCourse();
        $student = $this->createStudent(courseIds: [$course->id]);
        $schedule = $this->createSchedule($course);

        Attendance::query()->create([
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => today()->toDateString(),
            'status' => AttendanceStatus::Hadir,
            'source' => 'scanner',
        ]);

        $this->getJson(route('attendance.data', [
            'course_id' => $course->id,
            'schedule_id' => $schedule->id,
        ]))
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.hadir', 1)
            ->assertJsonPath('attendances.0.course', $course->name)
            ->assertJsonPath('attendances.0.schedule_id', $schedule->id);
    }

    public function test_master_data_can_register_course_student_and_schedule(): void
    {
        $this->post(route('master.courses.store'), [
            'code' => 'web-101',
            'name' => 'Pemrograman Web',
            'lecturer' => 'Dosen Test',
            'is_active' => 1,
        ])->assertRedirect(route('master.index'));

        $course = Course::query()->where('code', 'WEB-101')->firstOrFail();

        $this->post(route('master.students.store'), [
            'npm' => '221099999',
            'name' => 'Mahasiswa Baru',
            'class_name' => 'ti-5a',
            'course_ids' => [$course->id],
            'is_active' => 1,
        ])->assertRedirect(route('master.index').'#student-form');

        $this->assertDatabaseHas('students', [
            'npm' => '221099999',
            'class_name' => 'TI-5A',
        ]);
        $this->assertDatabaseHas('course_student', [
            'student_id' => Student::query()->where('npm', '221099999')->value('id'),
            'course_id' => $course->id,
        ]);

        $this->post(route('master.schedules.store'), [
            'course_id' => $course->id,
            'class_name' => 'ti-5a',
            'schedule_date' => today()->addDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room' => 'Ruang 101',
            'is_active' => 1,
        ])->assertRedirect(route('master.index').'#schedule-form');

        $this->assertDatabaseHas('schedules', [
            'course_id' => $course->id,
            'class_name' => 'TI-5A',
            'room' => 'Ruang 101',
        ]);
    }

    private function createCourse(array $attributes = []): Course
    {
        return Course::query()->create(array_merge([
            'code' => 'WEB-'.Str::upper(Str::random(4)),
            'name' => 'Mata Kuliah Test',
            'lecturer' => 'Dosen Test',
            'is_active' => true,
        ], $attributes));
    }

    private function createStudent(array $attributes = [], array $courseIds = []): Student
    {
        $student = Student::query()->create(array_merge([
            'npm' => '221011001',
            'name' => 'Siti Aminah',
            'class_name' => 'TI-1A',
            'is_active' => true,
        ], $attributes));

        if ($courseIds !== []) {
            $student->courses()->sync($courseIds);
        }

        return $student;
    }

    private function createSchedule(Course $course, array $attributes = []): Schedule
    {
        return Schedule::query()->create(array_merge([
            'course_id' => $course->id,
            'class_name' => 'TI-1A',
            'schedule_date' => today()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'room' => 'Ruang Test',
            'qr_token' => Str::random(64),
            'is_active' => true,
        ], $attributes));
    }
}
