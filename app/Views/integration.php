<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrasi IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
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

        /* Device Card Animation */
        .device-card {
            transition: all 0.3s ease;
        }

        .device-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        /* Status Indicator */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-online {
            background-color: #10b981;
        }

        .status-offline {
            background-color: #6b7280;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Streaming Video Container */
        .stream-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            min-height: 300px;
        }

        .stream-video {
            width: 100%;
            height: 100%;
            display: block;
            border: none;
        }

        .stream-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 2;
        }

        .device-ip-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
        }

        .device-ip-overlay button {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4px;
            border-radius: 9999px;
            color: white;
            cursor: pointer;
        }

        .device-ip-overlay button:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        .qr-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid #10b981;
            border-radius: 8px;
            animation: qrPulse 1s infinite;
            pointer-events: none;
        }

        @keyframes qrPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Scrollbar */
        .device-scroll::-webkit-scrollbar { width: 4px; }
        .device-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>

<body class="h-screen flex flex-col md:flex-row text-white font-light">

<!-- Mobile Header -->
<div class="md:hidden flex items-center justify-between p-4 glass z-[60]">
    <button id="open-btn" class="p-1"><i data-lucide="menu" class="w-8 h-8"></i></button>
    <img src="/images/logo-4.png" class="w-10 h-10 rounded-full border border-white/40" alt="logo">
    <div class="w-8"></div>
</div>

<!-- Sidebar -->
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
            <a href="/bank-file" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="folder"></i><span>Bank File</span></a>
            <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="/payment-management" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="banknote"></i><span>Management Payment</span></a>
            <?php endif; ?>
            <a href="/integration" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="cable"></i><span>Integrasi IoT</span></a>
        </div>
    </div>
    <div class="flex flex-col gap-3">
        <a href="/credit" class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white py-2 rounded-xl transition text-sm border border-white/10">
            <i data-lucide="info" class="w-4 h-4"></i> Credit
        </a>
        <a href="<?= base_url('logout') ?>" class="bg-red-500/80 hover:bg-red-600 text-center py-2 rounded-xl transition text-sm">Logout</a>
    </div>
</nav>

<!-- Overlay -->
<div id="overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[90] hidden md:hidden"></div>

