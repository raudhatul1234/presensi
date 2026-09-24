<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('master.index', [
            'students' => Student::query()
                ->with('courses')
                ->orderBy('name')
                ->get(),
            'courses' => Course::query()
                ->withCount('students')
                ->orderBy('code')
                ->get(),
            'schedules' => Schedule::query()
                ->with('course')
                ->orderByDesc('schedule_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $this->normalizeStudentRequest($request);

        $validated = $request->validate([
            'npm' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/', 'unique:students,npm'],
            'name' => ['required', 'string', 'max:100'],
            'class_name' => ['required', 'string', 'max:20'],
            'course_ids' => ['nullable', 'array', 'max:50'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'npm.required' => 'NPM wajib diisi.',
            'npm.unique' => 'NPM sudah terdaftar.',
            'npm.regex' => 'Format NPM tidak valid.',
            'name.required' => 'Nama wajib diisi.',
            'class_name.required' => 'Kelas wajib diisi.',
        ]);

        $student = Student::query()->create([
            'npm' => $validated['npm'],
            'name' => $validated['name'],
            'class_name' => $validated['class_name'],
            'is_active' => $request->boolean('is_active', true),
        ]);
        $student->courses()->sync($validated['course_ids'] ?? []);

        return redirect()
            ->route('master.index')
            ->with('success', "Mahasiswa {$student->name} berhasil didaftarkan.")
            ->withFragment('student-form');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:120'],
            'lecturer' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.unique' => 'Kode mata kuliah sudah terdaftar.',
            'name.required' => 'Nama mata kuliah wajib diisi.',
        ]);

        Course::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'lecturer' => $validated['lecturer'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('master.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $request->merge([
            'class_name' => strtoupper(trim((string) $request->input('class_name'))),
        ]);

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'class_name' => ['required', 'string', 'max:20'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'course_id.required' => 'Mata kuliah wajib dipilih.',
            'course_id.exists' => 'Mata kuliah tidak ditemukan.',
            'class_name.required' => 'Kelas wajib diisi.',
            'schedule_date.required' => 'Tanggal jadwal wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $course = Course::query()->find($validated['course_id']);

        if ($course === null || ! $course->is_active) {
            throw ValidationException::withMessages([
                'course_id' => 'Mata kuliah tidak aktif atau tidak ditemukan.',
            ]);
        }

        $duplicate = Schedule::query()
            ->where('course_id', $validated['course_id'])
            ->where('class_name', $validated['class_name'])
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->where('start_time', $validated['start_time'].':00')
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'schedule_date' => 'Jadwal dengan mata kuliah, kelas, tanggal, dan jam tersebut sudah ada.',
            ]);
        }

        $schedule = Schedule::query()->create([
            'course_id' => $validated['course_id'],
            'class_name' => $validated['class_name'],
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'room' => $validated['room'] ?? null,
            'qr_token' => $this->newQrToken(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('master.index')
            ->with('success', "Jadwal {$schedule->course->name} berhasil dibuat. QR sudah siap digunakan.")
            ->withFragment('schedule-form');
    }

    public function toggleStudent(Student $student): RedirectResponse
    {
        $student->update(['is_active' => ! $student->is_active]);

        return redirect()
            ->route('master.index')
            ->with('success', "Status {$student->name} berhasil diperbarui.")
            ->withFragment('student-list');
    }

    public function toggleCourse(Course $course): RedirectResponse
    {
        $course->update(['is_active' => ! $course->is_active]);

        return redirect()
            ->route('master.index')
            ->with('success', "Status mata kuliah {$course->code} berhasil diperbarui.")
            ->withFragment('course-list');
    }

    public function toggleSchedule(Schedule $schedule): RedirectResponse
    {
        $schedule->update(['is_active' => ! $schedule->is_active]);

        return redirect()
            ->route('master.index')
            ->with('success', 'Status jadwal berhasil diperbarui.')
            ->withFragment('schedule-list');
    }

    public function scheduleQr(Schedule $schedule): View
    {
        return view('schedules.qr', [
            'schedule' => $schedule->load('course'),
        ]);
    }

    private function normalizeStudentRequest(Request $request): void
    {
        $request->merge([
            'npm' => strtoupper(trim((string) $request->input('npm'))),
            'class_name' => strtoupper(trim((string) $request->input('class_name'))),
        ]);
    }

    private function newQrToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Schedule::query()->where('qr_token', $token)->exists());

        return $token;
    }
}
