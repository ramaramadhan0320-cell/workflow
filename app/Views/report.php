<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?= esc(session()->get('username')) ?></title>
    
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('<?= base_url("images/bg.jpg") ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        @media (min-width: 768px) {
            body { overflow: hidden; }
            main { height: 100vh; overflow: hidden !important; }
        }

        @media (max-width: 767px) {
            body { min-height: 100vh; overflow-y: auto; }
            main { min-height: 100vh !important; overflow: visible !important; }
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .trans-scroll::-webkit-scrollbar { width: 4px; }
        .trans-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        .hide-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .hide-scroll::-webkit-scrollbar {
            display: none;
        }

        .status-masuk { color: #22c55e; }
        .status-keluar { color: #ef4444; }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
        }

        .summary-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 1.5rem;
            border-radius: 1rem;
        }
    </style>
</head>

<body class="text-white">

<main class="p-4 md:p-6 h-screen md:overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6 shrink-0">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="hover:bg-white/10 p-2 rounded-lg transition" title="Back">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>
            <h1 class="text-2xl md:text-3xl font-bold uppercase tracking-wide">Report</h1>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-white/60 text-sm">Tanggal:</label>
            <input type="date" id="reportDate" value="<?= $selected_date ?>" 
                   class="bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 transition"
                   onchange="changeDate(this.value)">
        </div>
    </div>

    <!-- Main Content - 2 Columns on Desktop -->
    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 overflow-hidden">

        <!-- Left Column: Summary + Modal Input -->
        <div class="md:col-span-1 flex flex-col gap-4 overflow-hidden">

            <!-- Summary Cards -->
            <div class="glass hide-scroll rounded-3xl p-4 md:p-5 border border-white/10 shadow-lg flex-1 overflow-y-auto">
                <h2 class="text-lg font-semibold uppercase tracking-widest mb-4 text-white">Summary</h2>

                <!-- Modal Awal Card -->
                <div class="summary-card mb-3">
                    <div class="text-white/60 text-xs uppercase tracking-widest mb-2">Modal Awal</div>
                    <div class="flex items-center gap-2">
                        <input type="number" id="modalAwalInput" value="<?= $modal_awal ?>" 
                               class="flex-1 bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white font-semibold text-lg focus:outline-none focus:border-blue-500 transition"
                               placeholder="0" min="0">
                        <button type="button" onclick="saveModalAwal()" class="btn-primary px-3 py-2 rounded-lg text-white font-semibold text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="text-[10px] text-white/40 mt-1">Ubah nilai modal awal bisnis Anda</div>
                </div>

                <!-- Uang Masuk (Otomatis dari Payments) -->
                <div class="summary-card mb-3 border-green-500/40">
                    <div class="text-white/60 text-xs uppercase tracking-widest mb-1">Uang Masuk</div>
                    <div class="text-2xl font-bold text-green-400">+ <?= number_format($summary['total_masuk'], 0, ',', '.') ?></div>
                    <div class="text-[10px] text-white/50 mt-1">
                        <div>Paid Payments: <?= number_format($summary['total_masuk_paid'], 0, ',', '.') ?></div>
                        <div>Manual: <?= number_format($summary['total_masuk_manual'], 0, ',', '.') ?></div>
                    </div>
                </div>

                <!-- Uang Keluar (Transaksi) -->
                <div class="summary-card mb-3 border-red-500/40">
                    <div class="text-white/60 text-xs uppercase tracking-widest mb-1">Uang Keluar</div>
                    <div class="text-2xl font-bold text-red-400">- <?= number_format($summary['total_belanja'], 0, ',', '.') ?></div>
                    <div class="text-[10px] text-white/50 mt-1"><?= $summary['count_belanja'] ?> transaksi</div>
                </div>

                <!-- Total Balance -->
                <div class="summary-card text-center" style="border-color: <?= $total_balance >= 0 ? 'rgba(34, 197, 94, 0.4)' : 'rgba(239, 68, 68, 0.4)' ?>;">
                    <div class="text-white/60 text-xs uppercase tracking-widest mb-1">Balance</div>
                    <div class="text-3xl font-bold" style="color: <?= $total_balance >= 0 ? '#22c55e' : '#ef4444' ?>">
                        <?= number_format($total_balance, 0, ',', '.') ?>
                    </div>
                </div>

                <!-- Attendance Performance Chart -->
                <div class="summary-card mt-3">
                    <div class="text-white/60 text-xs uppercase tracking-widest mb-2">Kehadiran Karyawan</div>
                    <canvas id="attendanceChart" height="180"></canvas>
                    <div class="text-white/70 text-xs mt-2">
                        Total: <?= $attendance['total_users'] ?> (admin+karyawan), Hadir: <?= $attendance['hadir'] ?>, Sakit: <?= $attendance['sakit'] ?>, Alfa/absen: <?= $attendance['alfa'] ?>
                    </div>
                    <div class="text-white/70 text-xs">
                        Presentase hadir: <?= $attendance['attendance_percentage'] ?>% (<?= $attendance['hadir'] ?>/<?= $attendance['total_users'] ?>)
                    </div>
                </div>

                <!-- Detail user per status kehadiran -->

                <!-- Export button container -->
                <div class="glass rounded-3xl p-4 md:p-5 border border-white/10 shadow-lg mt-3">
                    <h3 class="text-white/60 text-xs uppercase tracking-widest mb-2">Ekspor Laporan</h3>
                    <p class="text-white/70 text-xs mb-3">Ekspor data transaksi dan kehadiran untuk tanggal <?= $selected_date ?>.</p>
                    <a href="<?= base_url('report/export?date=' . $selected_date) ?>" class="btn-primary px-4 py-2 rounded-lg text-white font-semibold text-sm">Download CSV</a>
                </div>

            </div>

        </div>

        <!-- Right Column: Transactions -->
        <div class="md:col-span-2 flex flex-col gap-4 overflow-hidden">

            <!-- Form Input Transaksi -->
            <div class="glass rounded-3xl p-4 md:p-5 border border-white/10 shadow-lg">
                <h2 class="text-lg font-semibold uppercase tracking-widest mb-3">Tambah Transaksi Manual</h2>
                <form id="transactionForm" class="grid grid-cols-2 md:grid-cols-12 gap-2 items-end">
                    <div class="md:col-span-2">
                        <label class="text-[9px] font-bold uppercase text-white/60 mb-1 block">Tipe</label>
                        <select name="tipe_transaksi" id="tipe_transaksi" class="w-full bg-white/10 border border-white/20 rounded-lg px-2 py-2 text-white text-xs focus:outline-none transition-all duration-300" required onchange="toggleTransactionFields(this.value)">
                            <option value="" class="bg-gray-800">Pilih</option>
                            <option value="masuk" class="bg-gray-800 text-green-400">Masuk (+)</option>
                            <option value="keluar" class="bg-gray-800 text-red-400">Keluar (-)</option>
                            <option value="cashbon" class="bg-gray-800 text-yellow-400">Cashbon (-)</option>
                            <option value="bonus" class="bg-gray-800 text-yellow-400">Bonus (+)</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-3" id="field_nama">
                        <label class="text-[9px] font-bold uppercase text-white/60 mb-1 block">Nama Transaksi</label>
                        <input type="text" name="nama_transaksi" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white text-xs focus:outline-none focus:border-blue-500" placeholder="Contoh: Belanja Stok">
                    </div>

                    <div class="md:col-span-3 hidden" id="field_user">
                        <label class="text-[9px] font-bold uppercase text-white/60 mb-1 block">Pilih Karyawan</label>
                        <select name="target_user_id" class="w-full bg-white/10 border border-white/20 rounded-lg px-2 py-2 text-white text-xs focus:outline-none focus:border-blue-500">
                            <option value="">-- Pilih User --</option>
                            <?php foreach($all_users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= esc($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-[9px] font-bold uppercase text-white/60 mb-1 block">Nominal</label>
                        <input type="text" name="harga" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white text-xs focus:outline-none focus:border-blue-500" placeholder="0" required oninput="formatRupiahInput(this)">
                    </div>

                    <div class="md:col-span-5 flex flex-col justify-end items-end">
                        <input type="hidden" name="tanggal_transaksi" value="<?= $selected_date ?>">
                        
                        <div id="field_khusus" class="hidden items-center mb-2">
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="checkbox" id="is_khusus" name="is_khusus" value="true" class="sr-only peer">
                                <div class="w-8 h-4 bg-white/10 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white/40 after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-blue-500 peer-checked:after:bg-white"></div>
                                <span class="ml-2 text-[9px] font-bold text-blue-300 uppercase tracking-tighter">Khusus (>500k)</span>
                            </label>
                        </div>

                        <button type="submit" id="submitTransaction" class="btn-primary px-6 py-2 rounded-lg text-white font-bold text-xs uppercase tracking-widest flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 self-end">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transactions List -->
            <div class="glass rounded-3xl p-4 md:p-5 flex flex-col flex-1 overflow-hidden border border-white/10 shadow-lg">
                <div class="flex justify-between items-center mb-3 shrink-0">
                    <h2 class="text-lg font-semibold uppercase tracking-widest">Semua Transaksi</h2>
                    <span class="text-white/50 text-xs"><?= count($transactions) ?> transaksi</span>
                </div>

                <div class="hidden md:grid grid-cols-5 gap-3 bg-white/5 rounded-lg px-3 py-2 mb-2 text-[9px] font-bold uppercase tracking-widest opacity-60 shrink-0">
                    <span>Tanggal</span>
                    <span>Nama</span>
                    <span class="text-right">Harga</span>
                    <span class="text-center">Tipe</span>
                    <span class="text-right">Aksi</span>
                </div>

                <div class="flex-1 overflow-y-auto trans-scroll space-y-2 pr-1">
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach($transactions as $t): 
                            if ($t['tipe_transaksi'] === 'masuk') {
                                $rowClass = 'bg-green-400/10 border-green-500/20 hover:bg-green-400/15';
                                $textClass = 'text-green-100';
                                $accentColor = '#22c55e';
                            } elseif ($t['tipe_transaksi'] === 'keluar') {
                                $rowClass = 'bg-red-400/10 border-red-500/20 hover:bg-red-400/15';
                                $textClass = 'text-red-100';
                                $accentColor = '#ef4444';
                            } else {
                                $rowClass = 'bg-yellow-400/10 border-yellow-500/20 hover:bg-yellow-400/15';
                                $textClass = 'text-yellow-100';
                                $accentColor = '#fbbf24';
                            }
                        ?>
                            <div class="flex flex-col md:grid md:grid-cols-5 md:gap-3 rounded-lg px-3 py-2 transition border text-xs items-start md:items-center <?= $rowClass ?>"
                                 data-transaction-id="<?= $t['id'] ?>">
                                
                                <div class="md:hidden text-white/50 mb-1 flex justify-between w-full items-center">
                                    <span><?= date('d/m/Y', strtotime($t['tanggal_transaksi'])) ?></span>
                                    <span class="text-[8px] px-1 py-0.5 bg-white/10 rounded text-white/60"><?= $t['type_display'] ?></span>
                                </div>

                                <span class="hidden md:inline text-white/70"><?= date('d/m/Y', strtotime($t['tanggal_transaksi'])) ?></span>
                                <div class="flex items-center gap-2">
                                    <?php if ($t['tipe_transaksi'] === 'cashbon' || $t['tipe_transaksi'] === 'bonus'): ?>
                                        <?php if (isset($t['profile']) && !empty($t['profile'])): ?>
                                            <img src="<?= base_url('profile/image/' . $t['profile']) ?>" class="w-6 h-6 rounded-full object-cover border border-white/20" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($t['username'] ?? 'U') ?>&background=random'">
                                        <?php else: ?>
                                            <div class="w-6 h-6 rounded-full bg-blue-500/30 flex items-center justify-center text-[10px] font-bold border border-white/20 text-blue-100">
                                                <?= strtoupper(substr($t['username'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center border border-white/20 <?= $t['tipe_transaksi'] === 'masuk' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' ?>">
                                            <i data-lucide="<?= $t['tipe_transaksi'] === 'masuk' ? 'plus-circle' : 'minus-circle' ?>" class="w-3 h-3"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="font-medium <?= $textClass ?>"><?= esc($t['nama_transaksi']) ?></span>
                                </div>
                                
                                <div class="md:text-right">
                                    <span class="font-semibold" style="color: <?= $accentColor ?>">
                                        <?= ($t['tipe_transaksi'] === 'masuk') ? '+ ' : '- ' ?>
                                        <?= number_format($t['tipe_transaksi'] === 'masuk' ? $t['harga_masuk'] : $t['harga'], 0, ',', '.') ?>
                                    </span>
                                </div>

                                <div class="hidden md:flex md:justify-center">
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 bg-white/5 rounded-full border border-white/10" style="color: <?= $accentColor ?>; border-color: <?= $accentColor ?>33">
                                        <?= ucfirst($t['tipe_transaksi']) ?>
                                    </span>
                                </div>

                                <div class="md:text-right">
                                    <?php if ($t['source'] !== 'payment'): ?>
                                        <button type="button" onclick="deleteTransaction('<?= $t['id'] ?>')" class="text-red-400 hover:text-red-300 text-xs font-semibold transition" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-white/40 text-[10px] uppercase tracking-tighter">Sistem</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-white/40">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                            <p class="text-sm">Belum ada transaksi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- Toast Notification -->
<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase">
    <span id="toastText"></span>
</div>

<script>
    lucide.createIcons();

    // Change report date
    function changeDate(date) {
        window.location.href = '<?= base_url('report') ?>?date=' + date;
    }

    // Set default tanggal form ke selected date
    const selectedDate = document.getElementById('reportDate').value;
    document.querySelector('input[name="tanggal_transaksi"]').value = selectedDate;

    // Get CSRF token from meta atau generate
    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '<?= csrf_hash() ?>';
    }

    // Save Modal Awal
    async function saveModalAwal() {
        const modalValue = parseInt(document.getElementById('modalAwalInput').value);
        const selectedDate = document.getElementById('reportDate').value;

        if (isNaN(modalValue) || modalValue < 0) {
            showToast('error', 'Modal tidak boleh negatif');
            return;
        }

        const formData = new FormData();
        formData.append('modal_awal', modalValue);
        formData.append('selected_date', selectedDate);
        formData.append('<?= csrf_token() ?>', getCsrfToken());

        try {
            const response = await fetch('<?= base_url('report/update-modal-awal') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('success', result.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', result.message || 'Gagal menyimpan modal awal');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Gagal menyimpan: ' + error.message);
        }
    }


    // Delete transaction
    async function deleteTransaction(id) {
        if (!confirm('Yakin ingin menghapus transaksi ini?')) return;

        try {
            const formData = new FormData();
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            const response = await fetch('<?= base_url('report/delete-transaction') ?>/' + id, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('success', result.message);
                document.querySelector(`[data-transaction-id="${id}"]`).style.opacity = '0';
                setTimeout(() => location.reload(), 500);
            } else {
                showToast('error', result.message || 'Gagal menghapus transaksi');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Terjadi kesalahan: ' + error.message);
        }
    }

    //初始化 chart kehadiran
    const attendanceStats = {
        total: <?= $attendance['total_users'] ?>,
        hadir: <?= $attendance['hadir'] ?>,
        sakit: <?= $attendance['sakit'] ?>,
        alfa: <?= $attendance['alfa'] ?>
    };

    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: ['Total Karyawan', 'Hadir', 'Sakit', 'Alfa'],
            datasets: [{
                label: 'Kehadiran (jumlah)',
                data: [attendanceStats.total, attendanceStats.hadir, attendanceStats.sakit, attendanceStats.alfa],
                backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444'],
                borderColor: ['#2563eb', '#16a34a', '#d97706', '#dc2626'],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Toast notification
    function showToast(type, msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastText').innerText = msg;
        toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
        toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
        setTimeout(() => toast.classList.add('hidden'), 2500);
    }
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

<script>
    // --- TRANSACTION LOGIC ---
    window.toggleTransactionFields = function(type) {
        const fieldNama = document.getElementById('field_nama');
        const fieldUser = document.getElementById('field_user');
        const fieldKhusus = document.getElementById('field_khusus');
        const select = document.getElementById('tipe_transaksi');

        // Reset and apply color to select
        select.classList.remove('border-green-500/50', 'border-red-500/50', 'border-yellow-500/50', 'bg-green-500/10', 'bg-red-500/10', 'bg-yellow-500/10');
        
        if (type === 'masuk') {
            select.classList.add('border-green-500/50', 'bg-green-500/10');
        } else if (type === 'keluar') {
            select.classList.add('border-red-500/50', 'bg-red-500/10');
        } else if (type === 'cashbon' || type === 'bonus') {
            select.classList.add('border-yellow-500/50', 'bg-yellow-500/10');
        }

        if (type === 'cashbon' || type === 'bonus') {
            fieldNama.classList.add('hidden');
            fieldUser.classList.remove('hidden');
            if (type === 'cashbon') {
                fieldKhusus.classList.remove('hidden');
                fieldKhusus.classList.add('flex');
            } else {
                fieldKhusus.classList.add('hidden');
                fieldKhusus.classList.remove('flex');
            }
        } else {
            fieldNama.classList.remove('hidden');
            fieldUser.classList.add('hidden');
            fieldKhusus.classList.add('hidden');
            fieldKhusus.classList.remove('flex');
        }
    }

    document.getElementById('transactionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitTransaction');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
        lucide.createIcons();

        try {
            const formData = new FormData(this);
            // Add CSRF if needed by the backend
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            const response = await fetch('<?= base_url('report/add-transaction') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const result = await response.json();

            if (result.status === 'success') {
                showToast('success', result.message);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('error', result.message || 'Gagal menyimpan transaksi');
            }
        } catch (error) {
            showToast('error', 'Terjadi kesalahan sistem');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            lucide.createIcons();
        }
    });

    if (typeof window.formatRupiahInput === 'undefined') {
        window.formatRupiahInput = function(el) {
            let val = el.value.replace(/[^0-9]/g, '');
            if (val === '') { el.value = ''; return; }
            el.value = new Intl.NumberFormat('id-ID').format(val);
        }
    }
</script>

<!-- Toast Notification -->
<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[1100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

</body>
</html>
