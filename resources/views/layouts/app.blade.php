<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem absensi mahasiswa berbasis QR Code jadwal, mata kuliah, dan data kehadiran.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Absensi Online')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('presensi_kelas/css/laravel.css') }}">
    <link rel="stylesheet" href="{{ asset('presensi_kelas/css/redesign.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.all.min.js" integrity="sha384-4iKKLMQ8+Zwi0u1Y3xyGlAGB9J3BHNjNaIfoTnxnXWY/ys2Tz0c2NxYhoWrqomTQ" crossorigin="anonymous"></script>
</head>
<body>
    <header class="navbar">
        <div class="container nav-container">
            <a href="{{ route('home') }}" class="logo">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                Absensi Online
            </a>

            <nav aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    Beranda
                </a>
                <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                    Dashboard
                </a>
                <a href="{{ route('master.index') }}" class="{{ request()->routeIs('master.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-database" aria-hidden="true"></i>
                    Master Data
                </a>
            </nav>
        </div>
    </header>

    <main>
        @if (session('success'))
            <div class="container flash-container">
                <div class="flash-message success" role="status" data-flash-type="success" data-flash-message="{{ session('success') }}">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="container flash-container">
                <div class="flash-message error" role="alert" data-flash-type="error" data-flash-message="Periksa kembali data yang Anda masukkan.">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    Periksa kembali data yang Anda masukkan.
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{ now()->year }} Absensi Online. Sistem Absensi Mahasiswa.</p>
        </div>
    </footer>

    @stack('scripts')
    <script src="{{ asset('presensi_kelas/js/app.js') }}" defer></script>
</body>
</html>
