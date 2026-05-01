<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank File</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            background-image: url('<?= base_url("images/bg.jpg") ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow: hidden;
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        #sidebar { transition: transform 0.3s ease-in-out; }

        @media (max-width: 767px) {
            .sidebar-closed { transform: translateX(-100%); }
            .sidebar-open { transform: translateX(0); }
        }

        .file-scroll::-webkit-scrollbar { width: 4px; }
        .file-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>

<?php
// Helper function to format bytes
function formatBytes($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return ['icon' => 'file-text', 'color' => 'text-red-400', 'bg' => 'bg-red-500/20'];
        case 'xlsx':
        case 'xls':
        case 'csv': return ['icon' => 'table', 'color' => 'text-green-400', 'bg' => 'bg-green-500/20'];
        case 'zip':
        case 'rar': return ['icon' => 'archive', 'color' => 'text-orange-400', 'bg' => 'bg-orange-500/20'];
        case 'ai':
        case 'cdr':
        case 'psd':
        case 'eps': return ['icon' => 'palette', 'color' => 'text-purple-400', 'bg' => 'bg-purple-500/20'];
        case 'png':
        case 'jpg':
        case 'jpeg': return ['icon' => 'image', 'color' => 'text-blue-400', 'bg' => 'bg-blue-500/20'];
        default: return ['icon' => 'file', 'color' => 'text-blue-400', 'bg' => 'bg-blue-500/20'];
    }
}
?>

<body class="h-screen flex flex-col md:flex-row text-white font-light">

<div class="md:hidden flex items-center justify-between p-4 glass z-[60]">
    <button id="open-btn" class="p-1"><i data-lucide="menu" class="w-8 h-8"></i></button>
    <img src="/images/logo-4.png" class="w-10 h-10 rounded-full border border-white/40" alt="logo">
    <div class="w-8"></div>
</div>

<nav id="sidebar" class="fixed md:relative inset-y-0 left-0 w-64 glass h-full p-6 flex flex-col justify-between z-[100] sidebar-closed md:transform-none">
    <div class="w-full">
        <div class="flex justify-end md:hidden mb-4"><button id="close-btn"><i data-lucide="x" class="w-8 h-8"></i></button></div>
        <div class="flex justify-center mb-8">
            <img src="/images/logo-4.png" class="w-16 h-16 object-cover rounded-full border border-white/40 shadow-lg" alt="logo">
        </div>
        <div class="space-y-5">
            <a href="/dashboard" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
            <a href="/roles" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="user-check"></i><span>Roles</span></a>
            <a href="/announcement" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="megaphone"></i><span>Announcement</span></a>
            <a href="/bank-file" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="folder"></i><span>Bank File</span></a>
            <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="/payment-management" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="banknote"></i><span>Management Payment</span></a>
            <?php endif; ?>
            <a href="/integration" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="cable"></i><span>Integrasi IoT</span></a>
        </div>
    </div>
    <div class="flex flex-col gap-3">
        <a href="/credit" class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white py-2 rounded-xl transition text-sm border border-white/10">
            <i data-lucide="info" class="w-4 h-4"></i> Credit
        </a>
        <a href="<?= base_url('logout') ?>" class="bg-red-500/80 hover:bg-red-600 text-center py-2 rounded-xl transition text-sm">Logout</a>
    </div>
</nav>

<div id="overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[90] hidden md:hidden"></div>

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden">

    <div class="glass rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden">

        <div class="flex justify-between items-center mb-6 shrink-0">
            <h1 class="text-xl md:text-2xl">Bank File</h1>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <div class="glass rounded-3xl p-4 md:p-5 flex-1 flex flex-col overflow-hidden shadow-lg">
            <div class="flex justify-between items-center mb-3 shrink-0">
                <h2 class="text-lg font-medium">File Management</h2>
                <button onclick="openUploadModal()" class="bg-white/10 hover:bg-white hover:text-gray-900 px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1">
                    <i data-lucide="plus" class="w-3 h-3"></i>
                    Upload
                </button>
            </div>

            <div class="hidden sm:grid grid-cols-4 gap-4 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                <span>Filename</span>
                <span>Size</span>
                <span>Date</span>
                <span class="text-right">Action</span>
            </div>

            <div class="overflow-y-auto file-scroll flex-1 pr-1">
                <?php if (empty($files)): ?>
                    <div class="text-center py-12 text-white/40">
                        <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                        <p class="text-sm">Belum ada file yang diunggah</p>
                    </div>
                <?php else: ?>
                    <?php foreach($files as $f): 
                        $fileInfo = getFileIcon($f['name']);
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['png', 'jpg', 'jpeg']);
                    ?>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2 items-center group">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-10 h-10 <?= $fileInfo['bg'] ?> rounded-lg flex items-center justify-center shrink-0 overflow-hidden">
                                    <?php if ($isImage): ?>
                                        <img src="/bank-file/download/<?= urlencode($f['name']) ?>" class="w-full h-full object-cover" alt="preview">
                                    <?php else: ?>
                                        <i data-lucide="<?= $fileInfo['icon'] ?>" class="w-5 h-5 <?= $fileInfo['color'] ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm font-medium truncate" title="<?= esc($f['name']) ?>"><?= esc($f['name']) ?></span>
                            </div>
                            <span class="hidden sm:block text-white/70 text-xs"><?= formatBytes($f['size']) ?></span>
                            <span class="hidden sm:block text-white/70 text-xs"><?= $f['modified_formatted'] ?></span>
                            <div class="flex gap-2 justify-end flex-wrap sm:flex-nowrap">
                                <a href="/bank-file/download/<?= urlencode($f['name']) ?>" class="download-btn bg-blue-500/50 hover:bg-blue-600 p-2 rounded transition" title="Download">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>
                                <button class="delete-btn bg-red-500/50 hover:bg-red-600 p-2 rounded transition" data-filename="<?= esc($f['name']) ?>" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<!-- Upload Modal -->
