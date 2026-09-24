<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.dashboard', [
            'classes' => Student::classOptions(),
            'statuses' => AttendanceStatus::cases(),
            'courses' => Course::query()->orderBy('name')->get(),
            'schedules' => Schedule::query()
                ->with('course')
                ->whereHas('course', fn ($query) => $query->active())
                ->orderByDesc('schedule_date')
                ->orderBy('start_time')
                ->limit(100)
                ->get(),
        ]);
    }

    public function checkStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'npm' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'nama' => ['nullable', 'string', 'max:100'],
        ], [
            'npm.required' => 'NPM wajib diisi.',
            'npm.regex' => 'Format NPM tidak valid.',
        ]);

        $npm = strtoupper(trim($validated['npm']));
        $student = Student::query()
            ->active()
            ->where('npm', $npm)
            ->first();

        if ($student === null) {
            return response()->json([
                'valid' => false,
                'message' => 'NPM tidak terdaftar atau akun mahasiswa sedang tidak aktif.',
            ], 404);
        }

        if (! empty($validated['nama']) && ! $this->sameName($student->name, $validated['nama'])) {
            throw ValidationException::withMessages([
                'nama' => 'Nama tidak sesuai dengan data NPM tersebut.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Data mahasiswa ditemukan.',
            'student' => $this->studentPayload($student),
        ]);
    }

    public function lookupSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:100'],
        ], [
            'token.required' => 'Token QR jadwal wajib diisi.',
        ]);

        $schedule = Schedule::query()
            ->with('course')
            ->active()
            ->where('qr_token', trim($validated['token']))
            ->first();

        if ($schedule === null || ! $schedule->course?->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'QR jadwal tidak valid atau sudah tidak aktif.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'message' => 'QR jadwal valid.',
            'schedule' => $this->schedulePayload($schedule),
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'npm' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'nama' => ['required', 'string', 'max:100'],
            'schedule_token' => ['required', 'string', 'max:100'],
        ], [
            'npm.required' => 'NPM wajib diisi.',
            'npm.regex' => 'Format NPM tidak valid.',
            'nama.required' => 'Nama wajib diisi.',
            'schedule_token.required' => 'Scan QR jadwal terlebih dahulu.',
        ]);

        $npm = strtoupper(trim($validated['npm']));
        $student = Student::query()
            ->active()
            ->where('npm', $npm)
            ->first();
        $schedule = Schedule::query()
            ->with('course')
            ->active()
            ->where('qr_token', trim($validated['schedule_token']))
            ->first();

        if ($student === null) {
            return response()->json([
                'valid' => false,
                'message' => 'NPM tidak terdaftar atau akun mahasiswa sedang tidak aktif.',
            ], 404);
        }

        if ($schedule === null || ! $schedule->course?->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'QR jadwal tidak valid atau sudah tidak aktif.',
            ], 404);
        }

        if (! $this->sameName($student->name, $validated['nama'])) {
            throw ValidationException::withMessages([
                'nama' => 'Nama tidak sesuai dengan data mahasiswa.',
            ]);
        }

        if (strtoupper($student->class_name) !== strtoupper($schedule->class_name)) {
            throw ValidationException::withMessages([
                'kelas' => 'Kelas mahasiswa tidak sesuai dengan kelas pada jadwal.',
            ]);
        }

        if (! $student->courses()->whereKey($schedule->course_id)->exists()) {
            throw ValidationException::withMessages([
                'mata_kuliah' => 'Mahasiswa belum terdaftar pada mata kuliah jadwal ini.',
            ]);
        }

        if (! $schedule->isOpenForAttendance()) {
            throw ValidationException::withMessages([
                'jadwal' => "Absensi hanya dapat dibuka pada {$schedule->date_label} pukul {$schedule->time_range}.",
            ]);
        }

        $attendanceDate = $schedule->schedule_date->toDateString();
        $existing = Attendance::query()
            ->where('student_id', $student->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        if ($existing !== null) {
            return $this->duplicateAttendanceResponse($schedule);
        }

        try {
            $attendance = Attendance::query()->create([
                'student_id' => $student->id,
                'schedule_id' => $schedule->id,
                'attendance_date' => $attendanceDate,
                'status' => AttendanceStatus::Hadir,
                'source' => 'scanner',
            ]);
        } catch (QueryException $exception) {
            if (Attendance::query()
                ->where('student_id', $student->id)
                ->where('schedule_id', $schedule->id)
                ->exists()) {
                return $this->duplicateAttendanceResponse($schedule);
            }

            throw $exception;
        }

        $attendance->setRelation('student', $student);
        $attendance->setRelation('schedule', $schedule);

        return response()->json([
            'valid' => true,
            'message' => 'Absensi berhasil dicatat sebagai hadir.',
            'attendance' => $this->attendancePayload($attendance),
            'schedule' => $this->schedulePayload($schedule),
        ], 201);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(AttendanceStatus::values())],
            'class_name' => ['nullable', 'string', 'max:20'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'schedule_id' => ['nullable', 'integer', 'exists:schedules,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = Attendance::query()->with(['student', 'schedule.course']);
        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();
        $hadir = (clone $query)
            ->where('status', AttendanceStatus::Hadir->value)
            ->count();

        $limit = (int) ($filters['limit'] ?? 100);
        $attendances = (clone $query)
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'stats' => [
                'total' => $total,
                'hadir' => $hadir,
                'belum_hadir' => max(0, $total - $hadir),
            ],
            'meta' => [
                'filtered_total' => $total,
                'returned' => $attendances->count(),
                'limit' => $limit,
            ],
            'attendances' => $attendances
                ->map(fn (Attendance $attendance): array => $this->attendancePayload($attendance))
                ->values(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->whereHas('student', function (Builder $studentQuery) use ($search): void {
                        $studentQuery->where(function (Builder $nameQuery) use ($search): void {
                            $nameQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('npm', 'like', "%{$search}%");
                        });
                    })
                    ->orWhereHas('schedule.course', function (Builder $courseQuery) use ($search): void {
                        $courseQuery->where(function (Builder $courseNameQuery) use ($search): void {
                            $courseNameQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                    })
                    ->orWhereHas('schedule', function (Builder $scheduleQuery) use ($search): void {
                        $scheduleQuery->where('room', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['class_name'])) {
            $class = strtoupper(trim($filters['class_name']));
            $query->whereHas('student', function (Builder $studentQuery) use ($class): void {
                $studentQuery->where('class_name', $class);
            });
        }

        if (! empty($filters['course_id'])) {
            $query->whereHas('schedule', function (Builder $scheduleQuery) use ($filters): void {
                $scheduleQuery->where('course_id', $filters['course_id']);
            });
        }

        if (! empty($filters['schedule_id'])) {
            $query->where('schedule_id', $filters['schedule_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('attendance_date', $filters['date']);
        }
    }

    private function sameName(string $registeredName, string $submittedName): bool
    {
        return Str::lower(Str::squish($registeredName)) === Str::lower(Str::squish($submittedName));
    }

    /**
     * @return array{npm: string, nama: string, kelas: string}
     */
    private function studentPayload(Student $student): array
    {
        return [
            'npm' => $student->npm,
            'nama' => $student->name,
            'kelas' => $student->class_name,
        ];
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function schedulePayload(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'token' => $schedule->qr_token,
            'course_id' => $schedule->course_id,
            'course_code' => $schedule->course?->code ?? '-',
            'course' => $schedule->course?->name ?? '-',
            'class_name' => $schedule->class_name,
            'date' => $schedule->date_label,
            'date_iso' => $schedule->schedule_date?->format('Y-m-d'),
            'time' => $schedule->time_range,
            'room' => $schedule->room ?: '-',
            'is_open' => $schedule->isOpenForAttendance(),
        ];
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function attendancePayload(Attendance $attendance): array
    {
        $schedule = $attendance->schedule;

        return [
            'id' => $attendance->id,
            'nama' => $attendance->student->name,
            'npm' => $attendance->student->npm,
            'kelas' => $attendance->student->class_name,
            'course_id' => $schedule?->course_id,
            'course_code' => $schedule?->course?->code ?? '-',
            'course' => $schedule?->course?->name ?? '-',
            'schedule_id' => $schedule?->id,
            'schedule' => $schedule ? "{$schedule->date_label} ({$schedule->time_range})" : '-',
            'room' => $schedule?->room ?: '-',
            'tanggal' => $attendance->attendance_date?->format('d/m/Y') ?? '-',
            'tanggal_iso' => $attendance->attendance_date?->format('Y-m-d'),
            'jam' => $attendance->created_at?->format('H:i:s') ?? '-',
            'recorded_at' => $attendance->created_at?->toIso8601String(),
            'status' => $attendance->status->value,
            'source' => $attendance->source,
        ];
    }

    private function duplicateAttendanceResponse(Schedule $schedule): JsonResponse
    {
        return response()->json([
            'valid' => false,
            'message' => "Mahasiswa sudah tercatat hadir pada jadwal {$schedule->course?->name}.",
            'schedule' => $this->schedulePayload($schedule),
        ], 409);
    }
}
