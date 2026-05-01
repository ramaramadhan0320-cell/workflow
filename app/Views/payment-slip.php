<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= esc($user['username']) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('<?= base_url("images/bg.jpg") ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Desktop view - fixed height */
        @media (min-width: 768px) {
            body {
                overflow: hidden;
            }
            
            main {
                height: 100vh;
                overflow: hidden !important;
            }
        }

        /* Mobile view - allow scrolling */
        @media (max-width: 767px) {
            body {
                min-height: 100vh;
                overflow-y: auto;
            }
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .slip-container {
            background: white;
            color: #333;
            padding: 2rem;
            border-radius: 16px;
        }

        .slip-header {
            text-align: center;
            border-bottom: 3px solid #1f2937;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .slip-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 0.5rem 0;
        }

        .slip-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }

        .slip-section {
            margin-bottom: 2rem;
        }

        .slip-section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .slip-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 14px;
        }

        .slip-label {
            color: #6b7280;
            font-weight: 500;
        }

        .slip-value {
            color: #1f2937;
            font-weight: 600;
            text-align: right;
        }

        .slip-total {
            display: flex;
            justify-content: space-between;
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }

        .slip-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
        }

        /* Hide scrollbar while keeping functionality */
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        @media print {
            body {
                background: white;
                overflow: visible;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>

<body class="h-screen flex flex-col text-white font-light">

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden">
    <div class="glass rounded-[32px] p-5 md:p-8 flex flex-col shadow-2xl h-full overflow-hidden">
        
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('payment') ?>" class="flex items-center gap-2 text-white/70 hover:text-white transition group">
                    <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                    <h1 class="text-xl md:text-2xl font-medium">Slip Gaji</h1>
                </a>
            </div>
            <span class="text-white/70 text-sm font-mono"><?= date('d/m/Y') ?></span>
        </div>

        <!-- SLIP GAJI CONTENT -->
        <div id="slipContent" class="flex-1 overflow-y-auto mb-6 min-h-0 hide-scrollbar">
            <div class="slip-container">
                <!-- Header -->
                <div class="slip-header">
                    <h2 class="slip-title">SLIP GAJI KARYAWAN</h2>
                    <p class="slip-subtitle">Periode: <?= date('F Y') ?></p>
                </div>

                <!-- Data Karyawan -->
                <div class="slip-section">
                    <div class="slip-section-title">Data Karyawan</div>
                    <div class="slip-row">
                        <span class="slip-label">Nama</span>
                        <span class="slip-value"><?= esc($user['username'] ?? 'N/A') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Email</span>
                        <span class="slip-value text-sm"><?= esc($user['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">ID Karyawan</span>
                        <span class="slip-value"><?= esc($user['id'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <!-- Rincian Gaji -->
                <div class="slip-section">
                    <div class="slip-section-title">Rincian Gaji</div>
                    <div class="slip-row">
                        <span class="slip-label">Gaji Pokok</span>
                        <span class="slip-value">IDR <?= number_format($user['gaji_total'] ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Bonus</span>
                        <span class="slip-value text-purple-600">+ IDR <?= number_format($total_bonus ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Cashbon</span>
                        <span class="slip-value text-yellow-600">- IDR <?= number_format($total_cashbon ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Potongan (Izin/Sakit)</span>
                        <span class="slip-value text-red-600">- IDR <?= number_format($total_potongan ?? 0, 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Total Gaji Bersih -->
                <?php 
                    $gaji_total = $user['gaji_total'] ?? 0;
                    $gaji_bersih = $gaji_total + ($total_bonus ?? 0) - ($total_potongan ?? 0) - ($total_cashbon ?? 0);
                ?>
                <div class="slip-section">
                    <div class="slip-total">
                        <span>GAJI BERSIH</span>
                        <span>IDR <?= number_format($gaji_bersih, 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Informasi Pencairan -->
                <div class="slip-section">
                    <div class="slip-section-title">Informasi Pencairan</div>
                    <div class="slip-row">
                        <span class="slip-label">Bank Tujuan</span>
                        <span class="slip-value"><?= esc($user['bank_tujuan'] ?? 'N/A') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Nomor Rekening</span>
                        <span class="slip-value"><?= esc($user['nomor_rekening'] ?? 'N/A') ?></span>
                    </div>
                    <div class="slip-row">
                        <span class="slip-label">Tanggal Pencairan</span>
                        <span class="slip-value"><?= esc($withdrawal_date ?? date('d/m/Y')) ?></span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="slip-footer">
                    <p>Slip ini dibuat secara otomatis oleh sistem dan berlaku sebagai bukti penggajian.</p>
                    <p>Tanggal Cetak: <?= $created_at ?? date('d/m/Y H:i:s') ?></p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div id="actionButtons" class="action-buttons flex flex-col gap-2 shrink-0">
            <?php if (!($slip_sent ?? false)): ?>
                <!-- Tombol Konfirmasi (sebelum dikirim) -->
                <button id="confirmBtn" class="flex-[1.5] bg-green-500 hover:bg-green-600 text-white py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-green-900/20 transition active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    Konfirmasi Slip Gaji
                </button>
            <?php else: ?>
                <!-- Setelah dikirim - Download section -->
                <div class="flex flex-col gap-2">
                    <button id="downloadBtn" class="flex-[1.5] bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-900/20 transition active:scale-95 flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Download Slip
                    </button>
                    <div class="text-center text-white/70 text-[9px]">
                        <p>✓ Slip sudah dikirim ke cloud</p>
                        <p class="text-white/50">File: <?= esc($slip_filename ?? '') ?></p>
                        <?php if ($slip_sent_at): ?>
                            <p class="text-white/50">Tanggal: <?= date('d/m/Y H:i', strtotime($slip_sent_at)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- MODAL KONFIRMASI -->
<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-[24px] w-full max-w-md p-8 text-gray-900 shadow-2xl">
        <div class="mb-6">
            <h2 class="text-xl font-bold mb-4">Konfirmasi Slip Gaji</h2>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-gray-700 space-y-2">
                <p class="font-semibold text-yellow-900">⚠️ Perhatian:</p>
                <p>Segala data pribadi yang sudah anda masukan telah dinyatakan benar. Dengan mencentang opsi ini maka, mekanisme pencairan gaji akan dijalankan.</p>
                <p class="font-semibold text-red-600">Kesalahan pada data ada di luar tanggung jawab kami sebagai admin.</p>
            </div>
        </div>

        <div class="mb-6">
            <label class="flex items-start gap-3 cursor-pointer group">
                <input type="checkbox" id="confirmCheckbox" class="w-5 h-5 mt-0.5 rounded border-gray-300 text-green-500 focus:ring-2 focus:ring-green-500">
                <span class="text-sm text-gray-700 group-hover:text-gray-900">
                    Saya telah mengisi data dengan benar
                </span>
            </label>
        </div>

        <div class="flex gap-2">
            <button onclick="closeConfirmModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 py-2 rounded-lg text-sm font-bold transition">
                Batal
            </button>
            <button id="submitConfirmBtn" onclick="submitConfirmation()" disabled class="flex-1 bg-green-500 hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-2 rounded-lg text-sm font-bold transition">
                Konfirmasi & Kirim
            </button>
        </div>
    </div>
</div>

<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<script>
    lucide.createIcons();
    let pdfFile = null;

    function showToast(type, msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastText').innerText = msg;
        toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
        toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    // Open confirm modal
    const confirmBtn = document.getElementById('confirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            document.getElementById('confirmModal').classList.remove('hidden');
        });
    }

    // Download slip
    const downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            downloadPDF();
        });
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        document.getElementById('confirmCheckbox').checked = false;
        document.getElementById('submitConfirmBtn').disabled = true;
    }

    // Enable submit button when checkbox is checked
    document.getElementById('confirmCheckbox').addEventListener('change', function() {
        document.getElementById('submitConfirmBtn').disabled = !this.checked;
    });

    // Generate and send PDF
    function submitConfirmation() {
        const submitBtn = document.getElementById('submitConfirmBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Mengirim...';
        lucide.createIcons();

        // Generate PDF
        const element = document.getElementById('slipContent');
        const opt = {
            margin: 10,
            filename: 'SLIP_GAJI_<?= strtoupper($user['username']) ?>_<?= date('Ymd_His') ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };

        html2pdf().set(opt).from(element).outputPdf('blob').then(pdfBlob => {
            console.log('PDF generated as blob, size:', pdfBlob.size, 'bytes');
            
            // Validate PDF size
            if (!pdfBlob || pdfBlob.size < 100) {
                showToast('error', 'PDF tidak valid atau terlalu kecil');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Konfirmasi & Kirim';
                lucide.createIcons();
                return;
            }
            
            // Convert blob to base64
            const reader = new FileReader();
            reader.onload = function() {
                const pdfBase64 = reader.result; // data:application/pdf;base64,...
                
                console.log('PDF converted to base64, size:', pdfBase64.length, 'chars');
                console.log('PDF header:', pdfBase64.substring(0, 100));
                
                // Send to server using JSON
                console.log('Sending PDF to server...');
                
                fetch('<?= base_url('payment/send-slip') ?>', {
                    method: 'POST',
                    body: JSON.stringify({
                        pdfData: pdfBase64
                    }),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.status === 200) {
                        showToast('success', 'Slip gaji berhasil dikirim ke cloud');
                        closeConfirmModal();
                        
                        // Change button to download
                        changeToDownloadButton();
                        
                        setTimeout(() => {
                            window.location.href = '<?= base_url('payment') ?>';
                        }, 3000);
                    } else {
                        showToast('error', data.message || 'Gagal mengirim slip gaji');
                        console.error('Error detail:', data.debug);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Konfirmasi & Kirim';
                        lucide.createIcons();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showToast('error', 'Error: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Konfirmasi & Kirim';
                    lucide.createIcons();
                });
            };
            
            reader.onerror = function() {
                console.error('FileReader error:', reader.error);
                showToast('error', 'Gagal membaca PDF blob');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Konfirmasi & Kirim';
                lucide.createIcons();
            };
            
            reader.readAsDataURL(pdfBlob);
            
        }).catch(error => {
            console.error('PDF generation error:', error);
            showToast('error', 'Gagal membuat PDF: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Konfirmasi & Kirim';
            lucide.createIcons();
        });
    }

    function changeToDownloadButton() {
        const confirmBtn = document.getElementById('confirmBtn');
        confirmBtn.innerHTML = '<i data-lucide="download" class="w-4 h-4"></i> Download Slip';
        confirmBtn.className = 'flex-[1.5] bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-900/20 transition active:scale-95 flex items-center justify-center gap-2';
        
        // Change click handler
        confirmBtn.onclick = downloadPDF;
    }

    function downloadPDF() {
        const element = document.getElementById('slipContent');
        const opt = {
            margin: 10,
            filename: 'SLIP_GAJI_<?= strtoupper($user['username']) ?>_<?= date('Ymd_His') ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };
        html2pdf().set(opt).from(element).save();
        showToast('success', 'PDF berhasil diunduh');
    }
</script>

    <style>
        .floating-restore-button {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.95);
            color: white;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.3);
            cursor: pointer;
            z-index: 99999;
        }
        .floating-restore-button.hidden {
            display: none;
        }
    </style>
    <div id="restoreDeviceButton" class="floating-restore-button hidden" onclick="restoreIntegrationWindow()" title="Kembali ke Integrasi IoT">
        <i data-lucide="maximize-2" class="w-5 h-5"></i>
    </div>
    <script>
        function syncRestoreButton() {
            const state = localStorage.getItem('iotWindowState');
            if (!state) return;
            const btn = document.getElementById('restoreDeviceButton');
            if (!btn) return;
            btn.classList.remove('hidden');
            btn.title = 'Kembali ke Integrasi IoT';
        }

        function restoreIntegrationWindow() {
            const state = localStorage.getItem('iotWindowState');
            const url = localStorage.getItem('iotWindowUrl');
            const ip = localStorage.getItem('iotWindowIp');

            if (!state || !url || !ip) {
                window.location.href = '/integration';
                return;
            }

            document.getElementById('deviceIpOverlayLabel').textContent = ip;
            document.getElementById('deviceStatus').textContent = state === 'minimized' ? 'Minimized' : 'Membuka halaman...';
            document.getElementById('deviceFrame').src = url;
            document.getElementById('streamModal').classList.remove('hidden');
            document.getElementById('restoreDeviceButton').classList.add('hidden');
            document.body.style.overflow = 'hidden';
            localStorage.setItem('iotWindowState', 'open');
            localStorage.setItem('iotWindowUrl', url);
            localStorage.setItem('iotWindowIp', ip);
        }

        document.addEventListener('DOMContentLoaded', function() {
            syncRestoreButton();
            if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') lucide.createIcons();
        });
    </script>

    <style>
        .stream-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            min-height: 360px;
        }

        .stream-video {
            width: 100%;
            height: 100%;
            display: block;
            border: none;
        }

        .stream-overlay {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(15, 23, 42, 0.88);
            color: white;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            z-index: 20;
        }

        .overlay-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .overlay-title {
            font-size: 14px;
            font-weight: 700;
        }

        .floating-restore-button {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.95);
            color: white;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.3);
            cursor: pointer;
            z-index: 99999;
        }

        .floating-restore-button.hidden {
            display: none;
        }
    </style>

    <div id="streamModal" class="hidden fixed z-[200] inset-4 md:inset-8 lg:inset-16" style="max-width: 900px; max-height: 600px; margin: auto;">
        <div class="bg-white rounded-3xl shadow-2xl w-full h-full overflow-hidden border-4 border-blue-500">
            <div class="stream-container h-full" id="deviceWindowHeader">
                <div class="stream-overlay">
                    <div class="overlay-text">
                        <span id="deviceIpOverlayLabel" class="overlay-title">-</span>
                        <span id="deviceStatus">Menunggu koneksi...</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="minimizeDeviceWindow()" class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition" title="Minimize">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <button onclick="closeDeviceModal()" class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition" title="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <iframe id="deviceFrame" class="stream-video" sandbox="allow-scripts allow-same-origin allow-forms" title="Device Page"></iframe>
                <input type="hidden" id="currentStreamPath" value="/stream">
                <input type="hidden" id="deviceIpInput" value="">
            </div>
        </div>
    </div>

    <script>
        let isDeviceWindowMinimized = false;
        let deviceWindowOffset = { x: 0, y: 0 };
        let isDragging = false;

        function saveDeviceWindowState(state) {
            localStorage.setItem('iotWindowState', state);
        }

        function saveDeviceWindowInfo(ip, url) {
            localStorage.setItem('iotWindowIp', ip);
            localStorage.setItem('iotWindowUrl', url);
        }

        function openDeviceModal() {
            const modal = document.getElementById('streamModal');
            modal.classList.remove('hidden');
            document.getElementById('restoreDeviceButton').classList.add('hidden');
            document.body.style.overflow = 'hidden';
            saveDeviceWindowState('open');
        }

        function closeDeviceModal() {
            const modal = document.getElementById('streamModal');
            modal.classList.add('hidden');
            document.getElementById('restoreDeviceButton').classList.add('hidden');
            document.body.style.overflow = '';
            const frame = document.getElementById('deviceFrame');
            frame.src = 'about:blank';
            document.getElementById('deviceStatus').textContent = 'Menunggu koneksi...';
            localStorage.removeItem('iotWindowState');
            localStorage.removeItem('iotWindowUrl');
            localStorage.removeItem('iotWindowIp');
        }

        function minimizeDeviceWindow() {
            document.getElementById('streamModal').classList.add('hidden');
            document.getElementById('restoreDeviceButton').classList.remove('hidden');
            document.body.style.overflow = '';
            isDeviceWindowMinimized = true;
            saveDeviceWindowState('minimized');
        }

        function restoreDeviceWindow() {
            document.getElementById('streamModal').classList.remove('hidden');
            document.getElementById('restoreDeviceButton').classList.add('hidden');
            document.body.style.overflow = 'hidden';
            isDeviceWindowMinimized = false;
            saveDeviceWindowState('open');
        }

        function restoreIntegrationWindow() {
            const state = localStorage.getItem('iotWindowState');
            const url = localStorage.getItem('iotWindowUrl');
            const ip = localStorage.getItem('iotWindowIp');

            if (!state || !url || !ip) {
                window.location.href = '/integration';
                return;
            }

            document.getElementById('deviceIpOverlayLabel').textContent = ip;
            document.getElementById('deviceStatus').textContent = state === 'minimized' ? 'Minimized' : 'Membuka halaman...';
            document.getElementById('deviceFrame').src = url;
            openDeviceModal();
            saveDeviceWindowInfo(ip, url);
        }

        function setupWindowDrag() {
            const header = document.getElementById('deviceWindowHeader');
            const modal = document.getElementById('streamModal');
            if (!header || !modal) return;

            header.addEventListener('mousedown', function(e) {
                if (e.target.closest('button')) return;
                isDragging = true;
                const rect = modal.getBoundingClientRect();
                deviceWindowOffset.x = e.clientX - rect.left;
                deviceWindowOffset.y = e.clientY - rect.top;
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                const newLeft = e.clientX - deviceWindowOffset.x;
                const newTop = e.clientY - deviceWindowOffset.y;
                
                // Keep modal within viewport bounds
                const maxLeft = window.innerWidth - modal.offsetWidth;
                const maxTop = window.innerHeight - modal.offsetHeight;
                
                modal.style.left = Math.max(0, Math.min(newLeft, maxLeft)) + 'px';
                modal.style.top = Math.max(0, Math.min(newTop, maxTop)) + 'px';
                modal.style.right = 'auto';
                modal.style.transform = 'none';
            });

            document.addEventListener('mouseup', function() {
                isDragging = false;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            setupWindowDrag();
            if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') lucide.createIcons();
        });
    </script>
</body>
</html>
