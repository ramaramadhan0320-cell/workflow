<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement</title>
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

        .scroll-custom::-webkit-scrollbar { width: 4px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>

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
            <a href="/announcement" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="megaphone"></i><span>Announcement</span></a>
            <a href="/bank-file" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="folder"></i><span>Bank File</span></a>
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
            <div class="flex flex-col">
                <h1 class="text-xl md:text-2xl font-bold">Announcement</h1>
                <p class="text-white/50 text-xs mt-1">Pusat informasi dan pengumuman sistem</p>
            </div>
            <div class="flex gap-2">
                <?php if ($user['role'] === 'admin'): ?>
                <a href="/announcement/permissions" class="bg-white/10 hover:bg-white hover:text-gray-900 px-3 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    Akses Izin
                </a>
                <?php endif; ?>
                
                <?php if ($can_create): ?>
                <button onclick="openAddModal()" class="bg-blue-500/80 hover:bg-blue-600 px-3 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Buat Baru
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto pr-2 scroll-custom space-y-4">
            <?php if (empty($announcements)): ?>
                <div class="flex flex-col items-center justify-center h-full opacity-30">
                    <i data-lucide="megaphone" class="w-16 h-16 mb-4"></i>
                    <p>Belum ada pengumuman</p>
                </div>
            <?php else: ?>
                <?php foreach($announcements as $a): ?>
                    <div class="glass rounded-2xl p-6 relative border border-white/10 group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-blue-400"><?= esc($a['title']) ?></h3>
                                <div class="flex items-center gap-3 text-[10px] text-white/50 mt-1 uppercase tracking-widest">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($a['profile'])): ?>
                                            <img src="<?= base_url('profile/image/' . $a['profile']) ?>" class="w-5 h-5 rounded-full object-cover border border-white/20" alt="profile">
                                        <?php else: ?>
                                            <div class="w-5 h-5 rounded-full bg-blue-500/30 flex items-center justify-center text-[8px] font-bold text-blue-300 border border-white/10">
                                                <?= strtoupper(substr($a['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <span><?= esc($a['username']) ?></span>
                                    </div>
                                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?= date('d M Y, H:i', strtotime($a['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <?php if ($a['status'] === 'pending'): ?>
                                    <span class="bg-yellow-500/20 text-yellow-400 text-[9px] px-2 py-1 rounded-full border border-yellow-500/30 uppercase font-bold tracking-tighter">Pending Approval</span>
                                <?php elseif ($a['status'] === 'rejected'): ?>
                                    <span class="bg-red-500/20 text-red-400 text-[9px] px-2 py-1 rounded-full border border-red-500/30 uppercase font-bold tracking-tighter">Rejected</span>
                                <?php endif; ?>

                                <?php if ($user['role'] === 'admin'): ?>
                                    <button onclick="confirmDelete(<?= $a['id'] ?>)" class="p-2 bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white rounded-lg transition opacity-0 group-hover:opacity-100">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-sm text-white/80 leading-relaxed whitespace-pre-line">
                            <?= esc($a['content']) ?>
                        </div>

                        <?php if ($user['role'] === 'admin' && $a['status'] === 'pending'): ?>
                            <div class="mt-6 flex gap-3 border-t border-white/5 pt-4">
                                <button onclick="approve(<?= $a['id'] ?>)" class="flex-1 bg-green-500/20 hover:bg-green-500 text-green-400 hover:text-white py-2 rounded-xl text-[10px] font-bold uppercase transition flex items-center justify-center gap-2">
                                    <i data-lucide="check" class="w-4 h-4"></i> Setujui
                                </button>
                                <button onclick="reject(<?= $a['id'] ?>)" class="flex-1 bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white py-2 rounded-xl text-[10px] font-bold uppercase transition flex items-center justify-center gap-2">
                                    <i data-lucide="x" class="w-4 h-4"></i> Tolak
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Create Modal -->
<div id="modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-[200]">
    <div class="bg-white rounded-3xl p-8 w-[90%] max-w-[500px] text-gray-900 relative shadow-2xl scale-95 transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-800">✕</button>
        <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
            <i data-lucide="megaphone" class="text-blue-500"></i>
            Buat Pengumuman
        </h2>
        
        <form id="formAnnouncement" class="space-y-4">
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Judul</label>
                <input type="text" name="title" required class="w-full border rounded-xl px-4 py-3 mt-1 focus:border-blue-500 outline-none transition" placeholder="Masukkan judul pengumuman">
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Isi Pengumuman</label>
                <textarea name="content" required rows="6" class="w-full border rounded-xl px-4 py-3 mt-1 focus:border-blue-500 outline-none transition resize-none" placeholder="Tuliskan isi pengumuman di sini..."></textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-bold text-xs uppercase tracking-widest transition shadow-lg mt-4">
                Publikasikan Sekarang
            </button>
        </form>
    </div>
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

    function openAddModal() {
        document.getElementById('modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
    }

    document.getElementById('formAnnouncement').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        const response = await fetch('/announcement/store', {
            method: 'POST',
            body: formData
        });
        
        const res = await response.json();
        alert(res.message);
        if(res.success) window.location.reload();
    });

    async function approve(id) {
        if(!confirm('Setujui pengumuman ini?')) return;
        const response = await fetch('/announcement/approve/' + id);
        const res = await response.json();
        alert(res.message);
        if(res.success) window.location.reload();
    }

    async function reject(id) {
        if(!confirm('Tolak pengumuman ini?')) return;
        const response = await fetch('/announcement/reject/' + id);
        const res = await response.json();
        alert(res.message);
        if(res.success) window.location.reload();
    }

    async function confirmDelete(id) {
        if(!confirm('Hapus pengumuman ini?')) return;
        const response = await fetch('/announcement/delete/' + id);
        const res = await response.json();
        alert(res.message);
        if(res.success) window.location.reload();
    }
</script>

</body>
</html>
