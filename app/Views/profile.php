<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?= esc($user['username']) ?></title>
    
    <!-- Preload critical fonts -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Load Tailwind first (blocking, untuk styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Load Lucide first (blocking, untuk icons) -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
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

        /* Loading skeleton animation */
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .profile-img {
            object-fit: cover;
            display: none;
        }

        .profile-img.loaded {
            display: block;
        }
    </style>
</head>

<body class="h-screen flex flex-col text-white font-light">

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden">
    <div class="glass rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden shadow-2xl">
        
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('dashboard') ?>" class="flex items-center gap-2 text-white/70 hover:text-white transition group">
                    <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                    <h1 class="text-xl md:text-2xl font-medium">Profile</h1>
                </a>
            </div>
            <span class="text-white/70 text-sm font-mono"><?= date('d/m/Y H:i') ?></span>
        </div>

        <!-- Alert Messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="mb-4 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-green-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span><?= session()->getFlashdata('success') ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span><?= session()->getFlashdata('error') ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex-1 flex flex-col md:flex-row gap-6 overflow-hidden">
            
            <div class="w-full md:w-1/3 flex flex-col gap-4">
                <div class="glass rounded-3xl p-6 flex flex-col items-center shadow-lg border border-white/10">
                    <div class="w-32 h-32 bg-white/10 rounded-2xl flex items-center justify-center mb-4 overflow-hidden border border-white/20 shadow-inner">
                        <!-- Skeleton Loader -->
                        <div id="profileImgSkeleton" class="skeleton w-full h-full rounded-xl"></div>
                        
                        <!-- Actual Image (hidden until loaded) -->
                        <?php if(!empty($user['profile'])): ?>
                            <img id="profileImg" 
                                 class="profile-img w-full h-full" 
                                 data-src="<?= base_url('profile/image/' . $user['profile']) ?>"
                                 alt="Profile">
                        <?php else: ?>
                            <i data-lucide="user" class="w-16 h-16 text-white/40"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-xl font-semibold"><?= esc($user['username']) ?></h2>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 mb-6">Employee Ecosystem</p>
                    
                    <div class="w-full space-y-2">
                        <button onclick="openEditModal()" class="w-full bg-white text-gray-900 py-2 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-gray-200 transition">
                            Edit Profile
                        </button>
                        <button onclick="openPasswordModal()" class="w-full bg-red-500/20 border border-red-500/30 text-red-200 py-2 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-red-500/40 transition">
                            Reset Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col gap-6 overflow-hidden">
                <div class="glass rounded-3xl p-6 shadow-lg border border-white/10 overflow-y-auto">
                    <h2 class="text-lg font-medium uppercase tracking-[0.1em] text-white mb-4">Informasi Pribadi</h2>
                    <div class="grid grid-cols-1 gap-y-4 text-sm">
                        <?php $val = fn($field) => esc($user[$field] ?? '-'); ?>
                        
                        <div>
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Username</label>
                            <p class="mt-1 text-white font-medium"><?= $val('username') ?></p>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Email</label>
                            <p class="mt-1 text-white font-medium"><?= $val('email') ?></p>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Alamat</label>
                            <p class="mt-1 text-white font-medium"><?= $val('alamat') ?></p>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Tempat & Tanggal Lahir</label>
                            <p class="mt-1 text-white font-medium">
                                <?= $val('tempat_lahir') ?>, 
                                <?= !empty($user['tanggal_lahir']) ? date('d/m/Y', strtotime($user['tanggal_lahir'])) : '-' ?>
                            </p>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Pendidikan Terakhir</label>
                            <p class="mt-1 text-white font-medium"><?= $val('pendidikan_terakhir') ?></p>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="text-[10px] font-bold uppercase text-white/40 tracking-widest">Tahun Mulai Kerja</label>
                            <p class="mt-1 text-white font-medium"><?= $val('tahun_mulai_bekerja') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Profile Modal -->
<div id="editModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-[32px] w-full max-w-md p-8 text-gray-900 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold uppercase tracking-tight">Edit Profile</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-900 transition">✕</button>
        </div>
        
        <form id="formProfile" method="POST" onsubmit="handleFormSubmit(event)">
            <?= csrf_field() ?>
            
            <div class="flex flex-col items-center mb-4"> 
                <label class="cursor-pointer group relative">
                    <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300 group-hover:border-blue-500 transition">
                        <img id="imgPrev" class="<?= !empty($user['profile']) ? '' : 'hidden' ?> w-full h-full object-cover" 
                             src="<?= !empty($user['profile']) ? base_url('profile/image/' . $user['profile']) : '' ?>">
                        
                        <i id="imgIcon" data-lucide="camera" class="<?= !empty($user['profile']) ? 'hidden' : '' ?> w-6 h-6 text-gray-400"></i>
                    </div>
                    <input type="file" name="profile" class="hidden" onchange="previewProfile(this)" accept="image/*">
                </label>
                <span class="text-[9px] font-bold uppercase text-gray-400 mt-2">Ganti Foto</span>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Alamat</label>
                    <input type="text" name="alamat" value="<?= esc($user['alamat']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm"> 
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="<?= esc($user['tempat_lahir']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tgl Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= esc($user['tanggal_lahir']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Pendidikan Terakhir</label>
                    <input type="text" name="pendidikan_terakhir" value="<?= esc($user['pendidikan_terakhir']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tahun Mulai Kerja</label>
                    <input type="number" name="tahun_mulai_bekerja" value="<?= esc($user['tahun_mulai_bekerja']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Email</label>
                    <input type="email" name="email" value="<?= esc($user['email']) ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white py-3 rounded-2xl font-bold uppercase text-xs tracking-[0.2em] shadow-lg shadow-gray-200 mt-4">Simpan Perubahan</button>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="passwordModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-[32px] w-full max-w-xs p-8 text-gray-900 shadow-2xl">
        <h2 class="text-lg font-bold uppercase mb-6">Reset Password</h2>
        <form action="<?= base_url('profile/reset-password') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Password Baru</label>
                <input type="password" name="new_password" required class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-red-500 transition">
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="closePasswordModal()" class="flex-1 py-3 text-xs font-bold uppercase text-gray-400 hover:text-gray-900 transition">Batal</button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-xl text-xs font-bold uppercase hover:bg-red-700 transition">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    // =========================
    // HANDLE FORM SUBMIT
    // =========================
    async function handleFormSubmit(e) {
        e.preventDefault();

        const form = document.getElementById('formProfile');
        const btn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';

        try {
            let res = await fetch('<?= base_url('profile/update') ?>', {
                method: 'POST',
                body: formData
            });

            let result = await res.json();
            console.log('Response:', result);

            // 🔥 HANDLE SESSION HABIS
            if (result.status === 401) {
                alert('Session habis, silakan login ulang');
                window.location.href = '/login';
                return;
            }

            if (result.status === 200) {
                alert(result.message);
                closeEditModal();

                // Redirect ke absensi
                console.log('Redirecting to /absensi');
                setTimeout(() => {
                    document.location.href = '/absensi';
                }, 100);

            } else {
                alert(result.message);
                btn.disabled = false;
                btn.innerHTML = 'Simpan Perubahan';
            }

        } catch (err) {
            console.error('Error:', err);
            alert('Server error');
            btn.disabled = false;
            btn.innerHTML = 'Simpan Perubahan';
        }
    }

    // =========================
    // MODAL CONTROL
    // =========================
    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function openPasswordModal() {
        document.getElementById('passwordModal').classList.remove('hidden');
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').classList.add('hidden');
    }

    // =========================
    // PREVIEW IMAGE
    // =========================
    function previewProfile(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPrev').src = e.target.result;
                document.getElementById('imgPrev').classList.remove('hidden');
                document.getElementById('imgIcon').classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ===============================
    // LAZY LOAD PROFILE IMAGE
    // ===============================
    // DOMContentLoaded runs BEFORE images load
    document.addEventListener('DOMContentLoaded', function() {
        const profileImg = document.getElementById('profileImg');
        
        if (profileImg && profileImg.dataset.src) {
            // Delay image download by 2000ms (2 detik) untuk let halaman load 100% dulu
            setTimeout(() => {
                const img = new Image();
                
                img.onload = function() {
                    // Image loaded successfully
                    profileImg.src = img.src;
                    profileImg.classList.add('loaded');
                    
                    // Hide skeleton
                    const skeleton = document.getElementById('profileImgSkeleton');
                    if (skeleton) {
                        skeleton.style.display = 'none';
                    }
                };
                
                img.onerror = function() {
                    // Image failed to load
                    const skeleton = document.getElementById('profileImgSkeleton');
                    if (skeleton) {
                        skeleton.innerHTML = '<i data-lucide="image-off" class="w-8 h-8 text-white/40"></i>';
                        // Wait for lucide to be available before calling createIcons
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                };
                
                // Start loading image
                img.src = profileImg.dataset.src;
            }, 2000); // 2 detik delay - biarkan halaman load 100% dulu
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
