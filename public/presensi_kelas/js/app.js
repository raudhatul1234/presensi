(() => {
    'use strict';

    const numberFormatter = new Intl.NumberFormat('id-ID');
    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    });
    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    });

    const notificationTimers = new WeakMap();

    document.addEventListener('DOMContentLoaded', () => {
        initClock();
        initAttendanceForm();
        initSmartScanner();
        initQrGenerator();
        initDashboard();
        initMasterNavigation();
        initFlashAlerts();
        initConfirmForms();
        initStandaloneScheduleQr();
    });

    function initClock() {
        const dateElement = document.getElementById('currentDate');
        const timeElement = document.getElementById('currentTime');

        if (!dateElement && !timeElement) {
            return;
        }

        const update = () => {
            const now = new Date();

            if (dateElement) {
                dateElement.textContent = dateFormatter.format(now);
            }

            if (timeElement) {
                timeElement.textContent = timeFormatter.format(now);
            }
        };

        update();
        window.setInterval(update, 1000);
    }

    function initAttendanceForm() {
        const form = document.getElementById('attendanceForm');

        if (!form) {
            return;
        }

        const notification = document.getElementById('notification');
        const saveButton = document.getElementById('saveAttendanceButton');
        const checkButton = document.getElementById('checkStudentButton');
        const scanButton = document.getElementById('openScannerButton');
        const tokenInput = document.getElementById('scheduleTokenInput');
        const originalSaveContent = saveButton?.innerHTML ?? '';
        let selectedSchedule = null;
        let selectedToken = '';

        form._attendanceState = {
            getSchedule: () => selectedSchedule,
            setSchedule: (schedule) => {
                selectedSchedule = schedule;
                selectedToken = schedule?.token ?? '';
            },
        };

        tokenInput?.addEventListener('input', () => {
            selectedSchedule = null;
            selectedToken = tokenInput.value.trim();
            hideScheduleContext(form);
        });

        checkButton?.addEventListener('click', () => {
            lookupRegisteredStudent(form, notification, checkButton);
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setAttendanceButtonsDisabled(true);

            try {
                const token = tokenInput?.value.trim() ?? '';

                if (!token) {
                    throw new Error('Scan QR jadwal atau masukkan token jadwal terlebih dahulu.');
                }

                if (!selectedSchedule || selectedSchedule.token !== token) {
                    const lookup = await lookupSchedule(form.dataset.scheduleLookupUrl, token);

                    if (!lookup.valid || !lookup.schedule) {
                        throw new Error(lookup.message ?? 'QR jadwal tidak valid.');
                    }

                    selectedSchedule = lookup.schedule;
                    renderScheduleContext(form, selectedSchedule);
                }

                if (tokenInput?.value.trim() !== token) {
                    throw new Error('Token QR berubah. Silakan scan ulang jadwal.');
                }

                const result = await fetchJson(form.dataset.scanUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        npm: form.querySelector('#npm')?.value.trim() ?? '',
                        nama: form.querySelector('#nama')?.value.trim() ?? '',
                        schedule_token: token,
                    }),
                });

                showNotification(
                    notification,
                    result.message ?? 'Absensi berhasil dicatat sebagai hadir.',
                    'success',
                );
                form.reset();
                selectedSchedule = null;
                selectedToken = '';
                hideScheduleContext(form);
                updateClockImmediately();
            } catch (error) {
                showNotification(
                    notification,
                    getErrorMessage(error, 'Gagal mencatat absensi. Silakan coba lagi.'),
                    'error',
                );
            } finally {
                if (saveButton) {
                    saveButton.innerHTML = originalSaveContent;
                }

                setAttendanceButtonsDisabled(false);
            }
        });

        function setAttendanceButtonsDisabled(disabled) {
            form.querySelectorAll('input, select, button').forEach((control) => {
                control.disabled = disabled;
            });

            if (saveButton && disabled) {
                saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Mencatat...';
            }
        }
    }

    async function lookupRegisteredStudent(form, notification, checkButton) {
        const npmInput = form.querySelector('#npm');
        const nameInput = form.querySelector('#nama');

        if (!npmInput || !npmInput.value.trim()) {
            showNotification(notification, 'Masukkan NPM terlebih dahulu.', 'error');
            npmInput?.focus();
            return;
        }

        const originalContent = checkButton?.innerHTML ?? '';
        checkButton && (checkButton.disabled = true);
        checkButton && (checkButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>');

        try {
            const result = await lookupStudent(
                form.dataset.lookupUrl,
                npmInput.value.trim(),
                nameInput?.value ?? '',
            );

            if (result.valid && result.student) {
                fillStudentFields(form, result.student);
                showNotification(notification, 'Data mahasiswa valid dan terdaftar.', 'success');
            }
        } catch (error) {
            showNotification(
                notification,
                getErrorMessage(error, 'Data mahasiswa tidak dapat diverifikasi.'),
                'error',
            );
        } finally {
            if (checkButton) {
                checkButton.disabled = false;
                checkButton.innerHTML = originalContent;
            }
        }
    }

    function initSmartScanner() {
        const form = document.getElementById('attendanceForm');
        const openButton = document.getElementById('openScannerButton');
        const notification = document.getElementById('notification');
        const sourceInput = document.getElementById('attendanceSource');

        if (!form || !openButton) {
            return;
        }

        if (typeof window.Swal === 'undefined') {
            initScanner();
            return;
        }

        const scanner = {
            instance: null,
            handled: false,
            generation: 0,
            startPromise: null,
        };

        openButton.addEventListener('click', () => {
            if (sourceInput) {
                sourceInput.value = 'scanner';
            }

            if (typeof window.Html5Qrcode === 'undefined') {
                showNotification(notification, 'QR Scanner tidak tersedia. Pastikan koneksi internet aktif.', 'error');
                return;
            }

            if (!window.isSecureContext && window.location.hostname !== 'localhost') {
                showNotification(notification, 'Kamera hanya dapat diakses melalui HTTPS atau localhost.', 'error');
                return;
            }

            scanner.handled = false;
            scanner.generation += 1;
            const generation = scanner.generation;

            window.Swal.fire({
                title: 'Modal Token QR Jadwal',
                html: `
                    <div class="swal-scanner-reader-shell">
                        <div id="swalReader" class="swal-scanner-reader">
                            <div class="loading"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Menyiapkan kamera...</div>
                        </div>
                        <div class="swal-scanner-token">
                            <small>Token QR yang dipindai</small>
                            <code id="swalTokenPreview">Menunggu QR...</code>
                        </div>
                        <div class="swal-scanner-info">
                            <i class="fa-solid fa-camera" aria-hidden="true"></i>
                            <span>Arahkan kamera ke QR Code jadwal. Izinkan akses kamera jika diminta.</span>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonText: 'Selesai',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
                customClass: {
                    popup: 'swal-scanner-popup',
                    title: 'swal-title',
                    htmlContainer: 'swal-scanner-content',
                    confirmButton: 'swal-confirm',
                    cancelButton: 'swal-cancel',
                },
                didOpen: () => {
                    const reader = document.getElementById('swalReader');
                    startScanner(reader, generation);
                },
                willClose: () => {
                    void stopScanner();
                },
            });
        });

        async function startScanner(reader, generation) {
            if (!reader || scanner.instance || generation !== scanner.generation) {
                return;
            }

            let instance;
            let startPromise;

            try {
                instance = new window.Html5Qrcode(reader.id);
                scanner.instance = instance;
                startPromise = instance.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1,
                    },
                    (decodedText) => onScanSuccess(decodedText, generation),
                    () => {},
                );
                scanner.startPromise = startPromise;
                await startPromise;
            } catch (error) {
                if (generation === scanner.generation) {
                    console.error('QR Scanner:', error);
                    showNotification(notification, 'Kamera tidak dapat digunakan. Periksa izin kamera dan coba lagi.', 'error');
                    await stopScanner();
                    window.Swal.close();
                }
            } finally {
                if (scanner.startPromise === startPromise) {
                    scanner.startPromise = null;
                }
            }
        }

        async function onScanSuccess(decodedText, generation) {
            if (scanner.handled || generation !== scanner.generation) {
                return;
            }

            scanner.handled = true;
            const token = normalizeScannedToken(decodedText);
            const tokenPreview = document.getElementById('swalTokenPreview');
            const tokenInput = form.querySelector('#scheduleTokenInput');
            const nameInput = form.querySelector('#nama');
            const npmInput = form.querySelector('#npm');

            if (tokenPreview) {
                tokenPreview.textContent = token || 'Token tidak valid';
            }

            await stopScanner();
            if (window.Swal.isVisible()) {
                window.Swal.close();
            }

            if (!tokenInput) {
                return;
            }

            tokenInput.value = token;
            form._attendanceState?.setSchedule(null);

            try {
                const lookup = await lookupSchedule(form.dataset.scheduleLookupUrl, token);

                if (!lookup.valid || !lookup.schedule) {
                    throw new Error(lookup.message ?? 'QR jadwal tidak valid.');
                }

                if (lookup.schedule.is_open === false) {
                    throw new Error('Jadwal ini belum dapat dibuka untuk absensi.');
                }

                form._attendanceState?.setSchedule(lookup.schedule);
                renderScheduleContext(form, lookup.schedule);
                showNotification(notification, 'QR jadwal valid. Melanjutkan proses absensi.', 'success');

                if (!nameInput?.value.trim() && npmInput?.value.trim()) {
                    await lookupRegisteredStudent(form, notification, document.getElementById('checkStudentButton'));
                }

                if (nameInput?.value.trim() && npmInput?.value.trim()) {
                    form.requestSubmit();
                } else {
                    showNotification(notification, 'Lengkapi NPM dan nama terlebih dahulu, lalu tekan Catat sebagai Hadir.', 'info');
                }
            } catch (error) {
                showNotification(notification, getErrorMessage(error, 'QR jadwal tidak dapat diverifikasi.'), 'error');
            }
        }

        async function stopScanner() {
            scanner.generation += 1;
            const instance = scanner.instance;
            const startPromise = scanner.startPromise;
            scanner.instance = null;
            scanner.startPromise = null;

            if (!instance) {
                return;
            }

            if (startPromise) {
                try {
                    await startPromise;
                } catch (error) {
                    console.warn('Scanner startup:', error);
                }
            }

            try {
                if (instance.isScanning) {
                    await instance.stop();
                }
            } catch (error) {
                console.warn('Scanner stop:', error);
            }

            try {
                await instance.clear();
            } catch (error) {
                console.warn('Scanner clear:', error);
            }
        }
    }

    function initScanner() {
        const modal = document.getElementById('scannerModal');
        const reader = document.getElementById('reader');
        const openButton = document.getElementById('openScannerButton');
        const closeButton = document.getElementById('closeScannerButton');
        const closeBottomButton = document.getElementById('closeScannerBottomButton');
        const attendanceForm = document.getElementById('attendanceForm');
        const notification = document.getElementById('notification');
        const tokenPreview = document.getElementById('scannerTokenPreview');
        const sourceInput = document.getElementById('attendanceSource');

        if (!modal || !reader || !attendanceForm) {
            return;
        }

        const scanner = {
            instance: null,
            handled: false,
            generation: 0,
            startPromise: null,
        };

        openButton?.addEventListener('click', () => {
            if (sourceInput) {
                sourceInput.value = 'scanner';
            }

            openScanner();
        });
        closeButton?.addEventListener('click', () => stopScanner());
        closeBottomButton?.addEventListener('click', () => stopScanner());

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                stopScanner();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                stopScanner();
            }
        });

        function openScanner() {
            if (typeof window.Html5Qrcode === 'undefined') {
                showNotification(
                    notification,
                    'QR Scanner tidak tersedia. Pastikan koneksi internet aktif.',
                    'error',
                );
                return;
            }

            if (!window.isSecureContext && window.location.hostname !== 'localhost') {
                showNotification(
                    notification,
                    'Kamera hanya dapat diakses melalui HTTPS atau localhost.',
                    'error',
                );
                return;
            }

            scanner.handled = false;
            scanner.generation += 1;
            if (tokenPreview) {
                tokenPreview.textContent = 'Menunggu QR...';
            }
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            reader.innerHTML = '<div class="loading"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Menyiapkan kamera...</div>';
            window.setTimeout(startScanner, 100);
        }

        async function startScanner() {
            if (!modal.classList.contains('show') || scanner.instance) {
                return;
            }

            const generation = scanner.generation;
            let instance;
            let startPromise;

            try {
                instance = new window.Html5Qrcode('reader');
                scanner.instance = instance;
                startPromise = instance.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1,
                    },
                    (decodedText) => onScanSuccess(decodedText, generation),
                    () => {},
                );
                scanner.startPromise = startPromise;
                await startPromise;
            } catch (error) {
                if (generation === scanner.generation) {
                    console.error('QR Scanner:', error);
                    showNotification(
                        notification,
                        'Kamera tidak dapat digunakan. Periksa izin kamera dan coba lagi.',
                        'error',
                    );
                    await stopScanner();
                }
            } finally {
                if (scanner.startPromise === startPromise) {
                    scanner.startPromise = null;
                }
            }
        }

        async function onScanSuccess(decodedText, generation) {
            if (scanner.handled || generation !== scanner.generation) {
                return;
            }

            scanner.handled = true;
            const token = normalizeScannedToken(decodedText);
            if (tokenPreview) {
                tokenPreview.textContent = token || 'Token tidak valid';
            }
            const tokenInput = attendanceForm.querySelector('#scheduleTokenInput');
            const nameInput = attendanceForm.querySelector('#nama');
            const npmInput = attendanceForm.querySelector('#npm');

            await stopScanner();

            if (!tokenInput) {
                return;
            }

            tokenInput.value = token;
            attendanceForm._attendanceState?.setSchedule(null);

            try {
                const lookup = await lookupSchedule(attendanceForm.dataset.scheduleLookupUrl, token);

                if (!lookup.valid || !lookup.schedule) {
                    throw new Error(lookup.message ?? 'QR jadwal tidak valid.');
                }

                attendanceForm._attendanceState?.setSchedule(lookup.schedule);
                renderScheduleContext(attendanceForm, lookup.schedule);
                showNotification(notification, 'QR jadwal valid. Melanjutkan proses absensi.', 'success');

                if (!nameInput?.value.trim() && npmInput?.value.trim()) {
                    await lookupRegisteredStudent(attendanceForm, notification, document.getElementById('checkStudentButton'));
                }

                if (nameInput?.value.trim() && npmInput?.value.trim()) {
                    attendanceForm.requestSubmit();
                } else {
                    showNotification(
                        notification,
                        'Lengkapi NPM dan nama terlebih dahulu, lalu tekan Catat sebagai Hadir.',
                        'info',
                    );
                }
            } catch (error) {
                showNotification(
                    notification,
                    getErrorMessage(error, 'QR jadwal tidak dapat diverifikasi.'),
                    'error',
                );
            }
        }

        async function stopScanner() {
            scanner.generation += 1;
            modal.classList.remove('show');
            document.body.style.overflow = '';

            const instance = scanner.instance;
            const startPromise = scanner.startPromise;
            scanner.instance = null;
            scanner.startPromise = null;

            if (!instance) {
                reader.replaceChildren();
                return;
            }

            if (startPromise) {
                try {
                    await startPromise;
                } catch (error) {
                    console.warn('Scanner startup:', error);
                }
            }

            try {
                if (instance.isScanning) {
                    await instance.stop();
                }
            } catch (error) {
                console.warn('Scanner stop:', error);
            }

            try {
                await instance.clear();
            } catch (error) {
                console.warn('Scanner clear:', error);
            }

            reader.replaceChildren();
        }
    }

    function normalizeScannedToken(value) {
        let result = String(value ?? '').trim();

        try {
            const url = new URL(result);
            result = url.searchParams.get('token')
                ?? url.searchParams.get('schedule')
                ?? url.searchParams.get('qr')
                ?? result;
        } catch {
            result = result.replace(/^schedule\s*:\s*/i, '').trim();
        }

        return result.slice(0, 100);
    }

    function initQrGenerator() {
        const form = document.getElementById('qrCodeForm');

        if (!form) {
            return;
        }

        const select = document.getElementById('scheduleQrSelect');
        const notification = document.getElementById('qrNotification');
        const resultContainer = document.getElementById('barcodeResult');
        const generateButton = document.getElementById('generateQrButton');
        const downloadButton = document.getElementById('downloadQrButton');
        const originalButtonContent = generateButton?.innerHTML ?? '';
        let currentToken = '';

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            currentToken = '';
            downloadButton.hidden = true;
            showPlaceholder(resultContainer, 'fa-solid fa-spinner fa-spin', 'Menyiapkan QR Code...');

            if (typeof window.QRCode === 'undefined') {
                showPlaceholder(resultContainer, 'fa-solid fa-triangle-exclamation', 'Pembuat QR Code tidak tersedia.');
                showNotification(notification, 'Pembuat QR Code tidak tersedia.', 'error');
                return;
            }

            const option = select?.selectedOptions?.[0];

            if (!option || !option.dataset.token) {
                showPlaceholder(resultContainer, 'fa-solid fa-circle-info', 'Pilih jadwal terlebih dahulu.');
                showNotification(notification, 'Pilih jadwal terlebih dahulu.', 'error');
                return;
            }

            currentToken = option.dataset.token;
            generateButton.disabled = true;
            generateButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Membuat...';

            try {
                renderQrCode(resultContainer, currentToken, {
                    title: option.dataset.title ?? 'QR Jadwal',
                    details: option.dataset.details ?? '',
                });
                downloadButton.hidden = false;
                showNotification(notification, 'QR Code jadwal berhasil dibuat.', 'success');
            } catch (error) {
                currentToken = '';
                showPlaceholder(resultContainer, 'fa-solid fa-triangle-exclamation', 'QR Code gagal dibuat.');
                showNotification(notification, 'QR Code gagal dibuat.', 'error');
            } finally {
                generateButton.disabled = false;
                generateButton.innerHTML = originalButtonContent;
            }
        });

        downloadButton?.addEventListener('click', () => {
            downloadQrFromContainer(resultContainer, currentToken, 'qr-jadwal');
        });
    }

    function showPlaceholder(container, iconClass, message) {
        if (!container) {
            return;
        }

        container.replaceChildren();
        const placeholder = document.createElement('div');
        placeholder.className = 'barcode-placeholder';
        const icon = document.createElement('i');
        icon.className = iconClass;
        icon.setAttribute('aria-hidden', 'true');
        const text = document.createElement('p');
        text.textContent = message;
        placeholder.append(icon, text);
        container.append(placeholder);
    }

    function initMasterNavigation() {
        const root = document.querySelector('[data-master-nav]');

        if (!root) {
            return;
        }

        const links = [...root.querySelectorAll('[data-master-section-link]')];
        const sections = [...root.querySelectorAll('[data-master-section]')];
        const setActive = (id) => {
            links.forEach((link) => {
                const active = link.dataset.masterSectionLink === id;
                link.classList.toggle('active', active);

                if (active) {
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        links.forEach((link) => {
            link.addEventListener('click', () => setActive(link.dataset.masterSectionLink));
        });

        const initialSection = window.location.hash.replace('#', '');
        if (sections.some((section) => section.id === initialSection)) {
            setActive(initialSection);
        }

        if (!('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((first, second) => second.intersectionRatio - first.intersectionRatio)[0];

            if (visible?.target.id) {
                setActive(visible.target.id);
            }
        }, {
            rootMargin: '-100px 0px -55% 0px',
            threshold: [0.1, 0.35, 0.7],
        });

        sections.forEach((section) => observer.observe(section));
    }

    function initFlashAlerts() {
        document.querySelectorAll('[data-flash-message]').forEach((element) => {
            const message = element.dataset.flashMessage;
            if (!message) {
                return;
            }

            showNotification(null, message, element.dataset.flashType ?? 'info', 4500);
            if (typeof window.Swal !== 'undefined') {
                element.closest('.flash-container')?.remove();
            }
        });
    }

    function initConfirmForms() {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();
                const confirmed = await confirmAction({
                    title: form.dataset.confirmTitle || 'Konfirmasi aksi',
                    text: form.dataset.confirm,
                    confirmText: form.dataset.confirmButton || 'Ya, lanjutkan',
                });

                if (confirmed) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        });
    }

    async function confirmAction({ title, text, confirmText = 'Ya, lanjutkan', cancelText = 'Batal' }) {
        if (typeof window.Swal === 'undefined') {
            return window.confirm(`${title}\n\n${text}`);
        }

        const result = await window.Swal.fire({
            title,
            text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusConfirm: false,
            customClass: {
                popup: 'swal-popup',
                title: 'swal-title',
                htmlContainer: 'swal-content',
                confirmButton: 'swal-confirm',
                cancelButton: 'swal-cancel',
            },
        });

        return result.isConfirmed;
    }

    function initStandaloneScheduleQr() {
        const root = document.querySelector('[data-schedule-qr]');

        if (!root) {
            return;
        }

        const token = root.dataset.token;
        const container = root.querySelector('#scheduleQrCode');

        if (typeof window.QRCode === 'undefined' || !container || !token) {
            return;
        }

        renderQrElement(container, token);
        document.getElementById('downloadScheduleQrButton')?.addEventListener('click', () => {
            downloadQrFromContainer(container, token, 'qr-jadwal');
        });
    }

    function renderQrCode(container, token, metadata) {
        if (!container) {
            return;
        }

        container.replaceChildren();
        const content = document.createElement('div');
        content.className = 'barcode-result-content';
        const qrElement = document.createElement('div');
        qrElement.id = 'qrcode';
        const title = document.createElement('strong');
        title.className = 'barcode-title';
        title.textContent = metadata.title ?? 'QR Jadwal';
        const details = document.createElement('div');
        details.className = 'barcode-npm';
        details.textContent = metadata.details ?? '';

        content.append(qrElement, title, details);
        container.append(content);
        renderQrElement(qrElement, token);
    }

    function renderQrElement(element, token) {
        new window.QRCode(element, {
            text: token,
            width: 220,
            height: 220,
            colorDark: '#4b382d',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.H,
        });
    }

    function downloadQrFromContainer(container, token, prefix) {
        const canvas = container?.querySelector('canvas');

        if (!canvas || !token) {
            return;
        }

        const link = document.createElement('a');
        link.download = `${prefix}-${token.slice(0, 12)}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    function initDashboard() {
        const dashboard = document.querySelector('[data-dashboard]');

        if (!dashboard) {
            return;
        }

        const filtersForm = document.getElementById('attendanceFilters');
        const searchInput = document.getElementById('searchInput');
        const courseFilter = document.getElementById('courseFilter');
        const scheduleFilter = document.getElementById('scheduleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const classFilter = document.getElementById('classFilter');
        const dateFilter = document.getElementById('dateFilter');
        const refreshButton = document.getElementById('refreshAttendanceButton');
        const resetButton = document.getElementById('resetAttendanceFilters');
        const tableBody = document.getElementById('attendanceTable');
        const tableMeta = document.getElementById('attendanceTableMeta');
        const totalElement = document.getElementById('totalAbsensi');
        const hadirElement = document.getElementById('totalHadir');
        const belumHadirElement = document.getElementById('totalBelumHadir');
        let requestSequence = 0;
        let searchTimer = null;

        filtersForm?.addEventListener('submit', (event) => event.preventDefault());
        searchInput?.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(loadAttendanceData, 300);
        });
        [courseFilter, scheduleFilter, statusFilter, classFilter, dateFilter].forEach((filter) => {
            filter?.addEventListener('change', loadAttendanceData);
        });
        refreshButton?.addEventListener('click', loadAttendanceData);
        resetButton?.addEventListener('click', () => {
            filtersForm?.reset();
            loadAttendanceData();
        });

        loadAttendanceData();

        async function loadAttendanceData() {
            const sequence = ++requestSequence;
            const url = new URL(dashboard.dataset.apiUrl, window.location.origin);
            const filters = {
                search: searchInput?.value.trim() ?? '',
                course_id: courseFilter?.value ?? '',
                schedule_id: scheduleFilter?.value ?? '',
                status: statusFilter?.value ?? '',
                class_name: classFilter?.value ?? '',
                date: dateFilter?.value ?? '',
            };

            Object.entries(filters).forEach(([key, value]) => {
                if (value) {
                    url.searchParams.set(key, value);
                }
            });

            url.searchParams.set('limit', '500');
            setRefreshLoading(true);
            renderTableMessage('Memuat data...', 'loading', 'fa-solid fa-spinner fa-spin');

            try {
                const result = await fetchJson(url.toString(), { method: 'GET' });

                if (sequence !== requestSequence) {
                    return;
                }

                updateStats(result.stats ?? {});
                renderAttendanceRows(result.attendances ?? []);
                updateTableMeta(result.meta ?? {}, result.attendances ?? []);
            } catch (error) {
                if (sequence === requestSequence) {
                    renderTableMessage(
                        getErrorMessage(error, 'Data absensi gagal dimuat.'),
                        'table-empty table-error',
                        'fa-solid fa-triangle-exclamation',
                    );

                    if (tableMeta) {
                        tableMeta.textContent = 'Gagal memuat data. Silakan coba lagi.';
                    }

                    showNotification(null, getErrorMessage(error, 'Data absensi gagal dimuat.'), 'error');
                }
            } finally {
                if (sequence === requestSequence) {
                    setRefreshLoading(false);
                }
            }
        }

        function updateStats(stats) {
            if (totalElement) {
                totalElement.textContent = numberFormatter.format(Number(stats.total ?? 0));
            }

            if (hadirElement) {
                hadirElement.textContent = numberFormatter.format(Number(stats.hadir ?? 0));
            }

            if (belumHadirElement) {
                belumHadirElement.textContent = numberFormatter.format(Number(stats.belum_hadir ?? 0));
            }
        }

        function renderAttendanceRows(attendances) {
            if (!tableBody) {
                return;
            }

            tableBody.replaceChildren();

            if (!attendances.length) {
                renderTableMessage(
                    'Belum ada data absensi yang sesuai filter.',
                    'table-empty',
                    'fa-solid fa-inbox',
                );
                return;
            }

            const fragment = document.createDocumentFragment();

            attendances.forEach((attendance, index) => {
                const row = document.createElement('tr');
                const values = [
                    String(index + 1),
                    attendance.tanggal ?? '-',
                    attendance.jam ?? '-',
                    `${attendance.course_code ?? '-'} ${attendance.course ?? '-'}`,
                    attendance.schedule ?? '-',
                    attendance.nama ?? '-',
                    attendance.npm ?? '-',
                    attendance.kelas ?? '-',
                ];

                values.forEach((value) => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.append(cell);
                });

                const statusCell = document.createElement('td');
                const statusBadge = document.createElement('span');
                const normalizedStatus = String(attendance.status ?? '').toLowerCase();
                const allowedStatuses = ['hadir', 'izin', 'sakit', 'alpa'];
                statusBadge.className = `status-badge status-${allowedStatuses.includes(normalizedStatus) ? normalizedStatus : 'alpa'}`;
                statusBadge.textContent = attendance.status ?? '-';
                statusCell.append(statusBadge);
                row.append(statusCell);
                fragment.append(row);
            });

            tableBody.append(fragment);
        }

        function renderTableMessage(message, className, iconClass) {
            if (!tableBody) {
                return;
            }

            tableBody.replaceChildren();
            tableBody.setAttribute('aria-busy', className === 'loading' ? 'true' : 'false');

            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 9;
            cell.className = className;
            const icon = document.createElement('i');
            icon.className = iconClass;
            icon.setAttribute('aria-hidden', 'true');
            const text = document.createElement('span');
            text.textContent = ` ${message}`;
            cell.append(icon, text);
            row.append(cell);
            tableBody.append(row);
        }

        function updateTableMeta(meta, attendances) {
            if (!tableMeta) {
                return;
            }

            const filteredTotal = Number(meta.filtered_total ?? attendances.length);
            const returned = Number(meta.returned ?? attendances.length);
            tableMeta.textContent = returned < filteredTotal
                ? `Menampilkan ${numberFormatter.format(returned)} dari ${numberFormatter.format(filteredTotal)} data terbaru.`
                : `Menampilkan ${numberFormatter.format(returned)} data absensi.`;
            tableBody?.setAttribute('aria-busy', 'false');
        }

        function setRefreshLoading(loading) {
            if (!refreshButton) {
                return;
            }

            refreshButton.disabled = loading;
            refreshButton.querySelector('i')?.classList.toggle('fa-spin', loading);
        }
    }

    async function lookupStudent(endpoint, npm, name = '') {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('npm', npm);

        if (name.trim()) {
            url.searchParams.set('nama', name.trim());
        }

        return fetchJson(url.toString(), { method: 'GET' });
    }

    async function lookupSchedule(endpoint, token) {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('token', token);
        return fetchJson(url.toString(), { method: 'GET' });
    }

    function fillStudentFields(form, student) {
        const nameInput = form.querySelector('#nama');
        const npmInput = form.querySelector('#npm');
        const classInput = form.querySelector('#kelas');

        if (nameInput) {
            nameInput.value = student.nama ?? '';
        }

        if (npmInput) {
            npmInput.value = student.npm ?? '';
        }

        if (classInput) {
            classInput.value = student.kelas ?? '';
        }
    }

    function renderScheduleContext(form, schedule) {
        const context = form.querySelector('#scheduleContext');
        const title = form.querySelector('#scheduleContextTitle');
        const details = form.querySelector('#scheduleContextDetails');

        if (!context) {
            return;
        }

        context.hidden = false;

        if (title) {
            title.textContent = `${schedule.course_code ?? ''} — ${schedule.course ?? 'Jadwal'}`;
        }

        if (details) {
            details.textContent = `Kelas ${schedule.class_name ?? '-'} · ${schedule.date ?? '-'} · ${schedule.time ?? '-'}${schedule.room && schedule.room !== '-' ? ` · ${schedule.room}` : ''}`;
        }
    }

    function hideScheduleContext(form) {
        const context = form.querySelector('#scheduleContext');

        if (context) {
            context.hidden = true;
        }
    }

    async function fetchJson(url, options = {}) {
        const headers = new Headers(options.headers ?? {});
        headers.set('Accept', 'application/json');
        headers.set('X-Requested-With', 'XMLHttpRequest');

        if ((options.method ?? 'GET').toUpperCase() !== 'GET') {
            headers.set('Content-Type', 'application/json');
            headers.set('X-CSRF-TOKEN', getCsrfToken());
        }

        const response = await fetch(url, {
            ...options,
            headers,
        });
        const responseText = await response.text();
        let payload = null;

        if (responseText) {
            try {
                payload = JSON.parse(responseText);
            } catch {
                payload = { message: responseText };
            }
        }

        if (!response.ok) {
            const error = new Error(payload?.message ?? `Permintaan gagal (${response.status}).`);
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload ?? {};
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    function getErrorMessage(error, fallback) {
        const payload = error?.payload;

        if (payload?.errors) {
            const firstMessages = Object.values(payload.errors).flat();
            if (firstMessages.length) {
                return String(firstMessages[0]);
            }
        }

        return error?.message || fallback;
    }

    function showNotification(element, message, type = 'info', duration = 5000) {
        if (!message) {
            return;
        }

        if (typeof window.Swal !== 'undefined') {
            const iconMap = {
                success: 'success',
                error: 'error',
                warning: 'warning',
                info: 'info',
            };
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: iconMap[type] ?? 'info',
                title: message,
                showConfirmButton: false,
                timer: Math.min(Math.max(duration, 2500), 8000),
                timerProgressBar: true,
                customClass: {
                    popup: 'swal-toast',
                    title: 'swal-title',
                    icon: 'swal-toast-icon',
                },
            });
            return;
        }

        if (!element) {
            return;
        }

        const previousTimer = notificationTimers.get(element);
        if (previousTimer) {
            window.clearTimeout(previousTimer);
        }

        element.textContent = message;
        element.className = `notification ${type}`;

        const timer = window.setTimeout(() => {
            element.className = 'notification';
        }, duration);

        notificationTimers.set(element, timer);
    }

    function updateClockImmediately() {
        const dateElement = document.getElementById('currentDate');
        const timeElement = document.getElementById('currentTime');

        if (dateElement) {
            dateElement.textContent = dateFormatter.format(new Date());
        }

        if (timeElement) {
            timeElement.textContent = timeFormatter.format(new Date());
        }
    }
})();
