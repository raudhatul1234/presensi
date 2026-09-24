<?php

header('Location: ../');
exit;

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Absensi Online</title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <!-- QR SCANNER -->

    <script
        src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js">
    </script>


    <!-- QR CODE GENERATOR -->

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
    </script>

</head>


<body>


    <!-- =====================================
         NAVBAR
    ====================================== -->

    <header class="navbar">

        <div class="container nav-container">

            <a
                href="index.html"
                class="logo"
            >

                <i class="fa-solid fa-calendar-check"></i>

                Absensi Online

            </a>


            <nav>

                <a
                    href="index.html"
                    class="active"
                >

                    <i class="fa-solid fa-house"></i>

                    Beranda

                </a>


                <a
                    href="dashboard.html"
                >

                    <i class="fa-solid fa-chart-column"></i>

                    Dashboard

                </a>

            </nav>

        </div>

    </header>



    <!-- =====================================
         HERO
    ====================================== -->

    <section class="hero">

        <div class="container hero-content">

            <div class="hero-text">

                <span class="hero-label">
                    SISTEM ABSENSI ONLINE
                </span>


                <h1>

                    Absensi Mahasiswa

                    <span>
                        Mudah & Cepat
                    </span>

                </h1>


                <p>

                    Lakukan absensi dengan melakukan
                    scan QR Code yang telah terdaftar.

                </p>


                <a
                    href="#absensi"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-qrcode"></i>

                    Mulai Scan

                </a>

            </div>


            <div class="hero-icon">

                <i class="fa-solid fa-user-check"></i>

            </div>

        </div>

    </section>



    <!-- =====================================
         FORM ABSENSI
    ====================================== -->

    <section
        class="attendance-section"
        id="absensi"
    >

        <div class="container">


            <div class="section-title">

                <span>
                    FORM ABSENSI
                </span>


                <h2>
                    Absensi Mahasiswa
                </h2>


                <p>
                    Isi data mahasiswa kemudian lakukan scan.
                </p>

            </div>



            <div class="attendance-card">


                <form id="attendanceForm">


                    <!-- =================================
                         TANGGAL DAN JAM
                    ================================== -->

                    <div class="date-time-box">


                        <!-- TANGGAL -->

                        <div class="date-time-item">

                            <i
                                class="fa-regular fa-calendar"
                            ></i>


                            <div>

                                <small>
                                    Tanggal
                                </small>


                                <strong
                                    id="currentDate"
                                >
                                    -
                                </strong>

                            </div>

                        </div>



                        <!-- JAM -->

                        <div class="date-time-item">

                            <i
                                class="fa-regular fa-clock"
                            ></i>


                            <div>

                                <small>
                                    Jam
                                </small>


                                <strong
                                    id="currentTime"
                                >
                                    -
                                </strong>

                            </div>

                        </div>

                    </div>



                    <!-- =================================
                         NAMA
                    ================================== -->

                    <div class="form-group">

                        <label for="nama">

                            <i
                                class="fa-solid fa-user"
                            ></i>

                            Nama Lengkap

                        </label>


                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            placeholder="Masukkan nama lengkap"
                            required
                        >

                    </div>



                    <!-- =================================
                         NPM
                    ================================== -->

                    <div class="form-group">

                        <label for="npm">

                            <i
                                class="fa-solid fa-id-card"
                            ></i>

                            NPM

                        </label>


                        <input
                            type="text"
                            id="npm"
                            name="npm"
                            placeholder="Masukkan NPM"
                            required
                        >

                    </div>



                    <!-- =================================
                         KELAS
                    ================================== -->

                    <div class="form-group">

                        <label for="kelas">

                            <i
                                class="fa-solid fa-users"
                            ></i>

                            Kelas

                        </label>


                        <select
                            id="kelas"
                            name="kelas"
                            required
                        >

                            <option value="">
                                Pilih Kelas
                            </option>


                            <option value="TI-1A">
                                TI-1A
                            </option>


                            <option value="TI-1B">
                                TI-1B
                            </option>


                            <option value="TI-2A">
                                TI-2A
                            </option>


                            <option value="TI-2B">
                                TI-2B
                            </option>


                            <option value="TI-3A">
                                TI-3A
                            </option>


                            <option value="TI-3B">
                                TI-3B
                            </option>


                            <option value="TI-4A">
                                TI-4A
                            </option>


                            <option value="TI-4B">
                                TI-4B
                            </option>

                        </select>

                    </div>



                    <!-- =================================
                         TOMBOL SCAN
                    ================================== -->

                    <button
                        type="button"
                        class="btn-submit"
                        onclick="openScanner()"
                    >

                        <i
                            class="fa-solid fa-qrcode"
                        ></i>

                        Scan

                    </button>



                    <!-- NOTIFICATION -->

                    <div
                        id="notification"
                        class="notification"
                    ></div>


                </form>

            </div>

        </div>

    </section>



    <!-- =====================================
         SCANNER MODAL
    ====================================== -->

    <div
        class="scanner-modal"
        id="scannerModal"
    >

        <div class="scanner-box">


            <div class="scanner-header">

                <div>

                    <h3>

                        <i
                            class="fa-solid fa-qrcode"
                        ></i>

                        Scan QR Code

                    </h3>


                    <p>

                        Arahkan kamera ke QR Code NPM.

                    </p>

                </div>


                <button
                    type="button"
                    class="close-scanner"
                    onclick="closeScanner()"
                >

                    <i
                        class="fa-solid fa-xmark"
                    ></i>

                </button>

            </div>



            <!-- TEMPAT SCANNER -->

            <div id="reader"></div>



            <div class="scanner-info">

                <i
                    class="fa-solid fa-camera"
                ></i>


                <span>

                    Izinkan akses kamera
                    untuk melakukan scan.

                </span>

            </div>



            <button
                type="button"
                class="btn-close-scanner"
                onclick="closeScanner()"
            >

                Tutup Scanner

            </button>

        </div>

    </div>



    <!-- =====================================
         FOOTER
    ====================================== -->

    <footer>

        <div class="container">

            <p>

                © 2026 Absensi Online.
                Sistem Absensi Mahasiswa.

            </p>

        </div>

    </footer>



    <!-- JAVASCRIPT -->

    <script
        src="js/script.js"
    ></script>

</body>

</html>