<!-- Main Content -->
<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden">

    <div class="glass rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div>
                <h1 class="text-xl md:text-2xl">Integrasi IoT</h1>
                <p class="text-white/60 text-xs md:text-sm mt-1">Kelola perangkat dan sensor IoT Anda</p>
            </div>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <!-- Main Container -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 overflow-hidden">
            
            <!-- Left Section - Device List -->
            <div class="lg:col-span-2 glass rounded-3xl p-5 flex flex-col overflow-hidden shadow-lg">
                <div class="flex justify-between items-center mb-4 shrink-0">
                    <h2 class="text-lg font-medium">Perangkat Terdaftar</h2>
                    <div class="flex gap-2">
                        <button onclick="refreshAllDeviceStatus()" class="bg-blue-500/80 hover:bg-blue-600 px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1" title="Refresh status semua device">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            Refresh All
                        </button>
                        <button onclick="openAddDeviceModal()" class="bg-green-500/80 hover:bg-green-600 px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1">
                            <i data-lucide="plus" class="w-3 h-3"></i>
                            Tambah Device
                        </button>
                    </div>
                </div>

                <div class="hidden sm:grid grid-cols-5 gap-3 bg-white/5 rounded-xl px-4 py-2 mb-3 text-[10px] font-bold uppercase tracking-widest opacity-60">
                    <span>Device</span>
                    <span>Tipe</span>
                    <span>Status</span>
                    <span>Last Update</span>
                    <span class="text-right">Aksi</span>
                </div>

                <div class="overflow-y-auto device-scroll flex-1 pr-2" id="iotDeviceList">
                    <!-- Device list akan diisi oleh JavaScript -->
                    <div class="text-center py-12 text-white/40">
                        <div class="animate-spin mb-3">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto opacity-50"></i>
                        </div>
                        <p class="text-sm">Loading devices...</p>
                    </div>
                </div>
            </div>

            <!-- Right Section - Statistics & Info -->
            <div class="flex flex-col gap-4 overflow-hidden">
                
                <!-- Status Summary -->
                <div class="glass rounded-3xl p-5 flex-1 flex flex-col overflow-hidden shadow-lg">
                    <h3 class="text-lg font-medium mb-4 shrink-0">Ringkasan Status</h3>
                    
                    <div class="space-y-3 flex-1">
                        <div class="glass rounded-xl p-4 flex items-center gap-3">
                            <div class="text-3xl text-green-400 font-bold">2</div>
                            <div class="flex flex-col">
                                <span class="text-xs text-white/70">Total Device</span>
                                <span class="text-white/40 text-[9px]">Terdaftar</span>
                            </div>
                        </div>
                        
                        <div class="glass rounded-xl p-4 flex items-center gap-3">
                            <div class="text-3xl text-blue-400 font-bold">1</div>
                            <div class="flex flex-col">
                                <span class="text-xs text-white/70">Device Online</span>
                                <span class="text-white/40 text-[9px]">Terhubung</span>
                            </div>
                        </div>
                        
                        <div class="glass rounded-xl p-4 flex items-center gap-3">
                            <div class="text-3xl text-purple-400 font-bold">5</div>
                            <div class="flex flex-col">
                                <span class="text-xs text-white/70">Absensi Hari Ini</span>
                                <span class="text-white/40 text-[9px]">Total Scan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass rounded-3xl p-5 flex-1 flex flex-col overflow-hidden shadow-lg">
                    <h3 class="text-lg font-medium mb-4 shrink-0">Aktivitas Terbaru</h3>
                    
                    <div class="space-y-2 flex-1 overflow-y-auto pr-2">
                        <div class="flex items-start gap-2 text-xs">
                            <div class="w-2 h-2 rounded-full bg-green-400 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white/80">Absensi IoT: User John Doe - Masuk</p>
                                <p class="text-white/40">5 menit yang lalu - Device_01</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-2 text-xs">
                            <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white/80">QR Code terdeteksi</p>
                                <p class="text-white/40">2 menit yang lalu - Device ID: Device_01</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2 text-xs">
                            <div class="w-2 h-2 rounded-full bg-purple-400 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white/80">Device connected</p>
                                <p class="text-white/40">10 menit yang lalu - IP: 192.168.1.100</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Device Floating Window Modal -->
<div id="streamModal" class="hidden fixed z-[200]" style="top: 20px; right: 20px; width: 900px; height: 650px;">
    <div class="bg-white rounded-3xl shadow-2xl w-full h-full flex flex-col overflow-hidden border-4 border-blue-500">
        <!-- Content -->
        <div class="flex-1 overflow-hidden bg-gray-900 relative">
            <div class="stream-overlay">
                <span id="deviceStatus">Menunggu koneksi...</span>
            </div>
            <div class="device-ip-overlay">
                <span id="deviceIpOverlayLabel">-</span>
                <button type="button" onclick="closeDeviceModal()" title="Tutup">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
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

function minimizeDeviceWindow() {
    const modal = document.getElementById('streamModal');
    const content = modal.querySelector('.flex-1');

    isDeviceWindowMinimized = !isDeviceWindowMinimized;

    if (isDeviceWindowMinimized) {
        modal.style.height = 'auto';
        content.classList.add('hidden');
    } else {
        modal.style.height = '600px';
        content.classList.remove('hidden');
    }
}

function setupWindowDrag() {
    const modal = document.getElementById('streamModal');

    modal.addEventListener('mousedown', function(e) {
        if (e.target.tagName === 'IFRAME') return; // Don't drag when clicking iframe
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

setupWindowDrag();

// Close modal when clicking outside or pressing ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('streamModal').classList.contains('hidden')) {
        closeDeviceModal();
    }
});

