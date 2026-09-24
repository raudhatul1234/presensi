@extends('layouts.app')

@section('title', 'Absensi Jadwal')

@section('content')
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <span class="hero-label">SISTEM ABSENSI JADWAL</span>
                <h1>
                    Absensi Mahasiswa
                    <span>Cepat & Terukur</span>
                </h1>
                <p>
                    Masukkan data mahasiswa terdaftar, lalu pindai QR Code
                    jadwal untuk mencatat kehadiran secara otomatis.
                </p>
                <div class="hero-actions">
                    <a href="#absensi" class="btn-primary">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                        Scan QR Jadwal
                    </a>
                    <a href="{{ route('master.index') }}" class="btn-secondary">
                        <i class="fa-solid fa-database" aria-hidden="true"></i>
                        Kelola Data
                    </a>
                </div>
            </div>

            <div class="hero-icon" aria-hidden="true">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
    </section>

    <section class="attendance-section" id="absensi">
        <div class="container">
            <div class="section-title">
                <span>FORM ABSENSI JADWAL</span>
                <h2>Catat Kehadiran Mahasiswa</h2>
                <p>QR Jadwal menentukan mata kuliah, kelas, tanggal, dan waktu absensi.</p>
            </div>

            <div class="attendance-card">
                <form
                    id="attendanceForm"
                    action="{{ route('attendance.scan') }}"
                    method="POST"
                    data-scan-url="{{ route('attendance.scan') }}"
                    data-lookup-url="{{ route('students.check') }}"
                    data-schedule-lookup-url="{{ route('schedules.lookup') }}"
                >
                    @csrf
                    <input type="hidden" name="source" value="scanner" id="attendanceSource">

                    <div class="date-time-box">
                        <div class="date-time-item">
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            <div>
                                <small>Tanggal</small>
                                <strong id="currentDate">-</strong>
                            </div>
                        </div>
                        <div class="date-time-item">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            <div>
                                <small>Jam</small>
                                <strong id="currentTime">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nama">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            Nama Lengkap
                        </label>
                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            placeholder="Masukkan nama terdaftar"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="npm">
                            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                            NPM
                        </label>
                        <div class="npm-input-wrapper">
                            <input
                                type="text"
                                id="npm"
                                name="npm"
                                placeholder="Masukkan NPM terdaftar"
                                maxlength="30"
                                pattern="[A-Za-z0-9-]+"
                                autocomplete="off"
                                required
                            >
                            <button type="button" class="btn-scan" id="checkStudentButton">
                                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                Cek
                            </button>
                        </div>
                        <small class="input-help">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            Nama dan kelas akan divalidasi terhadap database.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="kelas">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            Kelas Mahasiswa
                        </label>
                        <input type="text" id="kelas" name="kelas" placeholder="Otomatis dari data mahasiswa" readonly>
                    </div>

                    <div class="form-group">
                        <label for="scheduleTokenInput">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                            Token QR Jadwal
                        </label>
                        <div class="npm-input-wrapper">
                            <input
                                type="text"
                                id="scheduleTokenInput"
                                name="schedule_token"
                                placeholder="Scan QR atau tempel token jadwal"
                                maxlength="100"
                                autocomplete="off"
                                required
                            >
                            <button type="button" class="btn-scan" id="openScannerButton">
                                <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                Scan
                            </button>
                        </div>
                        <small class="input-help">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            QR dibuat dari halaman Master Data setelah jadwal dibuat.
                        </small>
                    </div>

                    <div class="schedule-context" id="scheduleContext" hidden>
                        <div class="schedule-context-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div>
                        <div>
                            <strong id="scheduleContextTitle">Jadwal ditemukan</strong>
                            <span id="scheduleContextDetails">-</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="saveAttendanceButton">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Catat sebagai Hadir
                    </button>

                    <div id="notification" class="notification" role="status" aria-live="polite"></div>
                </form>
            </div>
        </div>
    </section>

    <section class="barcode-section" id="qr-code">
        <div class="container">
            <div class="section-title">
                <span>QR CODE JADWAL</span>
                <h2>Buat QR Jadwal</h2>
                <p>Pilih jadwal aktif untuk membuat QR yang dapat dipindai mahasiswa.</p>
            </div>

            <div class="barcode-card">
                <form id="qrCodeForm" class="barcode-form">
                    <label for="scheduleQrSelect">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        Jadwal Aktif
                    </label>
                    <div class="barcode-input">
                        <select id="scheduleQrSelect" required>
                            <option value="">Pilih Jadwal</option>
                            @foreach ($schedules as $schedule)
                                <option
                                    value="{{ $schedule->id }}"
                                    data-token="{{ $schedule->qr_token }}"
                                    data-title="{{ $schedule->course->code }} — {{ $schedule->course->name }}"
                                    data-details="Kelas {{ $schedule->class_name }} · {{ $schedule->date_label }} · {{ $schedule->time_range }}{{ $schedule->room ? ' · '.$schedule->room : '' }}"
                                >
                                    {{ $schedule->course->code }} · {{ $schedule->class_name }} · {{ $schedule->date_label }} · {{ $schedule->time_range }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-generate" id="generateQrButton" @disabled($schedules->isEmpty())>
                            <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                            Buat QR
                        </button>
                    </div>
                    @if ($schedules->isEmpty())
                        <small class="field-error">Belum ada jadwal aktif. Tambahkan jadwal melalui Master Data.</small>
                    @endif
                </form>

                <div id="qrNotification" class="notification" role="status" aria-live="polite"></div>

                <div class="barcode-result" id="barcodeResult">
                    <div class="barcode-placeholder">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                        <p>QR Code jadwal akan muncul di sini.</p>
                    </div>
                </div>

                <button type="button" class="btn-download-barcode" id="downloadQrButton" hidden>
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    Download QR Code
                </button>
            </div>
        </div>
    </section>

    <div class="scanner-modal qr-token-modal" id="scannerModal" role="dialog" aria-modal="true" aria-labelledby="scannerTitle" aria-describedby="scannerDescription">
        <div class="scanner-box">
            <div class="scanner-header">
                <div>
                    <h3 id="scannerTitle">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                        Modal Token QR Jadwal
                    </h3>
                    <p id="scannerDescription">Arahkan kamera ke QR Code yang ditampilkan pada jadwal.</p>
                </div>
                <button type="button" class="close-scanner" id="closeScannerButton" aria-label="Tutup scanner">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div id="reader"></div>

            <div class="scanner-token-preview">
                <small>Token QR yang dipindai</small>
                <code id="scannerTokenPreview">Menunggu QR...</code>
            </div>

            <div class="scanner-info">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <span>Izinkan akses kamera untuk melakukan pemindaian QR Code jadwal.</span>
            </div>

            <button type="button" class="btn-close-scanner" id="closeScannerBottomButton">
                Tutup Scanner
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
@endpush
