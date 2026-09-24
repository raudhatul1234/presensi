@extends('layouts.app')

@section('title', 'QR Jadwal - '.$schedule->course->name)

@section('content')
    <section class="qr-page-section">
        <div class="container">
            <div class="section-title">
                <span>QR CODE JADWAL</span>
                <h2>{{ $schedule->course->code }} — {{ $schedule->course->name }}</h2>
                <p>Mahasiswa memindai QR ini untuk mencatat kehadiran pada jadwal aktif.</p>
            </div>

            <div
                class="schedule-qr-card"
                data-schedule-qr
                data-token="{{ $schedule->qr_token }}"
                data-title="{{ $schedule->course->code }} — {{ $schedule->course->name }}"
                data-details="Kelas {{ $schedule->class_name }} · {{ $schedule->date_label }} · {{ $schedule->time_range }}{{ $schedule->room ? ' · '.$schedule->room : '' }}"
            >
                <div id="scheduleQrCode" class="schedule-qr-code"></div>
                <h3>{{ $schedule->course->name }}</h3>
                <p class="schedule-qr-meta">{{ $schedule->course->code }} · Kelas {{ $schedule->class_name }}</p>
                <p class="schedule-qr-meta">{{ $schedule->date_label }} · {{ $schedule->time_range }}</p>
                @if ($schedule->room)
                    <p class="schedule-qr-meta">Ruang: {{ $schedule->room }}</p>
                @endif
                <div class="schedule-token-box">
                    <small>Token QR</small>
                    <code>{{ $schedule->qr_token }}</code>
                </div>
                <button type="button" class="btn-download-barcode" id="downloadScheduleQrButton">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    Download QR Code
                </button>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
@endpush
