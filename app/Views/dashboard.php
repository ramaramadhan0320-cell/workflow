<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            background-image: url('<?= base_url("images/bg.jpg") ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow: hidden; /* Matikan scroll seluruh bodi */
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

        .status-pending { color: #d4a853; }
        .status-payment { color: #e74c3c; }
        .status-process { color: #e67e22; }
        .status-finishing { color: #008738; }
        .status-done { color: #005f9e; }

        /* Custom Scrollbar kecil untuk Task List */
        .task-scroll::-webkit-scrollbar { width: 4px; }
        .task-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>

<body class="h-screen flex flex-col md:flex-row text-white font-light">

<div class="md:hidden flex items-center justify-between p-4 glass z-[60]">
    <button id="open-btn" class="p-1"><i data-lucide="menu" class="w-8 h-8"></i></button>
    <img src="<?= base_url("images/logo-4.png") ?>" class="w-10 h-10 rounded-full border border-white/40" alt="logo">
    <div class="w-8"></div>
</div>

<nav id="sidebar" class="fixed md:relative inset-y-0 left-0 w-64 glass h-full p-6 flex flex-col justify-between z-[100] sidebar-closed md:transform-none">
    <div class="w-full">
        <div class="flex justify-end md:hidden mb-4"><button id="close-btn"><i data-lucide="x" class="w-8 h-8"></i></button></div>
        <div class="flex justify-center mb-8">
            <img src="<?= base_url("images/logo-4.png") ?>" class="w-16 h-16 object-cover rounded-full border border-white/40 shadow-lg" alt="logo">
        </div>
        <div class="space-y-5">
            <a href="#" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
            <a href="/roles" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="user-check"></i><span>Roles</span></a>
            <a href="/announcement" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="megaphone"></i><span>Announcement</span></a>
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
            <h1 class="text-xl md:text-2xl">Dashboard</h1>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <div class="glass rounded-3xl p-4 md:p-5 mb-8 flex flex-col h-[65%] md:h-[70%] overflow-hidden shadow-lg">
            <div class="flex justify-between items-center mb-3 shrink-0">
                <h2 class="text-lg font-medium">Task List</h2>
                <a href="/detail" class="bg-white/10 hover:bg-white hover:text-gray-900 px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    Detail
                </a>
            </div>

            <div class="hidden sm:grid grid-cols-4 gap-4 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                <span>Task</span>
                <span>Consumer</span>
                <span>Status</span>
                <span class="text-right">Date</span>
            </div>

            <div class="overflow-y-auto task-scroll flex-1 pr-1">
                <?php foreach($tasks as $t): ?>
                    <?php
                        $statusClass = match(strtolower($t['status'])) {
                            'pending' => 'status-pending',
                            'payment pending' => 'status-payment',
                            'process' => 'status-process',
                            'finishing' => 'status-finishing',
                            'done' => 'status-done',
                            default => 'text-white'
                        };
                    ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium"><?= $t['task_name'] ?></span>
                            <span class="text-white/40 text-[9px] sm:hidden"><?= $t['consumer'] ?></span>
                        </div>
                        <span class="hidden sm:block text-white/70 text-xs"><?= $t['consumer'] ?></span>
                        <span class="<?= $statusClass ?> text-[10px] font-bold uppercase tracking-tighter sm:items-start items-end flex sm:justify-start justify-end">
                            ● <?= $t['status'] ?>
                        </span>
                        <span class="hidden sm:block text-white/40 text-[10px] text-right font-mono"><?= date('d.m.y', strtotime($t['date_entry'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 shrink-0">
            <a href="/absensi" class="group">
                <div class="glass rounded-2xl p-4 text-center group-hover:bg-white/20 transition-all border border-white/5 shadow-md">
                    <i data-lucide="user-check" class="w-7 h-7 md:w-10 md:h-10 mx-auto mb-2 opacity-80 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] md:text-xs uppercase tracking-widest">Absensi</span>
                </div>
            </a>
            <a href="/payment" class="group">
                <div class="glass rounded-2xl p-4 text-center group-hover:bg-white/20 transition-all border border-white/5 shadow-md">
                    <i data-lucide="banknote" class="w-7 h-7 md:w-10 md:h-10 mx-auto mb-2 opacity-80 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] md:text-xs uppercase tracking-widest">Payment</span>
                </div>
            </a>
            <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="/report" class="group">
                <div class="glass rounded-2xl p-4 text-center group-hover:bg-white/20 transition-all border border-white/5 shadow-md">
                    <i data-lucide="file-text" class="w-7 h-7 md:w-10 md:h-10 mx-auto mb-2 opacity-80 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] md:text-xs uppercase tracking-widest">Report</span>
                </div>
            </a>
            <?php endif; ?>
        </div>

    </div>
</main>

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

    function updateRealtimeClock() {
        const dateEl = document.getElementById('currentDate');
        if (!dateEl) return;
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const secs = String(now.getSeconds()).padStart(2, '0');
        dateEl.innerHTML = `<span class="opacity-50">${day}/${month}/${year}</span> <span class="font-bold text-blue-400 ml-2">${hours}:${mins}:${secs}</span>`;
    }

    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000);
</script>

</body>
</html>
