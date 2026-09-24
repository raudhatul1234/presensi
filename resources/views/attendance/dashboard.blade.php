@extends('layouts.app')

@section('title', 'Dashboard Absensi')

@section('content')
    <section class="dashboard-section">
        <div class="container">
            <div class="section-title">
                <span>DASHBOARD</span>
                <h2>Data Absensi Jadwal</h2>
                <p>Lihat kehadiran mahasiswa berdasarkan mata kuliah, jadwal, kelas, dan tanggal.</p>
            </div>

            <div
                class="dashboard"
                data-dashboard
                data-api-url="{{ route('attendance.data') }}"
            >
                <div class="statistics" aria-live="polite">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-list" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span>Total Absensi</span>
                            <strong id="totalAbsensi">0</strong>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon hadir-icon">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span>Hadir</span>
                            <strong id="totalHadir">0</strong>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon alpa-icon">
                            <i class="fa-solid fa-user-clock" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span>Belum Hadir</span>
                            <strong id="totalBelumHadir">0</strong>
                        </div>
                    </div>
                </div>

                <form class="filter-card" id="attendanceFilters">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="searchInput"
                            name="search"
                            placeholder="Cari nama, NPM, atau mata kuliah..."
                            maxlength="100"
                        >
                    </div>

                    <select id="courseFilter" name="course_id" aria-label="Filter mata kuliah">
                        <option value="">Semua Mata Kuliah</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</option>
                        @endforeach
                    </select>

                    <select id="scheduleFilter" name="schedule_id" aria-label="Filter jadwal">
                        <option value="">Semua Jadwal</option>
                        @foreach ($schedules as $schedule)
                            <option value="{{ $schedule->id }}">{{ $schedule->course->code }} · {{ $schedule->class_name }} · {{ $schedule->date_label }}</option>
                        @endforeach
                    </select>

                    <select id="statusFilter" name="status" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </select>

                    <select id="classFilter" name="class_name" aria-label="Filter kelas">
                        <option value="">Semua Kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class }}">{{ $class }}</option>
                        @endforeach
                    </select>

                    <input type="date" id="dateFilter" name="date" aria-label="Filter tanggal">

                    <button type="button" class="btn-refresh" id="refreshAttendanceButton">
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                        Refresh
                    </button>

                    <button type="button" class="btn-reset" id="resetAttendanceFilters">
                        Reset
                    </button>
                </form>

                <div class="table-card">
                    <div class="table-meta" id="attendanceTableMeta">
                        Memuat data...
                    </div>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Mata Kuliah</th>
                                    <th>Jadwal</th>
                                    <th>Nama</th>
                                    <th>NPM</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTable" aria-busy="true">
                                <tr>
                                    <td colspan="9" class="loading">
                                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                        Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