<form id="uploadForm" enctype="multipart/form-data">
    <div id="uploadModal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50 transition-all duration-200 opacity-0">
        <div class="bg-white rounded-3xl p-6 md:p-8 w-[90%] max-w-[450px] text-gray-800 relative shadow-2xl transform scale-95 transition-all duration-200">
            <button type="button" onclick="closeUploadModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">✕</button>
            <h2 class="text-xl font-semibold mb-6">Upload Bank File</h2>
            
            <div class="space-y-4 text-sm">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Pilih File</label>
                    <input type="file" name="file" id="fileInput" required 
                        class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500" 
                        accept=".csv,.xlsx,.xls,.pdf,.doc,.docx,.png,.jpg,.jpeg,.cdr,.ai,.psd,.eps,.zip,.rar,.txt">
                    <span class="text-xs text-gray-500 mt-1 block">Format: Dokumen, Foto, Zip, File Editing (CDR, AI, PSD, etc)</span>
                </div>
            </div>

            <div id="uploadProgress" class="hidden mt-4">
                <div class="w-full bg-gray-200 rounded-lg h-2">
                    <div id="progressBar" class="bg-blue-500 h-2 rounded-lg" style="width: 0%"></div>
                </div>
                <span id="progressText" class="text-xs text-gray-500 mt-2">0%</span>
            </div>

            <button id="submitBtn" type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition mt-6">
                Upload File
            </button>
        </div>
    </div>
</form>

<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<script>
    lucide.createIcons();
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const openBtn = document.getElementById('open-btn');
    const closeBtn = document.getElementById('close-btn');

    function toggleMenu() {
        sidebar.classList.toggle('sidebar-closed');
        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('hidden');
    }

    openBtn.addEventListener('click', toggleMenu);
    closeBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);

    function updateLocalDate() {
        const dateEl = document.getElementById('currentDate');
        if (!dateEl) return;
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        dateEl.textContent = `${day}/${month}/${year}`;
    }

    updateLocalDate();
    setInterval(updateLocalDate, 60000);

    // --- Toast/Alert Functions ---
    window.showToast = function(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastText = document.getElementById('toastText');
        toastText.textContent = message;
        toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest ' + 
            (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };

    // --- Upload Modal ---
    window.openUploadModal = function() {
        const modal = document.getElementById('uploadModal');
        document.getElementById('fileInput').value = '';
        document.getElementById('uploadProgress').classList.add('hidden');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.bg-white').classList.remove('scale-95');
        }, 10);
    };

    window.closeUploadModal = function() {
        const modal = document.getElementById('uploadModal');
        modal.classList.add('opacity-0');
        modal.querySelector('.bg-white').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    };

    // Close modal when clicking outside
    document.getElementById('uploadModal').addEventListener('click', function(e) {
        if (e.target === this) closeUploadModal();
    });

    // --- Delete File ---
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const filename = this.dataset.filename;

            if (!confirm(`Apakah Anda yakin ingin menghapus file "${filename}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/bank-file/delete/${encodeURIComponent(filename)}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showToast('File berhasil dihapus!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan: ' + err.message, 'error');
            }
        });
    });

    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        
        if (!file) {
            showToast('Pilih file terlebih dahulu', 'error');
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        uploadProgress.classList.remove('hidden');
        submitBtn.disabled = true;

        let progress = 0;
        const interval = setInterval(() => {
            if (progress < 90) {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressText.textContent = progress + '%';
            }
        }, 100);

        try {
            const formData = new FormData(this);
            const response = await fetch('/bank-file/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            clearInterval(interval);
            progressBar.style.width = '100%';
            progressText.textContent = '100%';

            const data = await response.json();

            if (data.success) {
                showToast('File berhasil diunggah!', 'success');
                setTimeout(() => {
                    closeUploadModal();
                    location.reload();
                }, 1000);
            } else {
                showToast('Error: ' + (data.message || 'Upload gagal'), 'error');
                submitBtn.disabled = false;
                uploadProgress.classList.add('hidden');
            }
        } catch (err) {
            clearInterval(interval);
            showToast('Terjadi kesalahan: ' + err.message, 'error');
            submitBtn.disabled = false;
            uploadProgress.classList.add('hidden');
        }
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
