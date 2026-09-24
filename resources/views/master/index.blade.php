@extends('layouts.app')

@section('title', 'Master Data Absensi')

@section('content')
    @php
        $studentErrors = $errors->hasAny(['npm', 'name', 'class_name', 'course_ids', 'course_ids.*']);
        $courseErrors = $errors->hasAny(['code', 'name', 'lecturer', 'description', 'is_active']);
        $scheduleErrors = $errors->hasAny(['course_id', 'class_name', 'schedule_date', 'start_time', 'end_time', 'room', 'is_active']);
    @endphp

    <section class="management-section">
        <div class="container">
            <div class="section-title management-title">
                <span>PENGATURAN SISTEM</span>
                <h2>Master Data</h2>
                <p>Semua aksi pengelolaan data dan QR absensi tersedia melalui navigasi ini.</p>
            </div>

            <div class="master-layout" data-master-nav>
                <aside class="master-sidebar">
                    <div class="master-sidebar-heading">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i>
                        <span>Menu Master Data</span>
                    </div>
                    <nav class="master-subnav" aria-label="Navigasi master data">
                        <a href="#master-students" class="active" data-master-section-link="master-students" aria-current="page">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            <span>Data Mahasiswa</span>
                            <small>{{ $students->count() }} data</small>
                        </a>
                        <a href="#master-courses" data-master-section-link="master-courses">
                            <i class="fa-solid fa-book" aria-hidden="true"></i>
                            <span>Mata Kuliah</span>
                            <small>{{ $courses->count() }} data</small>
                        </a>
                        <a href="#master-schedules" data-master-section-link="master-schedules">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                            <span>Jadwal & QR</span>
                            <small>{{ $schedules->count() }} data</small>
                        </a>
                    </nav>

                    <div class="master-sidebar-links">
                        <a href="{{ route('home') }}">
                            <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                            Scan QR Jadwal
                        </a>
                        <a href="{{ route('attendance.index') }}">
                            <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                            Lihat Dashboard
                        </a>
                    </div>

                    <div class="master-sidebar-tip">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <p>Buat mata kuliah dan jadwal sebelum mahasiswa dapat scan QR.</p>
                    </div>
                </aside>

                <div class="master-content">
                    <section class="master-section" id="master-students" data-master-section>
                        <div class="master-section-header">
                            <div>
                                <span class="master-section-kicker">MAHASISWA</span>
                                <h3>Data Mahasiswa</h3>
                                <p>Daftarkan mahasiswa dan tentukan mata kuliah yang diikuti.</p>
                            </div>
                            <span class="master-count">{{ $students->count() }} terdaftar</span>
                        </div>

                        <div class="master-action-grid">
                            <div class="management-card" id="student-form">
                                <div class="management-card-header">
                                    <div class="management-icon student-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Daftarkan Mahasiswa</h3>
                                        <p>Input data mahasiswa baru.</p>
                                    </div>
                                </div>

                                <form action="{{ route('master.students.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="masterStudentName">Nama Lengkap</label>
                                        <input type="text" id="masterStudentName" name="name" value="{{ $studentErrors ? old('name') : '' }}" maxlength="100" required>
                                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="masterStudentNpm">NPM</label>
                                        <input type="text" id="masterStudentNpm" name="npm" value="{{ $studentErrors ? old('npm') : '' }}" maxlength="30" pattern="[A-Za-z0-9-]+" required>
                                        @error('npm')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="masterStudentClass">Kelas</label>
                                        <input type="text" id="masterStudentClass" name="class_name" value="{{ $studentErrors ? old('class_name') : '' }}" maxlength="20" placeholder="Contoh: TI-1A" required>
                                        @error('class_name')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label>Mata Kuliah</label>
                                        @php($selectedCourses = $studentErrors ? old('course_ids', []) : [])
                                        @if (! is_array($selectedCourses))
                                            @php($selectedCourses = [])
                                        @endif
                                        <div class="checkbox-grid">
                                            @forelse ($courses as $course)
                                                <label class="check-option">
                                                    <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array($course->id, $selectedCourses))>
                                                    <span>{{ $course->code }} — {{ $course->name }}</span>
                                                </label>
                                            @empty
                                                <small class="muted-text">Tambahkan mata kuliah terlebih dahulu.</small>
                                            @endforelse
                                        </div>
                                        @error('course_ids')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <label class="check-option compact-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($studentErrors ? old('is_active', true) : true)>
                                        <span>Mahasiswa aktif dan boleh absensi</span>
                                    </label>
                                    <button type="submit" class="btn-submit compact-submit">
                                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                        Simpan Mahasiswa
                                    </button>
                                </form>
                            </div>

                            <div class="management-card list-card" id="student-list">
                                <div class="management-card-header">
                                    <div class="management-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Daftar Mahasiswa</h3>
                                        <p>{{ $students->count() }} mahasiswa terdaftar.</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="management-table">
                                        <thead><tr><th>Nama</th><th>NPM</th><th>Kelas</th><th>Mata Kuliah</th><th>Status</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                            @forelse ($students as $student)
                                                <tr>
                                                    <td>{{ $student->name }}</td>
                                                    <td>{{ $student->npm }}</td>
                                                    <td>{{ $student->class_name }}</td>
                                                    <td>
                                                        @forelse ($student->courses as $course)
                                                            <span class="course-pill">{{ $course->code }}</span>
                                                        @empty
                                                            <span class="muted-text">Belum ada</span>
                                                        @endforelse
                                                    </td>
                                                    <td><span class="status-badge {{ $student->is_active ? 'status-hadir' : 'status-alpa' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                                    <td>
                                                        <form action="{{ route('master.students.toggle', $student) }}" method="POST" class="inline-form" data-confirm="Status {{ $student->name }} akan diubah." data-confirm-title="Ubah Status Mahasiswa" data-confirm-button="Ya, ubah status">
                                                            @csrf
                                                            <button type="submit" class="table-action">{{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="table-empty">Belum ada mahasiswa.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="master-section" id="master-courses" data-master-section>
                        <div class="master-section-header">
                            <div>
                                <span class="master-section-kicker">MATA KULIAH</span>
                                <h3>Data Mata Kuliah</h3>
                                <p>Kelola mata kuliah yang digunakan pada jadwal dan absensi.</p>
                            </div>
                            <span class="master-count">{{ $courses->count() }} aktif</span>
                        </div>

                        <div class="master-action-grid">
                            <div class="management-card" id="course-form">
                                <div class="management-card-header">
                                    <div class="management-icon course-icon"><i class="fa-solid fa-book" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Tambah Mata Kuliah</h3>
                                        <p>Buat mata kuliah baru.</p>
                                    </div>
                                </div>

                                <form action="{{ route('master.courses.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="masterCourseCode">Kode Mata Kuliah</label>
                                        <input type="text" id="masterCourseCode" name="code" value="{{ $courseErrors ? old('code') : '' }}" maxlength="30" placeholder="Contoh: WEB-101" required>
                                        @error('code')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="masterCourseName">Nama Mata Kuliah</label>
                                        <input type="text" id="masterCourseName" name="name" value="{{ $courseErrors ? old('name') : '' }}" maxlength="120" placeholder="Contoh: Pemrograman Web" required>
                                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="masterCourseLecturer">Dosen Pengampu</label>
                                        <input type="text" id="masterCourseLecturer" name="lecturer" value="{{ $courseErrors ? old('lecturer') : '' }}" maxlength="100" placeholder="Nama dosen (opsional)">
                                        @error('lecturer')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="masterCourseDescription">Deskripsi</label>
                                        <textarea id="masterCourseDescription" name="description" rows="3" maxlength="1000" placeholder="Deskripsi singkat (opsional)">{{ $courseErrors ? old('description') : '' }}</textarea>
                                        @error('description')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <label class="check-option compact-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($courseErrors ? old('is_active', true) : true)>
                                        <span>Mata kuliah aktif</span>
                                    </label>
                                    <button type="submit" class="btn-submit compact-submit">
                                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                        Simpan Mata Kuliah
                                    </button>
                                </form>
                            </div>

                            <div class="management-card list-card" id="course-list">
                                <div class="management-card-header">
                                    <div class="management-icon course-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Daftar Mata Kuliah</h3>
                                        <p>{{ $courses->count() }} mata kuliah tersedia.</p>
                                    </div>
                                </div>
                                <div class="course-list-grid">
                                    @forelse ($courses as $course)
                                        <div class="course-list-item">
                                            <div>
                                                <strong>{{ $course->code }}</strong>
                                                <span>{{ $course->name }}</span>
                                                <small>{{ $course->lecturer ?: 'Dosen belum ditentukan' }} · {{ $course->students_count }} mahasiswa</small>
                                            </div>
                                            <div class="course-list-actions">
                                                <span class="status-badge {{ $course->is_active ? 'status-hadir' : 'status-alpa' }}">{{ $course->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                <form action="{{ route('master.courses.toggle', $course) }}" method="POST" class="inline-form" data-confirm="Status mata kuliah {{ $course->code }} akan diubah." data-confirm-title="Ubah Status Mata Kuliah" data-confirm-button="Ya, ubah status">
                                                    @csrf
                                                    <button type="submit" class="table-action">{{ $course->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="muted-text">Belum ada mata kuliah.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="master-section" id="master-schedules" data-master-section>
                        <div class="master-section-header">
                            <div>
                                <span class="master-section-kicker">JADWAL & QR</span>
                                <h3>Data Jadwal</h3>
                                <p>Buat jadwal, aktifkan QR, dan buka halaman QR untuk dipindai mahasiswa.</p>
                            </div>
                            <span class="master-count">{{ $schedules->count() }} jadwal</span>
                        </div>

                        <div class="master-action-grid">
                            <div class="management-card" id="schedule-form">
                                <div class="management-card-header">
                                    <div class="management-icon schedule-icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Buat Jadwal & QR</h3>
                                        <p>QR digunakan untuk mencatat hadir.</p>
                                    </div>
                                </div>

                                <form action="{{ route('master.schedules.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="masterScheduleCourse">Mata Kuliah</label>
                                        <select id="masterScheduleCourse" name="course_id" required>
                                            <option value="">Pilih Mata Kuliah</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}" @selected($scheduleErrors && (string) old('course_id') === (string) $course->id)>
                                                    {{ $course->code }} — {{ $course->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('course_id')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-grid two-columns">
                                        <div class="form-group">
                                            <label for="masterScheduleClass">Kelas</label>
                                            <input type="text" id="masterScheduleClass" name="class_name" value="{{ $scheduleErrors ? old('class_name') : '' }}" maxlength="20" placeholder="TI-1A" required>
                                            @error('class_name')<small class="field-error">{{ $message }}</small>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="masterScheduleDate">Tanggal</label>
                                            <input type="date" id="masterScheduleDate" name="schedule_date" value="{{ $scheduleErrors ? old('schedule_date') : '' }}" required>
                                            @error('schedule_date')<small class="field-error">{{ $message }}</small>@enderror
                                        </div>
                                    </div>
                                    <div class="form-grid two-columns">
                                        <div class="form-group">
                                            <label for="masterScheduleStart">Mulai</label>
                                            <input type="time" id="masterScheduleStart" name="start_time" value="{{ $scheduleErrors ? old('start_time') : '' }}" required>
                                            @error('start_time')<small class="field-error">{{ $message }}</small>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="masterScheduleEnd">Selesai</label>
                                            <input type="time" id="masterScheduleEnd" name="end_time" value="{{ $scheduleErrors ? old('end_time') : '' }}" required>
                                            @error('end_time')<small class="field-error">{{ $message }}</small>@enderror
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="masterScheduleRoom">Ruangan</label>
                                        <input type="text" id="masterScheduleRoom" name="room" value="{{ $scheduleErrors ? old('room') : '' }}" maxlength="50" placeholder="Contoh: Ruang 101 (opsional)">
                                        @error('room')<small class="field-error">{{ $message }}</small>@enderror
                                    </div>
                                    <label class="check-option compact-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($scheduleErrors ? old('is_active', true) : true)>
                                        <span>Jadwal aktif dan dapat dipindai</span>
                                    </label>
                                    <button type="submit" class="btn-submit compact-submit">
                                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                        Simpan Jadwal & QR
                                    </button>
                                </form>
                            </div>

                            <div class="management-card list-card" id="schedule-list">
                                <div class="management-card-header">
                                    <div class="management-icon schedule-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></div>
                                    <div>
                                        <h3>Daftar Jadwal & QR</h3>
                                        <p>QR dapat dibuka dan dipresentasikan kepada mahasiswa.</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="management-table">
                                        <thead><tr><th>Mata Kuliah</th><th>Kelas</th><th>Jadwal</th><th>Ruang</th><th>Status</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                            @forelse ($schedules as $schedule)
                                                <tr>
                                                    <td><strong>{{ $schedule->course->code }}</strong><br>{{ $schedule->course->name }}</td>
                                                    <td>{{ $schedule->class_name }}</td>
                                                    <td>{{ $schedule->date_label }}<br><span class="muted-text">{{ $schedule->time_range }}</span></td>
                                                    <td>{{ $schedule->room ?: '-' }}</td>
                                                    <td><span class="status-badge {{ $schedule->is_active ? 'status-hadir' : 'status-alpa' }}">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                                    <td>
                                                        <a href="{{ route('schedules.qr', $schedule) }}" class="table-action" target="_blank" rel="noopener">
                                                            <i class="fa-solid fa-qrcode" aria-hidden="true"></i> Buka QR
                                                        </a>
                                                        <form action="{{ route('master.schedules.toggle', $schedule) }}" method="POST" class="inline-form" data-confirm="Status jadwal {{ $schedule->course->code }} akan diubah." data-confirm-title="Ubah Status Jadwal" data-confirm-button="Ya, ubah status">
                                                            @csrf
                                                            <button type="submit" class="table-action">{{ $schedule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="table-empty">Belum ada jadwal.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
