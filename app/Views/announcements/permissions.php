<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Permissions</title>
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

        .scroll-custom::-webkit-scrollbar { width: 4px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>

<body class="h-screen flex items-center justify-center p-4 md:p-10 text-white font-light">

    <div class="glass rounded-[32px] p-6 md:p-10 w-full max-w-[800px] h-full flex flex-col overflow-hidden relative">
        <div class="flex justify-between items-center mb-8 shrink-0">
            <div class="flex items-center gap-4">
                <a href="/announcement" class="hover:bg-white/10 p-2 rounded-xl transition">
                    <i data-lucide="arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold">Akses Pengumuman</h1>
                    <p class="text-white/50 text-xs mt-1 uppercase tracking-widest">Atur siapa saja yang bisa membuat pengumuman</p>
                </div>
            </div>
            <i data-lucide="shield-check" class="w-10 h-10 opacity-20"></i>
        </div>

        <div class="flex-1 overflow-y-auto pr-2 scroll-custom">
            <div class="grid grid-cols-1 gap-3">
                <?php foreach($users as $u): ?>
                    <div class="glass rounded-2xl p-4 flex items-center justify-between border border-white/5 hover:bg-white/5 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-bold border border-blue-500/30">
                                <?= strtoupper(substr($u['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm"><?= esc($u['username']) ?></h3>
                                <p class="text-[10px] text-white/40 uppercase tracking-widest"><?= esc($u['role']) ?></p>
                            </div>
                        </div>

                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20">Full Access</span>
                        <?php else: ?>
                            <button onclick="toggle(<?= $u['id'] ?>, this)" 
                                    class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase transition flex items-center gap-2 <?= ($u['can_announce'] ?? 0) == 1 ? 'bg-green-500 text-white shadow-lg shadow-green-500/20' : 'bg-white/10 text-white/60 hover:bg-white/20' ?>">
                                <i data-lucide="<?= ($u['can_announce'] ?? 0) == 1 ? 'check-circle' : 'circle' ?>" class="w-4 h-4"></i>
                                <?= ($u['can_announce'] ?? 0) == 1 ? 'Diberikan Izin' : 'Beri Izin' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-white/10 text-center shrink-0">
            <p class="text-white/30 text-[10px] uppercase tracking-[0.2em]">Sistem Moderasi Pengumuman v1.0</p>
        </div>
    </div>

<script>
    lucide.createIcons();

    async function toggle(userId, btn) {
        const response = await fetch('/announcement/togglePermission/' + userId);
        const res = await response.json();
        
        if (res.success) {
            const isGranted = res.new_status == 1;
            btn.className = `px-4 py-2 rounded-xl text-[10px] font-bold uppercase transition flex items-center gap-2 ${isGranted ? 'bg-green-500 text-white shadow-lg shadow-green-500/20' : 'bg-white/10 text-white/60 hover:bg-white/20'}`;
            btn.innerHTML = `<i data-lucide="${isGranted ? 'check-circle' : 'circle'}" class="w-4 h-4"></i> ${isGranted ? 'Diberikan Izin' : 'Beri Izin'}`;
            lucide.createIcons();
        } else {
            alert(res.message);
        }
    }
</script>

</body>
</html>
