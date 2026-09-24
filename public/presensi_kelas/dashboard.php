<?php

header('Location: ../dashboard');
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

    <title>
        Dashboard Absensi
    </title>


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

</head>


<body>


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

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


                <a href="index.html">

                    <i class="fa-solid fa-house"></i>

                    Beranda

                </a>


                <a
                    href="dashboard.html"
                    class="active"
                >

                    <i class="fa-solid fa-chart-column"></i>

                    Dashboard

                </a>


            </nav>


        </div>


    </header>



    <!-- =====================================================
         DASHBOARD
    ====================================================== -->

    <section class="dashboard-section">


        <div class="container">


            <div class="section-title">


                <span>
                    DASHBOARD
                </span>


                <h2>
                    Data Absensi Mahasiswa
                </h2>


                <p>
                    Lihat data kehadiran mahasiswa.
                </p>


            </div>



            <!-- =================================================
                 STATISTIK
            ================================================== -->

            <div class="statistics">


                <!-- TOTAL ABSENSI -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-list"></i>

                    </div>


                    <div>

                        <span>
                            Total Absensi
                        </span>

                        <strong id="totalAbsensi">
                            0
                        </strong>

                    </div>


                </div>



                <!-- HADIR -->

                <div class="stat-card">


                    <div class="stat-icon hadir-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div>

                        <span>
                            Hadir
                        </span>

                        <strong id="totalHadir">
                            0
                        </strong>

                    </div>


                </div>



                <!-- BELUM HADIR -->

                <div class="stat-card">


                    <div class="stat-icon alpa-icon">

                        <i class="fa-solid fa-user-clock"></i>

                    </div>


                    <div>

                        <span>
                            Belum Hadir
                        </span>

                        <strong id="totalBelumHadir">
                            0
                        </strong>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 FILTER
            ================================================== -->

            <div class="filter-card">


                <!-- SEARCH -->

                <div class="search-box">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Cari nama atau NPM..."
                    >


                </div>



                <!-- STATUS -->

                <select id="statusFilter">


                    <option value="">
                        Semua Status
                    </option>


                    <option value="Hadir">
                        Hadir
                    </option>


                    <option value="Izin">
                        Izin
                    </option>


                    <option value="Sakit">
                        Sakit
                    </option>


                    <option value="Alpa">
                        Alpa
                    </option>


                </select>



                <!-- REFRESH -->

                <button
                    type="button"
                    class="btn-refresh"
                    onclick="loadAttendanceData()"
                >

                    <i class="fa-solid fa-rotate"></i>

                    Refresh

                </button>


            </div>



            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-responsive">


                    <table>


                        <thead>


                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Jam
                                </th>

                                <th>
                                    Nama
                                </th>

                                <th>
                                    NPM
                                </th>

                                <th>
                                    Kelas
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>


                        </thead>



                        <tbody id="attendanceTable">


                            <tr>


                                <td
                                    colspan="7"
                                    class="loading"
                                >

                                    <i class="fa-solid fa-spinner fa-spin"></i>

                                    Memuat data...

                                </td>


                            </tr>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </section>



    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer>


        <div class="container">


            <p>

                &copy; 2026 Absensi Online.
                Semua hak dilindungi.

            </p>


        </div>


    </footer>



    <!-- JAVASCRIPT -->

    <script src="js/script.js"></script>


</body>

</html>