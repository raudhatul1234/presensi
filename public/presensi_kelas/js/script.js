/* =====================================================
   GOOGLE APPS SCRIPT URL
===================================================== */

// Legacy static implementation. The active application uses Laravel endpoints.
const GOOGLE_SCRIPT_URL = "";


/* =====================================================
   VARIABEL SCANNER
===================================================== */

let html5QrCode = null;



/* =====================================================
   TANGGAL DAN JAM
===================================================== */

function updateDateTime() {

    const now = new Date();


    const dateElement =
        document.getElementById(
            "currentDate"
        );


    const timeElement =
        document.getElementById(
            "currentTime"
        );


    if (dateElement) {

        const tanggal =
            String(
                now.getDate()
            ).padStart(2, "0");


        const bulan =
            String(
                now.getMonth() + 1
            ).padStart(2, "0");


        const tahun =
            now.getFullYear();


        dateElement.textContent =
            `${tanggal}/${bulan}/${tahun}`;

    }


    if (timeElement) {

        const jam =
            String(
                now.getHours()
            ).padStart(2, "0");


        const menit =
            String(
                now.getMinutes()
            ).padStart(2, "0");


        const detik =
            String(
                now.getSeconds()
            ).padStart(2, "0");


        timeElement.textContent =
            `${jam}:${menit}:${detik}`;

    }

}


updateDateTime();


setInterval(
    updateDateTime,
    1000
);



/* =====================================================
   NOTIFICATION
===================================================== */

function showNotification(
    message,
    type = "success"
) {

    const notification =
        document.getElementById(
            "notification"
        );


    if (!notification) {
        return;
    }


    notification.textContent =
        message;


    notification.className =
        "notification " + type;


    notification.style.display =
        "block";


    setTimeout(
        function () {

            notification.style.display =
                "none";

        },
        5000
    );

}



/* =====================================================
   CEK MAHASISWA
===================================================== */

async function cekMahasiswa(
    npm,
    nama
) {

    try {

        const url =
            GOOGLE_SCRIPT_URL +
            "?action=cekMahasiswa" +
            "&npm=" +
            encodeURIComponent(npm) +
            "&nama=" +
            encodeURIComponent(nama);


        const response =
            await fetch(url);


        const result =
            await response.json();


        return result;

    }
    catch (error) {

        console.error(
            error
        );


        return {

            valid: false,

            error: true,

            message:
                "Gagal menghubungi database mahasiswa."

        };

    }

}



/* =====================================================
   BUKA SCANNER
===================================================== */

function openScanner() {

    const modal =
        document.getElementById(
            "scannerModal"
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        "active"
    );


    setTimeout(
        startScanner,
        300
    );

}



/* =====================================================
   MULAI SCANNER
===================================================== */

function startScanner() {

    if (
        typeof Html5Qrcode ===
        "undefined"
    ) {

        showNotification(
            "QR Scanner tidak tersedia.",
            "error"
        );

        return;

    }


    const reader =
        document.getElementById(
            "reader"
        );


    if (!reader) {
        return;
    }


    reader.innerHTML = "";


    html5QrCode =
        new Html5Qrcode(
            "reader"
        );


    const config = {

        fps: 10,

        qrbox: {
            width: 250,
            height: 250
        }

    };


    html5QrCode.start(

        {
            facingMode:
                "environment"
        },

        config,

        onScanSuccess,

        function () {}

    ).catch(
        function (error) {

            console.error(
                "Scanner error:",
                error
            );


            showNotification(
                "Kamera tidak dapat digunakan.",
                "error"
            );

        }
    );

}



/* =====================================================
   HASIL SCAN
===================================================== */

