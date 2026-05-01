<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Bonus & Cashbon - <?= esc($user['username']) ?></title>
    
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
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        /* Hide scrollbar but allow scroll */
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="h-screen flex flex-col text-white font-light">

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden md:overflow-hidden">
    <div class="glass glass-container rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden md:overflow-hidden shadow-2xl">
        
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('payment') ?>" class="flex items-center gap-2 text-white/70 hover:text-white transition group">
                    <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                    <h1 class="text-xl md:text-2xl font-medium">Rincian Bonus & Cashbon</h1>
                </a>
            </div>
            <span class="text-white/70 text-sm font-mono"><?= date('d/m/Y') ?></span>
        </div>

        <div class="flex-1 flex flex-col overflow-y-scroll hide-scrollbar gap-6">
            
            <!-- CASHBON SECTION -->
            <div class="flex flex-col flex-1 min-h-0">
                <div class="flex justify-between items-center mb-4 shrink-0">
                    <h2 class="text-lg font-medium">Cashbon</h2>
                    <span class="text-yellow-400 font-semibold text-sm">IDR <?= number_format($total_cashbon ?? 0, 0, ',', '.') ?></span>
                </div>
                
                <div class="hidden md:grid grid-cols-3 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60 shrink-0">
                    <span>Tanggal</span>
                    <span>User</span>
                    <span class="text-right">Nominal</span>
                </div>
                
                <div class="flex-1 overflow-y-scroll hide-scrollbar border border-white/10 rounded-xl">
                    <?php if (!empty($cashbon)): ?>
                        <?php foreach($cashbon as $c): ?>
                            <div class="flex flex-col md:grid md:grid-cols-3 gap-2 md:gap-0 py-3 border-b border-white/5 px-4 text-xs">
                                <span class="text-blue-300"><?= date('d/m/Y', strtotime($c['tanggal'])) ?></span>
                                <span class="text-white/70"><?= esc($user['username']) ?></span>
                                <span class="text-right font-semibold text-yellow-400">IDR <?= number_format($c['nominal'] ?? 0, 0, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-center h-20 text-white/50">
                            <p>Tidak ada data cashbon</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BONUS SECTION -->
            <div class="flex flex-col flex-1 min-h-0">
                <div class="flex justify-between items-center mb-4 shrink-0">
                    <h2 class="text-lg font-medium">Bonus</h2>
                    <span class="text-purple-400 font-semibold text-sm">IDR <?= number_format($total_bonus ?? 0, 0, ',', '.') ?></span>
                </div>
                
                <div class="hidden md:grid grid-cols-3 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60 shrink-0">
                    <span>Tanggal</span>
                    <span>User</span>
                    <span class="text-right">Nominal</span>
                </div>
                
                <div class="flex-1 overflow-y-scroll hide-scrollbar border border-white/10 rounded-xl">
                    <?php if (!empty($bonus)): ?>
                        <?php foreach($bonus as $b): ?>
                            <div class="flex flex-col md:grid md:grid-cols-3 gap-2 md:gap-0 py-3 border-b border-white/5 px-4 text-xs">
                                <span class="text-blue-300"><?= date('d/m/Y', strtotime($b['tanggal'])) ?></span>
                                <span class="text-white/70"><?= esc($user['username']) ?></span>
                                <span class="text-right font-semibold text-purple-400">IDR <?= number_format($b['nominal'] ?? 0, 0, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-center h-20 text-white/50">
                            <p>Tidak ada data bonus</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- TOTAL FOOTER -->
        <div class="shrink-0 mt-6 p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex justify-between">
                    <span class="text-white/40 text-sm">Total Cashbon</span>
                    <span class="font-semibold text-yellow-400">IDR <?= number_format($total_cashbon ?? 0, 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-white/40 text-sm">Total Bonus</span>
                    <span class="font-semibold text-purple-400">IDR <?= number_format($total_bonus ?? 0, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="flex justify-between mt-3 pt-3 border-t border-white/10">
                <span class="text-white/40 text-sm">Grand Total</span>
                <span class="font-bold text-green-400 text-lg">IDR <?= number_format(($total_cashbon ?? 0) + ($total_bonus ?? 0), 0, ',', '.') ?></span>
            </div>
        </div>



    </div>
</main>

<script>
    lucide.createIcons();
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
                deviceWindowOffset.x = e.clientX - modal.offsetLeft;
                deviceWindowOffset.y = e.clientY - modal.offsetTop;
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                modal.style.left = (e.clientX - deviceWindowOffset.x) + 'px';
                modal.style.top = (e.clientY - deviceWindowOffset.y) + 'px';
                modal.style.right = 'auto';
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
