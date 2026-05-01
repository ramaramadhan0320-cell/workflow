<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - <?= esc($user['username']) ?></title>
    
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

        .status-hadir { color: #008738; }
        .status-sakit { color: #d4a853; }
        .status-alfa { color: #e74c3c; }

        .task-scroll::-webkit-scrollbar { width: 4px; }
        .task-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

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
                    <h1 class="text-xl md:text-2xl font-medium">Absensi</h1>
                </a>
            </div>
            <span class="text-white/70 text-sm font-mono"><?= date('d/m/Y') ?></span>
        </div>

        <!-- Alert Messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="mb-4 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-green-200 flex items-center gap-3 shrink-0">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-200 flex items-center gap-3 shrink-0">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span><?= session()->getFlashdata('error') ?></span>
            </div>
        <?php endif; ?>

        <div class="flex-1 flex flex-col md:flex-row gap-6 overflow-hidden">
            
            <div class="w-full md:w-1/3 flex flex-col gap-4">
                <div class="glass rounded-3xl p-6 flex flex-col items-center shadow-lg border border-white/10">
                    <div class="w-24 h-24 bg-white/10 rounded-2xl flex items-center justify-center mb-4 overflow-hidden border border-white/20 shadow-inner">
                        <!-- Skeleton Loader -->
                        <div id="profileImgSkeleton" class="skeleton w-full h-full rounded-xl"></div>
                        
                        <!-- Actual Image (hidden until loaded) -->
                        <?php if(!empty($user['profile'])): ?>
                            <img id="profileImg" 
                                 class="profile-img w-full h-full" 
                                 data-src="<?= base_url('profile/image/' . $user['profile']) ?>"
                                 alt="Profile">
                        <?php else: ?>
                            <i data-lucide="user" class="w-12 h-12 text-white/40"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-lg font-semibold"><?= esc($user['username']) ?></h2>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 mb-4">Employee Ecosystem</p>
                    
                    <div class="w-full space-y-2">
                        <button onclick="openEditModal()" class="w-full bg-white text-gray-900 py-2 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-gray-200 transition">
                            Edit Profile
                        </button>
                        <button onclick="openPasswordModal()" class="w-full bg-red-500/20 border border-red-500/30 text-red-200 py-2 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-red-500/40 transition">
                            Reset Password
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <?php if (isset($sudah_absen) && $sudah_absen === false): ?>
                        <form action="<?= base_url('absensi/absen') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="hadir">
                            <button type="submit" class="w-full glass rounded-2xl p-4 text-center hover:bg-green-500/20 transition group border border-white/5">
                                <i data-lucide="user-check" class="w-8 h-8 mx-auto mb-2 status-hadir group-hover:scale-110 transition"></i>
                                <span class="text-[10px] uppercase tracking-widest block">Hadir</span>
                            </button>
                        </form>
                        <form action="<?= base_url('absensi/absen') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="sakit">
                            <button type="submit" class="w-full glass rounded-2xl p-4 text-center hover:bg-orange-500/20 transition group border border-white/5">
                                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto mb-2 status-sakit group-hover:scale-110 transition"></i>
                                <span class="text-[10px] uppercase tracking-widest block">Sakit</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="col-span-2 glass rounded-2xl p-6 text-center border border-white/10 opacity-60">
                            <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-white/40"></i>
                            <span class="text-[10px] uppercase tracking-widest block">Sudah Absen</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex-1 flex flex-col gap-6 overflow-hidden">
                <div class="glass rounded-3xl p-6 shadow-lg border border-white/10">
                    <h2 class="text-lg font-medium uppercase tracking-[0.1em] text-white mb-4">Biodata</h2>
                    <div class="grid grid-cols-1 gap-y-3 text-sm">
                        <?php $val = fn($field) => esc($user[$field] ?? '-'); ?>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-white/40">Alamat</span>
                            <span class="font-medium"><?= $val('alamat') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-white/40">Pendidikan</span>
                            <span class="font-medium"><?= $val('pendidikan_terakhir') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-white/40">Tempat Tanggal Lahir</span>
                            <span class="font-medium"><?= $val('tempat_lahir') ?>, <?= !empty($user['tanggal_lahir']) ? date('d/m/Y', strtotime($user['tanggal_lahir'])) : '-' ?></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-white/40">Mulai Kerja</span>
                            <span class="font-medium"><?= $val('tahun_mulai_bekerja') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-white/40">email</span>
                            <span class="font-medium"><?= $val('email') ?></span>
                        </div>
                    </div>
                </div>

                <div class="glass rounded-3xl p-5 flex flex-1 flex-col overflow-hidden border border-white/10">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Histori Kehadiran</h2>
                        <i data-lucide="clock-rewind" class="w-4 h-4 text-white/30"></i>
                    </div>
                    <div class="hidden md:grid grid-cols-2 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                        <span>Tanggal</span>
                        <span class="text-right">Status</span>
                    </div>
                    <div class="overflow-y-auto task-scroll flex-1 pr-1">
                        <?php foreach($kehadiran as $k): ?>
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

<div id="editModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-[32px] w-full max-w-md p-8 text-gray-900 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold uppercase tracking-tight">Edit Profile</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-900 transition">✕</button>
        </div>
        
        <form action="<?= base_url('profile/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-2">
            <?= csrf_field() ?>
            
            <div class="flex flex-col items-center mb-2"> 
                <label class="cursor-pointer group relative">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300 group-hover:border-blue-500 transition">
                        <img id="imgPrev" class="<?= !empty($user['profile']) ? '' : 'hidden' ?> w-full h-full object-cover" 
                             src="<?= !empty($user['profile']) ? base_url('profile/image/' . $user['profile']) : '' ?>">
                        
                        <i id="imgIcon" data-lucide="camera" class="<?= !empty($user['profile']) ? 'hidden' : '' ?> w-5 h-5 text-gray-400"></i>
                    </div>
                    <input type="file" name="profile" class="hidden" onchange="previewProfile(this)" accept="image/*">
                </label>
                <span class="text-[9px] font-bold uppercase text-gray-400 mt-1">Ganti Foto</span>
            </div>
            <div class="space-y-2"> <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Alamat</label>
                    <input type="text" name="alamat" value="<?= esc($user['alamat'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm"> </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="<?= esc($user['tempat_lahir'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tgl Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= esc($user['tanggal_lahir'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Pendidikan Terakhir</label>
                    <input type="text" name="pendidikan_terakhir" value="<?= esc($user['pendidikan_terakhir'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tahun Mulai Kerja</label>
                    <input type="number" name="tahun_mulai_bekerja" value="<?= esc($user['tahun_mulai_bekerja'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Email</label>
                    <input type="email" name="email" value="<?= esc($user['email'] ?? '') ?>" class="w-full border-b-2 border-gray-100 py-1 outline-none focus:border-blue-500 transition text-sm">
                </div>
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white py-3 rounded-2xl font-bold uppercase text-xs tracking-[0.2em] shadow-lg shadow-gray-200 mt-2">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<div id="passwordModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-[32px] w-full max-w-xs p-8 text-gray-900 shadow-2xl">
        <h2 class="text-lg font-bold uppercase mb-4">Reset Password</h2>
        <form action="<?= base_url('profile/reset-password') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400">Password Baru</label>
                <input type="password" name="new_password" required class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-red-500 transition">
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="closePasswordModal()" class="flex-1 py-3 text-xs font-bold uppercase text-gray-400">Batal</button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-xl text-xs font-bold uppercase">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

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

    function previewProfile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPrev').src = e.target.result;
                document.getElementById('imgPrev').classList.remove('hidden');
                document.getElementById('imgIcon').classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Handle form submissions with loading indicator
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn && form.action.includes('profile')) {
            // Show loading state
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>';
            lucide.createIcons();
            
            // Reset after 3 seconds if no redirect happens
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }, 3000);
        }
    });

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
</body>
</html>