document.addEventListener('click', function(e) {
    const modal = document.getElementById('streamModal');
    if (!modal.classList.contains('hidden') && e.target === modal) {
        closeDeviceModal();
    }
});
</script>

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

    // Auto refresh device status on load
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(loadDevices, 500);
        setTimeout(loadSummary, 1000);
        setTimeout(refreshAllDeviceStatusSilent, 2000);
    });

    async function refreshAllDeviceStatusSilent() {
        try {
            await fetch('/integration/refresh-all-status', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            loadDevices();
            loadSummary();
        } catch (e) {}
    }

    // Device Functions
    function addNewDevice() {
        alert('Fitur tambah device akan ditampilkan di modal dialog');
    }

    function viewDeviceDetail(deviceId) {
        alert('Menampilkan detail device #' + deviceId);
    }

    function editDevice(deviceId) {
        alert('Edit device #' + deviceId);
    }

    function deleteDevice(deviceId) {
        if (confirm('Yakin ingin menghapus device #' + deviceId + '?')) {
            alert('Device #' + deviceId + ' dihapus');
        }
    }

    // Initialize icons
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>

<!-- Device Streaming & QR Detection Script -->
<script>
let currentDeviceUrl = '';
let currentDeviceStreamUrl = '';
let currentDeviceName = '';

function openDeviceModal() {
    document.getElementById('streamModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeviceModal() {
    document.getElementById('streamModal').classList.add('hidden');
    document.body.style.overflow = '';
    const frame = document.getElementById('deviceFrame');
    frame.src = 'about:blank';
    document.getElementById('deviceStatus').textContent = 'Menunggu koneksi...';
}

async function viewStream(deviceId, deviceIp, devicePort, streamPath, pageUrl, mode = 'proxy') {
    const statusLabel = document.getElementById('deviceStatus');
    const frame = document.getElementById('deviceFrame');
    
    // Gunakan Proxy Umum secara default (untuk dashboard HTML IoT)
    const rawUrl = pageUrl || `http://${deviceIp}:${devicePort}`;
    
    if (mode === 'scanner') {
        currentDeviceUrl = `/integration/scanner?ip=${deviceIp}&port=${devicePort}&path=` + encodeURIComponent(streamPath);
    } else {
        currentDeviceUrl = `/integration/proxy?url=` + encodeURIComponent(rawUrl);
    }
    
    // Simpan URL stream untuk keperluan internal
    const normalizedPath = streamPath.startsWith('/') ? streamPath : '/' + streamPath;
    const rawStreamUrl = `http://${deviceIp}:${devicePort}${normalizedPath}`;
    currentDeviceStreamUrl = `/integration/stream?url=` + encodeURIComponent(rawStreamUrl);
    
    // Show modal with loading state
    statusLabel.textContent = `Sedang memindai ${deviceIp}:${devicePort} (via Proxy)...`;
    statusLabel.className = "text-blue-400 animate-pulse";
    frame.src = 'about:blank';
    openDeviceModal();

    // Pre-check connectivity via server
    try {
        const response = await fetch('/integration/check-device-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ device_id: deviceId })
        });
        const result = await response.json();
        
        if (result.success && result.status === 'online') {
            statusLabel.textContent = 'Terhubung! Memuat antarmuka...';
            statusLabel.className = "text-green-400";
            setTimeout(() => {
                frame.src = currentDeviceUrl;
            }, 500);
        } else {
            statusLabel.textContent = 'Perangkat tidak merespon (Offline)';
            statusLabel.className = "text-red-400";
            if(confirm('Perangkat tampaknya offline. Tetap mencoba buka?')) {
                frame.src = currentDeviceUrl;
            }
        }
    } catch (error) {
        frame.src = currentDeviceUrl;
    }
}

function openDevicePage() {
    if (!currentDeviceUrl) {
        alert('Tidak ada perangkat yang dipilih.');
        return;
    }
    window.open(currentDeviceUrl, '_blank');
}

function loadDeviceStream() {
    if (!currentDeviceStreamUrl) {
        alert('Tidak ada stream perangkat yang dipilih.');
        return;
    }
    const frame = document.getElementById('deviceFrame');
    frame.src = currentDeviceStreamUrl;
    document.getElementById('deviceStatus').textContent = 'Menghubungkan ke stream...';
}

function reloadDeviceFrame() {
    const frame = document.getElementById('deviceFrame');
    if (frame.src && frame.src !== 'about:blank') {
        frame.src = frame.src;
        document.getElementById('deviceStatus').textContent = 'Menyegarkan...';
    }
}

// Process Absensi
async function processAbsensi() {
    const qrData = document.getElementById('qrText').textContent;

    if (!qrData) {
        alert('No QR data to process');
        return;
    }

    try {
        const response = await fetch('/integration/process-absensi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                qr_data: qrData,
                device_ip: document.getElementById('deviceIpInput').value
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Absensi berhasil diproses!\n' + result.message);
            document.getElementById('qrResult').classList.add('hidden');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error processing absensi:', error);
        alert('Error processing absensi');
    }
}


// ====== IoT Device Management ======
async function loadDevices() {
    try {
        const response = await fetch('/integration/get-devices', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        if (result.success) {
            displayDevices(result.data);
        }
    } catch (error) {
        console.error('Error loading devices:', error);
    }
}

function displayDevices(devices) {
    const deviceList = document.getElementById('iotDeviceList');
    
    if (!deviceList) return;
    
    if (devices.length === 0) {
        deviceList.innerHTML = '<div class="text-center py-12 text-white/40"><i data-lucide="wifi-off" class="w-12 h-12 mx-auto mb-3 opacity-50"></i><p class="text-sm">Belum ada perangkat IoT terdaftar</p></div>';
        return;
    }
    
    deviceList.innerHTML = devices.map(device => `
        <div class="device-card glass rounded-xl px-4 py-3 mb-3 hover:bg-white/10 transition">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-center">
                <div class="flex flex-col">
                    <span class="text-sm font-medium">${device.device_name}</span>
                    <span class="text-white/40 text-[9px]">${device.device_ip}:${device.device_port}</span>
                </div>
                <div class="hidden sm:flex flex-col">
                    <span class="text-white/70 text-xs">${device.location || 'Unknown'}</span>
                    <span class="text-white/40 text-[9px]">Lokasi</span>
                </div>
                <div class="hidden sm:flex flex-col">
                    <div class="flex items-center gap-2">
                        <div class="status-dot ${device.status === 'online' ? 'status-online' : 'status-offline'}"></div>
                        <span class="text-xs ${device.status === 'online' ? 'text-green-400' : 'text-gray-400'}">${device.status.toUpperCase()}</span>
                    </div>
                    <span class="text-white/40 text-[9px]">${device.status === 'online' ? 'Connected' : 'Disconnected'}</span>
                </div>
                <div class="hidden sm:flex flex-col">
                    <span class="text-white/70 text-xs">${device.last_seen ? new Date(device.last_seen).toLocaleTimeString('id-ID') : 'Never'}</span>
                    <span class="text-white/40 text-[9px]">${device.last_seen ? new Date(device.last_seen).toLocaleDateString('id-ID') : 'Never'}</span>
                </div>
                <div class="flex gap-2 justify-end sm:justify-end">
                    <button onclick="viewStream('${device.id}', '${device.device_ip}', ${device.device_port}, '${device.stream_path || '/stream'}', '${device.page_url}')" class="bg-blue-500/60 hover:bg-blue-600 p-2 rounded-lg transition-all text-xs" title="Lihat Halaman">
                        <i data-lucide="monitor" class="w-3 h-3"></i>
                    </button>
                    <button onclick="checkDeviceStatus(${device.id})" class="bg-green-500/60 hover:bg-green-600 p-2 rounded-lg transition-all text-xs" title="Cek Status">
                        <i data-lucide="activity" class="w-3 h-3"></i>
                    </button>
                    <button onclick="removeDeviceConfirm(${device.id}, '${device.device_name}')" class="bg-red-500/60 hover:bg-red-600 p-2 rounded-lg transition-all text-xs" title="Hapus">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function openAddDeviceModal() {
    document.getElementById('addDeviceModal').classList.remove('hidden');
}

function closeAddDeviceModal() {
    document.getElementById('addDeviceModal').classList.add('hidden');
    document.getElementById('addDeviceForm').reset();
}

async function addNewDeviceSubmit(event) {
    event.preventDefault();
    
    const deviceIp = document.getElementById('deviceIp').value.trim();
    const deviceName = document.getElementById('deviceName').value.trim();
    const devicePortValue = document.getElementById('devicePort').value.trim();
    const devicePort = devicePortValue ? parseInt(devicePortValue, 10) : 80;
    const streamPath = document.getElementById('streamPath').value.trim() || '/stream';
    
    if (!deviceIp) {
        alert('IP Address harus diisi');
        return;
    }
    
    if (!deviceName) {
        alert('Nama Device harus diisi');
        return;
    }

    if (!devicePortValue || isNaN(devicePort) || devicePort < 1 || devicePort > 65535) {
        alert('Port tidak valid. Masukkan angka antara 1 dan 65535.');
        return;
    }
    
    try {
        const response = await fetch('/integration/add-device', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                device_ip: deviceIp,
                device_name: deviceName,
                device_port: devicePort,
                stream_path: streamPath,
                location: 'Unknown'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Device berhasil ditambahkan! Status: ' + result.status);
            closeAddDeviceModal();
            loadDevices();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding device:', error);
        alert('Error menambahkan device: ' + error.message);
    }
}

function checkDeviceStatus(deviceId) {
    fetch('/integration/check-device-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`Device Status: ${result.status.toUpperCase()}`);
            loadDevices();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error checking status:', error);
        alert('Error checking device status');
    });
}

function removeDeviceConfirm(deviceId, deviceName) {
    if (confirm(`Yakin ingin menghapus device "${deviceName}"?`)) {
        removeDevice(deviceId);
    }
}

function removeDevice(deviceId) {
    fetch('/integration/remove-device', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Device berhasil dihapus');
            loadDevices();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error removing device:', error);
        alert('Error menghapus device');
    });
}

// Load summary data
async function loadSummary() {
    try {
        const response = await fetch('/integration/get-summary', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        if (result.success) {
            updateSummaryDisplay(result.data);
        }
    } catch (error) {
        console.error('Error loading summary:', error);
    }
}

function updateSummaryDisplay(data) {
    // Update total devices
    const totalDevicesEl = document.querySelector('.glass.rounded-xl.p-4 .text-3xl.font-bold.text-green-400');
    if (totalDevicesEl) {
        totalDevicesEl.textContent = data.total_devices;
    }

    // Update online devices
    const onlineDevicesEl = document.querySelector('.glass.rounded-xl.p-4 .text-3xl.font-bold.text-blue-400');
    if (onlineDevicesEl) {
        onlineDevicesEl.textContent = data.online_devices;
    }

    // Update today attendance
    const attendanceEl = document.querySelector('.glass.rounded-xl.p-4 .text-3xl.font-bold.text-purple-400');
    if (attendanceEl) {
        attendanceEl.textContent = data.today_attendance;
    }
}

// Refresh all device status
async function refreshAllDeviceStatus() {
    try {
        const refreshBtn = event.target.closest('button');
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i data-lucide="loader" class="w-3 h-3 animate-spin"></i>Refreshing...';
        
        const response = await fetch('/integration/refresh-all-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        });
        const result = await response.json();
        
        if (result.success) {
            // Reload devices and summary
            await loadDevices();
            await loadSummary();
            
            // Show refresh status modal
            document.getElementById('refreshStatusOnline').textContent = result.data.online;
            document.getElementById('refreshStatusOffline').textContent = result.data.offline;
            document.getElementById('refreshStatusTotal').textContent = result.data.checked;
            document.getElementById('refreshStatusModal').classList.remove('hidden');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error refreshing device status:', error);
        alert('Error refreshing device status: ' + error.message);
    } finally {
        const refreshBtn = event.target.closest('button');
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3"></i>Refresh All';
            lucide.createIcons();
        }
    }
}

function closeRefreshStatusModal() {
    document.getElementById('refreshStatusModal').classList.add('hidden');
}

// Load recent activities
async function loadRecentActivities() {
    try {
        const response = await fetch('/integration/get-recent-activities', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        if (result.success) {
            updateActivitiesDisplay(result.data);
        }
    } catch (error) {
        console.error('Error loading recent activities:', error);
    }
}

function updateActivitiesDisplay(activities) {
    const activitiesContainer = document.querySelector('.glass.rounded-3xl.p-5.flex-1.flex.flex-col.overflow-hidden.shadow-lg .space-y-2.flex-1.overflow-y-auto.pr-2');
    
    if (!activitiesContainer) return;
    
    if (activities.length === 0) {
        activitiesContainer.innerHTML = '<div class="text-center py-8 text-white/40"><i data-lucide="activity" class="w-8 h-8 mx-auto mb-2 opacity-50"></i><p class="text-sm">Belum ada aktivitas</p></div>';
        return;
    }
    
    activitiesContainer.innerHTML = activities.map(activity => `
        <div class="flex items-start gap-2 text-xs">
            <div class="w-2 h-2 rounded-full bg-${activity.color}-400 mt-1.5 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-white/80">${activity.message}</p>
                <p class="text-white/40">${timeAgo(activity.time)} - ${activity.device}</p>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function timeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diffInSeconds = Math.floor((now - past) / 1000);
    
    if (diffInSeconds < 60) return `${diffInSeconds} detik yang lalu`;
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit yang lalu`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam yang lalu`;
    return `${Math.floor(diffInSeconds / 86400)} hari yang lalu`;
}

// Load devices on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDevices();
    loadSummary();
    loadRecentActivities();
    
    // Update device list every 30 seconds
    setInterval(loadDevices, 30000);
    // Update device status from server every 2 minutes (automatic health check)
    setInterval(function() {
        fetch('/integration/refresh-all-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        }).then(() => {
            loadDevices();
            loadSummary();
        }).catch(err => console.error('Auto-refresh error:', err));
    }, 120000);
    // Update summary every 10 seconds
    setInterval(loadSummary, 10000);
    // Update activities every 15 seconds
    setInterval(loadRecentActivities, 15000);
});

// Generate QR String Functions
function generateQrString() {
    document.getElementById('generateQrModal').classList.remove('hidden');
    document.getElementById('generatedQrString').textContent = 'Generating...';
    
    fetch('/api/iot/generate-string', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            document.getElementById('generatedQrString').textContent = result.encrypted_string;
        } else {
            document.getElementById('generatedQrString').textContent = 'Error: ' + (result.message || 'Failed to generate QR string');
        }
    })
    .catch(error => {
        console.error('Error generating QR string:', error);
        document.getElementById('generatedQrString').textContent = 'Error: Failed to generate QR string';
    });
}

