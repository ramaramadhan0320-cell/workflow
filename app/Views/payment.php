<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?= isset($user['username']) ? esc($user['username']) : 'User' ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

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
            
            .glass-container {
                overflow: hidden;
            }
        }

        /* Mobile view - allow scrolling */
        @media (max-width: 767px) {
            body {
                min-height: 100vh;
                overflow-y: auto;
            }
            
            main {
                min-height: 100vh !important;
                overflow: visible !important;
            }
            
            .glass-container {
                overflow: visible !important;
            }

            .content-wrapper {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .status-paid { color: #008738; }
        .status-pending { color: #d4a853; }
        .status-unpaid { color: #e74c3c; }

        /* Hide scrollbar but allow scroll */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        .hide-scrollbar::-webkit-scrollbar {
            display: none;  /* Chrome, Safari and Opera */
        }

        /* Old task-scroll class */
        .task-scroll {
            overflow-y: scroll !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
            padding-right: 0 !important;
        }
        
        .task-scroll::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
        }


    </style>
</head>

<body class="h-screen flex flex-col text-white font-light">

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden md:overflow-hidden">
    <div class="glass glass-container rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden md:overflow-hidden shadow-2xl">
        
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('dashboard') ?>" class="flex items-center gap-2 text-white/70 hover:text-white transition group">
                    <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                    <h1 class="text-xl md:text-2xl font-medium">Payment</h1>
                </a>
            </div>
            <span class="text-white/70 text-sm font-mono"><?= date('d/m/Y') ?></span>
        </div>

        

        <div class="flex-1 flex flex-col overflow-y-scroll hide-scrollbar">
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-2">
                    <span class="text-white/40">Total Gaji</span>
                    <span class="font-semibold text-blue-400">IDR <?= number_format((isset($user['gaji_total']) ? $user['gaji_total'] : 0), 0, ',', '.') ?></span>
                </div>

                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-2">
                    <span class="text-white/40">Total Cashbon</span>
                    <span class="font-semibold text-yellow-400">IDR <?= number_format((isset($total_cashbon) ? $total_cashbon : 0), 0, ',', '.') ?></span>
                </div>

                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-2">
                    <span class="text-white/40">Total Bonus</span>
                    <span class="font-semibold text-purple-400">IDR <?= number_format((isset($total_bonus) ? $total_bonus : 0), 0, ',', '.') ?></span>
                </div>

                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-2">
                    <span class="text-white/40">Potongan Izin/Sakit</span>
                    <span class="font-semibold text-red-400">- <?= number_format((isset($total_potongan) ? $total_potongan : 0), 0, ',', '.') ?></span>
                </div>
                
                <div class="mt-6 p-4 bg-white/5 rounded-2xl border border-white/10">
                    <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/50 mb-1">Gaji Bersih</p>
                    <?php 
                        $gaji_total = $user['gaji_total'] ?? 0;
                        $total_bonus = $total_bonus ?? 0;
                        $total_potongan = $total_potongan ?? 0;
                        $total_cashbon = $total_cashbon ?? 0;
                        $gaji_bersih = $gaji_total + $total_bonus - $total_potongan - $total_cashbon;
                    ?>
                    <h2 class="text-2xl font-800 text-green-400">IDR <?= number_format($gaji_bersih, 0, ',', '.') ?></h2>
                </div>
            </div>

            <div class="flex flex-col justify-between mt-6 flex-1">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white/40">Nama</span>
                        <span class="font-semibold text-right"><?= esc((string)(isset($user['username']) ? $user['username'] : 'N/A')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/40">Nomor Rekening</span>
                        <span class="font-semibold text-right"><?= esc((string)(isset($user['nomor_rekening']) ? $user['nomor_rekening'] : '0')) ?> (<?= esc((string)(isset($user['bank_tujuan']) ? $user['bank_tujuan'] : 'N/A')) ?>)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/40">Estimasi Cair</span>
                        <span class="font-semibold text-right text-indigo-300"><?= $withdrawal_date ?? date('d/m/Y') ?></span>
                    </div>
                </div>

                <div class="flex gap-2 mt-8">
                    <button id="slipGajiBtn" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white py-3.5 rounded-2xl text-[10px] font-bold uppercase tracking-widest transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white/5">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        Slip Gaji
                    </button>
                    <a href="<?= base_url('payment/rincian') ?>" class="flex-1 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/50 text-white py-3.5 rounded-2xl text-[10px] font-bold uppercase tracking-widest transition flex items-center justify-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        Rincian
                    </a>
                    <!-- TOMBOL CAIRKAN SEKARANG - DENGAN LOCK -->
                    <?php if ($can_withdraw ?? false): ?>
                        <a href="<?= base_url('payment/slip') ?>" class="flex-[1.2] bg-green-500 hover:bg-green-600 text-white py-3.5 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-green-900/20 active:scale-95 transition flex items-center justify-center gap-2">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                            CAIRKAN SEKARANG
                        </a>
                    <?php else: ?>
                        <button type="button" disabled title="Hanya bisa dicairkan pada tanggal pencairan dan Anda sudah absen" class="flex-[1.2] bg-gray-500 hover:bg-gray-500 text-white py-3.5 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-gray-900/20 transition flex items-center justify-center gap-2 opacity-60 cursor-not-allowed">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            CAIRKAN SEKARANG
                        </button>
                    <?php endif; ?>
                </div>

                <div class="mt-8 flex flex-col flex-1 min-h-0">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Histori Kehadiran</h2>
                        <i data-lucide="clock-rewind" class="w-4 h-4 text-white/30"></i>
                    </div>
                    <div class="hidden md:grid grid-cols-2 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                        <span>Tanggal</span>
                        <span class="text-right">Status</span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <?php foreach(($kehadiran ?? []) as $k): ?>
                            <div class="flex justify-between md:grid md:grid-cols-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2 items-center text-xs">
                                <span class="text-white/70"><?= date('d/m/Y', strtotime($k['tanggal'])) ?></span>
                                <span class="text-right uppercase font-bold tracking-tighter"><?= $k['status'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>



        

    </div>
</main>

<!-- SLIP PREVIEW MODAL -->
<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
</style>



<!-- Modal Preview Slip Gaji - Fullscreen -->
<div id="slipModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="bg-gray-900 w-screen h-screen flex flex-col border border-white/10">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-white/10 shrink-0">
            <h2 class="text-2xl font-bold text-white">Preview Slip Gaji</h2>
            <button onclick="closeSlipModal()" class="text-white/60 hover:text-white transition">
                <i data-lucide="x" class="w-8 h-8"></i>
            </button>
        </div>

        <!-- PDF Display - Full Size -->
        <div class="flex-1 overflow-auto bg-black flex items-center justify-center p-4">
            <embed id="pdfEmbed" type="application/pdf" class="w-full h-full" />
        </div>

        <!-- Footer Controls -->
        <div class="border-t border-white/10 p-6 bg-gray-950 flex justify-between items-center shrink-0">
            <div class="text-white/60 text-sm">
                <span id="pdfStatus">Loading...</span>
            </div>
            <div class="flex gap-3">
                <button onclick="downloadSlipFromModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition flex items-center gap-2 font-semibold">
                    <i data-lucide="download" class="w-5 h-5"></i>
                    Download
                </button>
                <button onclick="closeSlipModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    const slipGajiSent = <?= ($slip_gaji_sent ?? false) ? 'true' : 'false' ?>;
    const slipInAcc = <?= ($slip_in_acc ?? false) ? 'true' : 'false' ?>;
    let currentFilename = null;

    function showToast(type, msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastText').innerText = msg;
        toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
        toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
        setTimeout(() => toast.classList.add('hidden'), 2500);
    }

    function updateSlipGajiBtnState() {
        const btn = document.getElementById('slipGajiBtn');
        if (!slipGajiSent) {
            btn.disabled = true;
            btn.title = 'Anda harus mengkonfirmasi dan mengirim slip gaji terlebih dahulu';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        } else if (slipInAcc) {
            btn.disabled = true;
            btn.title = 'Preview disabled - File sudah di ACC';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            btn.disabled = false;
            btn.title = 'Klik untuk preview slip gaji';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function closeSlipModal() {
        document.getElementById('slipModal').classList.add('hidden');
        document.getElementById('pdfEmbed').src = '';
        currentFilename = null;
    }

    function downloadSlipFromModal() {
        if (!currentFilename) {
            showToast('error', 'Filename tidak tersedia');
            return;
        }
        const downloadUrl = '<?= base_url('payment/download-slip') ?>?filename=' + encodeURIComponent(currentFilename);
        window.location.href = downloadUrl;
        setTimeout(() => showToast('success', 'Download dimulai...'), 500);
    }

    async function openSlipModal() {
        if (!slipGajiSent) {
            showToast('error', 'Konfirmasi slip gaji terlebih dahulu');
            return;
        }

        if (slipInAcc) {
            showToast('error', 'Preview disabled - File sudah di ACC');
            return;
        }

        showToast('success', 'Loading PDF...');
        
        try {
            // Step 1: Get filename dari database
            const dataRes = await fetch('<?= base_url('payment/get-slip-data') ?>');
            const dataJson = await dataRes.json();
            
            if (dataJson.status !== 200 || !dataJson.data.filename) {
                showToast('error', 'Gagal get data slip');
                return;
            }
            
            currentFilename = dataJson.data.filename;
            
            // Step 2: Fetch PDF as base64
            const pdfRes = await fetch('<?= base_url('payment/get-slip-pdf-base64') ?>?filename=' + encodeURIComponent(currentFilename));
            const pdfJson = await pdfRes.json();
            
            if (pdfJson.status !== 200 || !pdfJson.data.base64) {
                showToast('error', 'Gagal load PDF');
                return;
            }
            
            // Step 3: Create data URI dan inject ke embed
            const dataUri = 'data:application/pdf;base64,' + pdfJson.data.base64;
            document.getElementById('pdfEmbed').src = dataUri;
            
            // Buka modal
            document.getElementById('slipModal').classList.remove('hidden');
            document.getElementById('pdfStatus').innerText = 'PDF Loaded - ' + currentFilename;
            showToast('success', 'PDF Loaded!');
            
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Error: ' + error.message);
        }
    }

    document.getElementById('slipGajiBtn').addEventListener('click', openSlipModal);

    document.addEventListener('DOMContentLoaded', function() {
        updateSlipGajiBtnState();
    });

    window.addEventListener('load', function() {
        updateSlipGajiBtnState();
    });
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