async function onScanSuccess(
    decodedText
) {

    const npm =
        decodedText.trim();


    /*
       Isi NPM hasil scan
    */

    const npmField =
        document.getElementById(
            "npm"
        );


    if (npmField) {

        npmField.value =
            npm;

    }


    closeScanner();


    /*
       Cek apakah nama sudah diisi
    */

    const namaField =
        document.getElementById(
            "nama"
        );


    const nama =
        namaField
            ? namaField.value.trim()
            : "";


    if (!nama) {

        showNotification(
            "QR berhasil dibaca. Silakan isi Nama terlebih dahulu.",
            "success"
        );

        return;

    }


    /*
       Cek database
    */

    showNotification(
        "Memeriksa data mahasiswa...",
        "success"
    );


    const result =
        await cekMahasiswa(
            npm,
            nama
        );


    if (!result.valid) {

        showNotification(
            result.message ||
            "NPM dan Nama tidak terdaftar.",
            "error"
        );

        return;

    }


    /*
       Isi kelas otomatis
    */

    const kelasField =
        document.getElementById(
            "kelas"
        );


    if (
        kelasField &&
        result.kelas
    ) {

        kelasField.value =
            result.kelas;

    }


    /*
       Langsung simpan absensi
    */

    await kirimAbsensi(
        result
    );

}



/* =====================================================
   KIRIM ABSENSI
===================================================== */

async function kirimAbsensi(
    mahasiswa
) {

    const kelasField =
        document.getElementById(
            "kelas"
        );


    const kelas =
        kelasField
            ? kelasField.value.trim()
            : mahasiswa.kelas;


    if (
        mahasiswa.kelas &&
        kelas !== mahasiswa.kelas
    ) {

        showNotification(
            "Kelas tidak sesuai dengan data mahasiswa.",
            "error"
        );

        return;

    }


    const now =
        new Date();


    const tanggal =
        String(
            now.getDate()
        ).padStart(2, "0") +
        "/" +
        String(
            now.getMonth() + 1
        ).padStart(2, "0") +
        "/" +
        now.getFullYear();


    const jam =
        String(
            now.getHours()
        ).padStart(2, "0") +
        ":" +
        String(
            now.getMinutes()
        ).padStart(2, "0") +
        ":" +
        String(
            now.getSeconds()
        ).padStart(2, "0");



    /*
       Data yang dikirim
    */

    const data = {

        tanggal:
            tanggal,

        jam:
            jam,

        nama:
            mahasiswa.nama,

        npm:
            mahasiswa.npm,

        kelas:
            mahasiswa.kelas,

        /*
           Otomatis Hadir
        */

        status:
            "Hadir"

    };



    try {

        showNotification(
            "Menyimpan absensi...",
            "success"
        );


        await fetch(
            GOOGLE_SCRIPT_URL,
            {

                method:
                    "POST",

                mode:
                    "no-cors",

                headers: {

                    "Content-Type":
                        "text/plain;charset=utf-8"

                },

                body:
                    JSON.stringify(
                        data
                    )

            }
        );


        showNotification(
            "Absensi berhasil disimpan.",
            "success"
        );


        /*
           Kosongkan form
        */

        const form =
            document.getElementById(
                "attendanceForm"
            );


        if (form) {

            form.reset();

        }


        updateDateTime();

    }
    catch (error) {

        console.error(
            error
        );


        showNotification(
            "Gagal menyimpan absensi.",
            "error"
        );

    }

}



/* =====================================================
   TUTUP SCANNER
===================================================== */

function closeScanner() {

    const modal =
        document.getElementById(
            "scannerModal"
        );


    if (html5QrCode) {

        html5QrCode
            .stop()
            .then(
                function () {

                    html5QrCode.clear();

                    html5QrCode = null;

                }
            )
            .catch(
                function () {

                    html5QrCode = null;

                }
            );

    }


    if (modal) {

        modal.classList.remove(
            "active"
        );

    }

}



/* =====================================================
   TUTUP MODAL JIKA KLIK LUAR
===================================================== */

const scannerModal =
    document.getElementById(
        "scannerModal"
    );


if (scannerModal) {

    scannerModal.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                scannerModal
            ) {

                closeScanner();

            }

        }
    );

}