function closeGenerateQrModal() {
    document.getElementById('generateQrModal').classList.add('hidden');
}

function copyQrString() {
    const qrString = document.getElementById('generatedQrString').textContent;
    if (qrString && qrString !== 'Click "Generate" to create QR string...' && !qrString.startsWith('Generating...') && !qrString.startsWith('Error:')) {
        navigator.clipboard.writeText(qrString).then(function() {
            alert('QR string copied to clipboard!');
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = qrString;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('QR string copied to clipboard!');
        });
    } else {
        alert('No valid QR string to copy');
    }
}
</script>
    <!-- Add Device Modal -->
    <div id="addDeviceModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-8 max-w-md w-full shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-6 h-6"></i>
                Tambah Perangkat IoT
            </h2>
            
            <form id="addDeviceForm" onsubmit="addNewDeviceSubmit(event)" class="space-y-4">
                <!-- Device Name -->
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Nama Device *</label>
                    <input type="text" id="deviceName" placeholder="Contoh: IoT-Device-01" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/40 focus:outline-none focus:border-blue-400" required>
                </div>

                <!-- Device IP -->
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">IP Address *</label>
                    <input type="text" id="deviceIp" placeholder="Contoh: 192.168.100.88" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/40 focus:outline-none focus:border-blue-400" required>
                    <p class="text-white/40 text-xs mt-1">Contoh: 192.168.100.88 atau 192.168.2.100</p>
                </div>

                <!-- Device Port -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white/80 text-sm font-medium mb-2">Port *</label>
                        <input type="number" id="devicePort" placeholder="Contoh: 80" value="80" min="1" max="65535" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/40 focus:outline-none focus:border-blue-400" required>
                    </div>
                    <div>
                        <label class="block text-white/80 text-sm font-medium mb-2">Stream Path</label>
                        <input type="text" id="streamPath" placeholder="Contoh: /stream" value="/stream" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/40 focus:outline-none focus:border-blue-400">
                    </div>
                </div>
                <p class="text-white/40 text-[10px] mt-1">Path stream (misal: /?action=stream atau /api/stream.mjpeg?src=cam1)</p>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeAddDeviceModal()" class="flex-1 bg-gray-500/60 hover:bg-gray-600 text-white py-2 rounded-lg transition font-medium">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-blue-500/80 hover:bg-blue-600 text-white py-2 rounded-lg transition font-medium flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Tambah Device
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generate QR Modal -->
    <div id="generateQrModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-8 max-w-lg w-full shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="qr-code" class="w-6 h-6"></i>
                Generate QR String
            </h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Encrypted QR String</label>
                    <div class="bg-black/30 border border-white/20 rounded-lg p-4">
                        <p id="generatedQrString" class="text-white font-mono text-sm break-all">Click "Generate" to create QR string...</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeGenerateQrModal()" class="flex-1 bg-gray-500/60 hover:bg-gray-600 text-white py-2 rounded-lg transition font-medium">
                        Tutup
                    </button>
                    <button type="button" onclick="copyQrString()" class="flex-1 bg-blue-500/80 hover:bg-blue-600 text-white py-2 rounded-lg transition font-medium flex items-center justify-center gap-2">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Status Modal -->
    <div id="refreshStatusModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-8 max-w-md w-full shadow-2xl text-center">
            <div class="mb-6">
                <i data-lucide="check-circle-2" class="w-12 h-12 mx-auto text-green-400"></i>
            </div>
            
            <h2 class="text-2xl font-bold text-white mb-6">Status Device Di-Refresh!</h2>
            
            <div class="space-y-4 mb-6">
                <div class="bg-green-500/20 border border-green-500/40 rounded-lg p-4">
                    <p class="text-white/60 text-sm mb-1">Online</p>
                    <p class="text-3xl font-bold text-green-400" id="refreshStatusOnline">0</p>
                </div>
                
                <div class="bg-gray-500/20 border border-gray-500/40 rounded-lg p-4">
                    <p class="text-white/60 text-sm mb-1">Offline</p>
                    <p class="text-3xl font-bold text-gray-400" id="refreshStatusOffline">0</p>
                </div>
                
                <div class="bg-blue-500/20 border border-blue-500/40 rounded-lg p-4">
                    <p class="text-white/60 text-sm mb-1">Total</p>
                    <p class="text-3xl font-bold text-blue-400" id="refreshStatusTotal">0</p>
                </div>
            </div>
            
            <button type="button" onclick="closeRefreshStatusModal()" class="w-full bg-blue-500/80 hover:bg-blue-600 text-white py-2 rounded-lg transition font-medium">
                OK
            </button>
        </div>
    </div>

</body>
</html>
