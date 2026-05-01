<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Workflow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-item:hover { background: rgba(255, 255, 255, 0.05); }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 50; align-items: center; justify-content: center; padding: 20px; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="min-h-screen flex">

    <!-- Sidebar -->
    <div class="w-64 glass border-r border-white/10 flex flex-col p-6 hidden lg:flex">
        <div class="flex items-center gap-4 mb-10">
            <img src="/images/logo-4.png" class="w-12 h-12 rounded-full border border-white/20 shadow-xl" alt="logo">
            <span class="font-bold text-xl tracking-tight">Workflow</span>
        </div>
        
        <nav class="space-y-2 flex-1">
            <a href="/dashboard" class="flex items-center gap-3 p-3 rounded-xl sidebar-item transition text-white/70 hover:text-white">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
            </a>
            <a href="/integration" class="flex items-center gap-3 p-3 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/20">
                <i data-lucide="cable" class="w-5 h-5"></i><span>Integrasi IoT</span>
            </a>
        </nav>

        <div class="pt-6 border-t border-white/10">
            <a href="/logout" class="flex items-center gap-3 p-3 rounded-xl text-red-400 hover:bg-red-500/10 transition">
                <i data-lucide="log-out" class="w-5 h-5"></i><span>Keluar</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="p-6 flex justify-between items-center glass border-b border-white/5">
            <div>
                <h1 class="text-2xl font-bold">Integrasi IoT</h1>
                <p class="text-white/40 text-sm">Kelola perangkat dan monitoring langsung</p>
            </div>
            <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-lg shadow-blue-600/20">
                <i data-lucide="plus" class="w-5 h-5"></i> Tambah Device
            </button>
        </header>

        <main class="p-8 overflow-y-auto flex-1 bg-[#020617]">
            <div id="deviceContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Data akan dimuat via JS -->
                <div class="col-span-full py-20 text-center">
                    <div class="animate-spin w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                    <p class="text-white/40">Memuat perangkat...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Tambah -->
    <div id="addModal" class="modal">
        <div class="glass p-8 rounded-3xl w-full max-w-md border border-white/10 shadow-2xl">
            <h2 class="text-xl font-bold mb-6">Tambah Perangkat</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-white/60 mb-1.5">Nama Perangkat</label>
                    <input type="text" id="nameInp" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:border-blue-500 outline-none transition" placeholder="Contoh: Kamera Absensi">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm text-white/60 mb-1.5">IP Address</label>
                        <input type="text" id="ipInp" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:border-blue-500 outline-none transition" placeholder="192.168.x.x">
                    </div>
                    <div>
                        <label class="block text-sm text-white/60 mb-1.5">Port</label>
                        <input type="number" id="portInp" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:border-blue-500 outline-none transition" value="1984">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-white/60 mb-1.5">Stream Path (Opsional)</label>
                    <input type="text" id="pathInp" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:border-blue-500 outline-none transition" placeholder="/api/stream.mjpeg?src=...">
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <button onclick="closeAddModal()" class="flex-1 py-3 rounded-xl hover:bg-white/5 transition border border-white/10">Batal</button>
                <button onclick="saveDevice()" class="flex-1 bg-blue-600 hover:bg-blue-500 py-3 rounded-xl transition font-bold">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Monitor -->
    <div id="monitorModal" class="modal">
        <div class="glass rounded-3xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden border border-white/10 shadow-2xl relative">
            <div class="p-4 border-b border-white/5 flex justify-between items-center bg-white/5">
                <div id="monitorStatus" class="text-sm text-white/60">Menghubungkan...</div>
                <button onclick="closeMonitorModal()" class="p-2 hover:bg-red-500/20 text-red-400 rounded-lg transition"><i data-lucide="x"></i></button>
            </div>
            <iframe id="monitorFrame" class="flex-1 bg-black w-full h-full border-none" allow="autoplay"></iframe>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Load data on start
        fetchDevices();

        async function fetchDevices() {
            const container = document.getElementById('deviceContainer');
            try {
                const res = await fetch('/integration/getDevices');
                const result = await res.json();
                
                if (result.success) {
                    container.innerHTML = result.data.length ? '' : '<div class="col-span-full py-20 text-center text-white/30">Belum ada perangkat.</div>';
                    result.data.forEach(dev => {
                        container.innerHTML += `
                            <div class="glass p-6 rounded-3xl border border-white/5 hover:border-blue-500/30 transition group relative overflow-hidden">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="p-3 bg-blue-500/10 rounded-2xl text-blue-400"><i data-lucide="video"></i></div>
                                    <div class="flex gap-2">
                                        <button onclick="checkStatus(${dev.id})" class="p-2 hover:bg-white/10 rounded-xl transition text-white/40" title="Cek Koneksi"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                                        <button onclick="deleteDevice(${dev.id})" class="p-2 hover:bg-red-500/10 rounded-xl transition text-red-400/40 hover:text-red-400" title="Hapus"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                </div>
                                <h3 class="font-bold text-lg mb-1">${dev.device_name}</h3>
                                <p class="text-white/40 text-sm mb-4">${dev.device_ip}:${dev.device_port}</p>
                                
                                <div class="flex gap-2 mt-4">
                                    <button onclick="viewMonitor('${dev.device_ip}', ${dev.device_port})" class="flex-1 bg-white/5 hover:bg-white/10 py-2.5 rounded-xl transition text-sm font-medium border border-white/10">Monitor</button>
                                    <button onclick="viewScanner('${dev.device_ip}', ${dev.device_port}, '${dev.stream_path}')" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl transition text-sm font-bold shadow-lg shadow-blue-600/10">Scanner</button>
                                </div>
                            </div>
                        `;
                    });
                    lucide.createIcons();
                }
            } catch (e) {
                container.innerHTML = '<div class="col-span-full py-20 text-center text-red-400">Gagal memuat data.</div>';
            }
        }

        function viewMonitor(ip, port) {
            const frame = document.getElementById('monitorFrame');
            const status = document.getElementById('monitorStatus');
            const url = `http://${ip}:${port}`;
            
            status.innerHTML = `Membuka <span class="text-blue-400">${url}</span> (Pastikan Anda se-jaringan)`;
            frame.src = url;
            document.getElementById('monitorModal').classList.add('active');
        }

        function viewScanner(ip, port, path) {
            const streamPath = path || '/api/stream.mjpeg?src=kamera_absensi';
            window.location.href = `/integration/scanner?ip=${ip}&port=${port}&path=${encodeURIComponent(streamPath)}`;
        }

        async function saveDevice() {
            const data = {
                device_name: document.getElementById('nameInp').value,
                device_ip: document.getElementById('ipInp').value,
                device_port: document.getElementById('portInp').value,
                stream_path: document.getElementById('pathInp').value
            };

            if (!data.device_name || !data.device_ip) return alert('Nama dan IP wajib diisi!');

            const res = await fetch('/integration/addDevice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            if (result.success) {
                closeAddModal();
                fetchDevices();
            }
        }

        async function deleteDevice(id) {
            if (!confirm('Hapus perangkat ini?')) return;
            const res = await fetch(`/integration/deleteDevice/${id}`);
            const result = await res.json();
            if (result.success) fetchDevices();
        }

        async function checkStatus(id) {
            const res = await fetch(`/integration/checkDeviceStatus?device_id=${id}`);
            const result = await res.json();
            alert(`Perangkat sedang ${result.status.toUpperCase()}`);
        }

        function openAddModal() { document.getElementById('addModal').classList.add('active'); }
        function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
        function closeMonitorModal() { 
            document.getElementById('monitorFrame').src = '';
            document.getElementById('monitorModal').classList.remove('active'); 
        }
    </script>
</body>
</html>
