# Absensi Online

Aplikasi absensi mahasiswa berbasis Laravel 12 dengan database **SQLite**. Alur utama sudah direfactor menjadi:

1. Admin mendaftarkan mahasiswa.
2. Admin membuat mata kuliah dan mengaitkan mahasiswa ke mata kuliah.
3. Admin membuat jadwal untuk mata kuliah, kelas, tanggal, dan waktu tertentu.
4. Sistem membuat QR Code khusus jadwal.
5. Mahasiswa memasukkan NPM/nama, memindai QR jadwal, dan otomatis tercatat sebagai **Hadir**.

## Menjalankan aplikasi

```bash
php artisan migrate --seed
php artisan serve
```

Buka:

- `http://127.0.0.1:8000/` — absensi mahasiswa
- `http://127.0.0.1:8000/master-data` — kelola mahasiswa, mata kuliah, dan jadwal
- `http://127.0.0.1:8000/dashboard` — dashboard absensi

Jika memakai XAMPP:

- `http://localhost/presensi/public/`
- `http://localhost/presensi/public/master-data`
- `http://localhost/presensi/public/dashboard`

Document root Apache sebaiknya diarahkan ke folder `C:\xampp\htdocs\presensi\public`. Jika project tidak sengaja berada di document root `htdocs`, `.htaccess` di root project memblokir `.env`, `database`, `storage`, `vendor`, dan file internal lainnya.

Untuk instalasi baru:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Deploy ke Railway

1. Push project ke repository Railway.
2. Pada **Variables** Railway tambahkan:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-APP.up.railway.app
APP_FORCE_HTTPS=true
ASSET_URL=https://YOUR-APP.up.railway.app
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
```

3. Generate key aplikasi dari Railway terminal:

```bash
php artisan key:generate
php artisan migrate --force
```

4. Pastikan domain Railway aktif. `Procfile` sudah tersedia dan aplikasi akan berjalan pada port yang diberikan Railway.

`bootstrap/app.php` sudah memproses forwarded proxy Railway/Traefik, memaksa URL generate menjadi HTTPS, dan mencegah mixed content pada asset lokal. Jangan mengubah `APP_URL` menggunakan `http://` pada production.

## Cara penggunaan

### 1. Master Data

Buka `/master-data`, lalu gunakan navigasi **Data Mahasiswa**, **Mata Kuliah**, dan **Jadwal & QR**:

- Tambahkan mata kuliah.
- Daftarkan mahasiswa dan pilih mata kuliah yang diikuti.
- Buat jadwal dengan mata kuliah, kelas, tanggal, jam, dan ruang. Jadwal pada versi ini bersifat tanggal-spesifik; untuk jadwal mingguan, buat satu baris untuk setiap tanggal.
- Klik **Buka QR** pada jadwal untuk menampilkan QR Code yang dapat dipresentasikan kepada mahasiswa.
- SweetAlert2 digunakan untuk toast, konfirmasi, dan modal scanner QR.

### 2. Absensi mahasiswa

Pada halaman utama:

1. Masukkan nama dan NPM mahasiswa terdaftar.
2. Tekan **Cek** untuk memvalidasi data mahasiswa.
3. Tekan **Scan** dan arahkan kamera ke QR Code jadwal.
4. Jika data lengkap, sistem otomatis mengirim absensi dengan status `Hadir`.

Token QR juga dapat ditempel manual ke kolom token untuk pengujian atau kamera tidak tersedia.

## Data demo

Seeder membuat:

- 6 mahasiswa
- 3 mata kuliah
- 4 jadwal demo pada tanggal hari ini
- Relasi mahasiswa ke mata kuliah

Mahasiswa dan jadwal demo dapat digunakan setelah menjalankan `php artisan db:seed`.

## Database

Migration tersedia pada:

- `database/migrations/2026_09_24_000000_create_attendance_tables.php`
- `database/migrations/2026_09_25_000000_create_schedule_attendance_tables.php`
- `database/migrations/2026_09_25_000001_add_schedule_identity_unique.php`

Tabel utama:

- `students`
- `courses`
- `course_student`
- `schedules`
- `attendances`

Absensi memiliki unique constraint per pasangan `student_id` dan `schedule_id`, sehingga mahasiswa tidak dapat tercatat dua kali pada jadwal yang sama.

SQLite aktif secara default:

```dotenv
DB_CONNECTION=sqlite
```

Database berada di `database/database.sqlite`. Untuk MySQL, ubah konfigurasi `.env` lalu jalankan:

```bash
php artisan config:clear
php artisan migrate --seed
```

## Endpoint

| Method | URL | Keterangan |
|---|---|---|
| GET | `/` | Form absensi dan QR jadwal |
| GET | `/master-data` | Master data |
| GET | `/schedules/{schedule}/qr` | Halaman QR jadwal |
| GET | `/api/students/check?npm=...` | Validasi mahasiswa |
| GET | `/api/schedules/lookup?token=...` | Validasi QR jadwal |
| POST | `/api/attendances/scan` | Catat kehadiran dari QR jadwal |
| GET | `/api/attendances` | Data absensi dan filter dashboard |

## Pengujian

```bash
php artisan test
```

Pemindai kamera memerlukan `localhost` atau HTTPS. Font Awesome, SweetAlert2, `html5-qrcode`, dan `qrcodejs` dimuat melalui CDN. Gunakan URL root `public/`, bukan `public/presensi_kelas/index.php`, karena file tersebut merupakan versi statis lama.

Untuk deployment produksi, tambahkan autentikasi/role untuk halaman Master Data dan dashboard, gunakan HTTPS, dan ubah `APP_DEBUG=false`. Endpoint absensi saat ini sengaja dirancang untuk alur lokal/prototipe dan bukan sebagai pengganti sistem autentikasi akademik.
