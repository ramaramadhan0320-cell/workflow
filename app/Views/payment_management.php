<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management</title>
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

        /* Custom Scrollbar untuk Payment List */
        .payment-scroll::-webkit-scrollbar { width: 4px; }
        .payment-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        /* Enhanced ACC Toggle Switch Styling */
        .slip-checkbox:focus-visible {
            outline: 2px solid rgba(76, 175, 80, 0.5);
            outline-offset: 2px;
        }

        /* Toggle Track Container - Smooth color transition */
        .slip-checkbox + div {
            background-color: rgba(75, 85, 99, 0.6);
            transition: background-color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .slip-checkbox:checked + div {
            background-color: rgba(34, 197, 94, 0.8);
        }

        /* Toggle Button/Knob - Main animation */
        .slip-checkbox + div > div {
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        box-shadow 0.3s ease-out;
            transform: translateX(0);
        }

        .slip-checkbox:checked + div > div {
            transform: translateX(1rem);
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.5);
        }

        /* Hover effect for toggle track */
        .slip-checkbox + div {
            cursor: pointer;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0);
            transition: background-color 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                        box-shadow 0.3s ease;
        }

        .slip-checkbox:hover + div {
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.3);
        }

        .slip-checkbox:checked:hover + div {
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.4);
        }

        /* Checkmark indicator animation */
        .slip-checkbox + div ~ span.absolute {
            transition: opacity 0.3s ease-in-out;
            opacity: 0;
        }

        .slip-checkbox:checked + div ~ span.absolute {
            opacity: 1;
        }

        /* Pulse animation pada toggle */
        @keyframes togglePulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .slip-checkbox:checked {
            animation: togglePulse 0.6s ease-out 0.1s;
        }

        /* Smooth cursor change */
        .slip-checkbox {
            cursor: pointer;
        }

        label.relative {
            user-select: none;
        }
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
            <a href="/announcement" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="megaphone"></i><span>Announcement</span></a>
            <a href="/bank-file" class="flex items-center gap-3 hover:bg-white/10 p-2 rounded-lg transition"><i data-lucide="folder"></i><span>Bank File</span></a>
            <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
            <a href="/payment-management" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="banknote"></i><span>Management Payment</span></a>
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
            <h1 class="text-xl md:text-2xl">Payment Management</h1>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <div class="glass rounded-3xl p-4 md:p-5 flex-1 flex flex-col overflow-hidden shadow-lg">
            <div class="flex justify-between items-center mb-3 shrink-0">
                <h2 class="text-lg font-medium">Employee Payment Overview</h2>
                <div class="flex items-center gap-3">
                    <div class="text-xs text-white/60 hidden sm:block">
                        <div class="flex items-center gap-4">
                            
                        </div>
                    </div>
                    <div class="text-xs text-white/60">
                        Withdrawal Date: <span class="font-medium text-white/80">
                            <?php echo !empty($paymentData) ? $paymentData[0]['withdrawal_date'] : date('d/m/Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="hidden sm:grid sm:grid-cols-[1.2fr_1fr_1fr_1fr_1fr_1.1fr_1.3fr] gap-4 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                <span>Employee</span>
                <span>Base Salary</span>
                <span>Deductions</span>
                <span>Bonus</span>
                <span>Cashbon</span>
                <span class="text-right pr-4">Net Salary</span>
                <div class="text-right">
                    <span class="text-[9px] block mb-1">Actions</span>
                </div>
            </div>

            <div class="overflow-y-auto payment-scroll flex-1 pr-1">
                <?php if (empty($paymentData)): ?>
                    <div class="text-center py-12 text-white/40">
                        <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                        <p class="text-sm">No employee data available</p>
                    </div>
                <?php else: ?>
                    <?php foreach($paymentData as $pd): ?>
                        <div class="grid grid-cols-2 sm:grid-cols-[1.2fr_1fr_1fr_1fr_1fr_1.1fr_1.3fr] gap-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2 items-center">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium"><?= esc($pd['user']['username']) ?></span>
                                <span class="text-white/40 text-[9px] sm:hidden">
                                    Attendance: <?= $pd['kehadiran_count'] ?> days
                                </span>
                            </div>
                            <div class="hidden sm:flex flex-col">
                                <span class="text-white/70 text-xs"><?= number_format($pd['user']['gaji_total'] ?? 0, 0, ',', '.') ?></span>
                                <span class="text-white/40 text-[9px]">Base Salary</span>
                            </div>
                            <div class="hidden sm:flex flex-col">
                                <span class="text-red-400 text-xs">-<?= number_format($pd['total_potongan'], 0, ',', '.') ?></span>
                                <span class="text-white/40 text-[9px]"><?= $pd['kehadiran_count'] ?> days</span>
                            </div>
                            <div class="hidden sm:flex flex-col">
                                <span class="text-green-400 text-xs">+<?= number_format($pd['total_bonus'], 0, ',', '.') ?></span>
                                <span class="text-white/40 text-[9px]">Bonus</span>
                            </div>
                            <div class="hidden sm:flex flex-col">
                                <span class="text-orange-400 text-xs">-<?= number_format($pd['total_cashbon'], 0, ',', '.') ?></span>
                                <span class="text-white/40 text-[9px]">Cashbon</span>
                            </div>
                            <div class="flex flex-col items-end pr-4">
                                <span class="text-white font-medium text-sm"><?= number_format($pd['gaji_bersih'], 0, ',', '.') ?></span>
                                <span class="text-white/40 text-[9px]">Net Salary</span>
                            </div>
                            <div class="flex gap-2 justify-end sm:justify-end items-end sm:items-center flex-nowrap">
                                <?php
                                $slipGajiModel = new \App\Models\SlipGajiModel();
                                $hasSlip = $slipGajiModel->hasSentSlip($pd['user']['id']);
                                $isInAcc = $pd['is_in_acc'] ?? false;
                                if ($hasSlip):
                                ?>
                                    <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                        <label class="relative inline-flex items-center cursor-pointer group">
                                            <input type="checkbox"
                                                   class="slip-checkbox sr-only peer"
                                                   value="<?= $pd['user']['id'] ?>"
                                                   id="slip-check-<?= $pd['user']['id'] ?>"
                                                   data-username="<?= esc($pd['user']['username']) ?>"
                                                   title="ACC Pembayaran - Pindahkan slip ke folder ACC"
                                                   <?= $isInAcc ? 'checked' : '' ?>>
                                            <div class="w-8 h-4 bg-gray-600/60 rounded-full transition-colors duration-400 flex items-center p-0.5 relative">
                                                <div class="w-3 h-3 bg-white rounded-full shadow-md transform transition-transform duration-400 ease-in-out absolute left-0.5 flex items-center justify-center text-[7px] font-bold">
                                                    <span class="text-gray-700">−</span>
                                                </div>
                                            </div>
                                            <!-- Checkmark indicator for checked state -->
                                            <span class="absolute left-2 pointer-events-none hidden text-[10px] font-bold text-white">✓</span>
                                        </label>
                                        <span class="text-white/60 text-[7px] font-bold uppercase tracking-widest whitespace-nowrap">ACC</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                        <button onclick="viewSlipGaji(<?= $pd['user']['id'] ?>, '<?= esc($pd['user']['username']) ?>')"
                                                class="bg-blue-500/80 hover:bg-blue-600/80 text-white p-1.5 rounded-lg transition-all duration-200 hover:scale-105 shadow-lg <?= $isInAcc ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                                title="<?= $isInAcc ? 'Preview disabled - File in ACC' : 'Preview Slip Gaji - Lihat isi file PDF' ?>"
                                                id="slip-btn-<?= $pd['user']['id'] ?>"
                                                <?= $isInAcc ? 'disabled' : '' ?>>
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <span class="text-white/60 text-[7px] font-bold uppercase tracking-widest whitespace-nowrap">Preview</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                        <button onclick="flushSlipData(<?= $pd['user']['id'] ?>, '<?= esc($pd['user']['username']) ?>')"
                                                class="bg-red-500/80 hover:bg-red-600/80 text-white p-1.5 rounded-lg transition-all duration-200 hover:scale-105 shadow-lg <?= !$isInAcc ? 'opacity-30 cursor-not-allowed' : '' ?>"
                                                title="<?= !$isInAcc ? 'Pindahkan ke ACC terlebih dahulu sebelum flush' : 'Flush Database - Hapus data dari database, PDF tetap di ACC sebagai jejak' ?>"
                                                id="flush-btn-<?= $pd['user']['id'] ?>"
                                                <?= !$isInAcc ? 'disabled' : '' ?>>
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <span class="text-white/60 text-[7px] font-bold uppercase tracking-widest whitespace-nowrap">Flush</span>
                                    </div>
                                <?php else: ?>
                                    <!-- EXPERIMENTAL: Unlock Withdrawal Button - SHOW ONLY IF NO SLIP DATA -->
                                    <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                        <button onclick="bypassWithdrawal(<?= $pd['user']['id'] ?>, '<?= esc($pd['user']['username']) ?>')"
                                                class="bg-yellow-500/80 hover:bg-yellow-600/80 text-white p-1.5 rounded-lg transition-all duration-200 hover:scale-105 shadow-lg"
                                                title="[EXPERIMENTAL] Bypass withdrawal lock - Aktifkan pencairan tanpa syarat"
                                                id="bypass-btn-<?= $pd['user']['id'] ?>">
                                            <i data-lucide="unlock" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <span class="text-yellow-400/80 text-[7px] font-bold uppercase tracking-widest whitespace-nowrap">Bypass</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<!-- SLIP PREVIEW MODAL -->
<div id="slipModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-md z-[500] flex items-center justify-center">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-[95%] max-w-6xl h-[90%] max-h-[900px] flex flex-col relative overflow-hidden transform transition-all duration-300">
        <!-- Close Button -->
        <button onclick="closeSlipModal()" class="absolute top-6 right-6 p-2 text-gray-400 hover:text-gray-800 transition-colors z-10">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <!-- Header -->
        <div class="p-8 border-b border-gray-100">
            <h2 id="slipModalTitle" class="text-2xl font-bold text-[#1a1f36]">Preview Slip Gaji</h2>
        </div>

        <!-- PDF Viewer -->
        <div class="flex-1 p-8 bg-gray-50/50">
            <embed id="pdfEmbed" src="" type="application/pdf" class="w-full h-full rounded-2xl shadow-inner border border-gray-200">
        </div>

        <!-- Footer -->
        <div class="p-8 flex justify-between items-center bg-white border-t border-gray-100">
            <div id="pdfStatus" class="text-sm font-medium text-gray-400">Loading PDF...</div>
            <div class="flex gap-4">
                <button onclick="downloadSlipFromModal()" class="bg-[#1a1f36] hover:bg-black text-white px-8 py-3.5 rounded-2xl transition-all duration-200 flex items-center gap-2 font-bold uppercase tracking-wider text-xs">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed top-6 left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<!-- EXPERIMENTAL: Bypass Notification Modal -->
<div id="bypassModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-md z-[500] flex items-center justify-center">
    <div class="bg-white rounded-[2.5rem] p-8 w-[95%] max-w-md shadow-2xl relative transform transition-all duration-300">
        <!-- Close Button -->
        <button onclick="closeBypassModal()" class="absolute top-6 right-6 p-2 text-gray-400 hover:text-gray-800 transition-colors">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <h3 id="bypassModalTitle" class="text-2xl font-bold text-[#1a1f36] mb-8 mt-2">Status Bypass</h3>

        <!-- Details -->
        <div id="bypassModalDetails" class="bg-gray-50 rounded-2xl p-6 mb-8 space-y-4">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Employee</span>
                <span id="bypassModalUser" class="text-[#1a1f36] font-semibold text-lg">-</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Durasi</span>
                <span id="bypassModalDuration" class="text-indigo-600 font-semibold">10 menit</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</span>
                <span id="bypassModalStatus" class="text-green-500 font-bold">✓ Aktif</span>
            </div>
        </div>

        <p id="bypassModalMessage" class="text-gray-500 mb-8 text-sm leading-relaxed px-1">
            Pencairan telah dibuka untuk user ini tanpa syarat tanggal dan kehadiran.
        </p>

        <!-- Close Button -->
        <button onclick="closeBypassModal()" class="w-full bg-[#1a1f36] hover:bg-black text-white font-bold py-4 rounded-2xl transition-all duration-200 uppercase tracking-[0.2em] text-xs">
            Tutup
        </button>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-md z-[600] flex items-center justify-center">
    <div class="bg-white rounded-[2.5rem] p-8 w-[95%] max-w-md shadow-2xl relative transform transition-all duration-300 scale-95 opacity-0" id="confirmModalContent">
        <!-- Close Button -->
        <button id="confirmCloseIcon" class="absolute top-6 right-6 p-2 text-gray-400 hover:text-gray-800 transition-colors">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <h3 id="confirmModalTitle" class="text-2xl font-bold text-[#1a1f36] mb-6 mt-2">Konfirmasi</h3>
        
        <p id="confirmModalMessage" class="text-gray-500 mb-10 text-base leading-relaxed"></p>
        
        <div class="flex flex-col gap-3">
            <button id="confirmOkBtn" class="w-full bg-[#1a1f36] hover:bg-black text-white font-bold py-4 rounded-2xl transition-all duration-200 uppercase tracking-[0.2em] text-xs shadow-lg shadow-gray-200">
                Ya, Lanjutkan
            </button>
            <button id="confirmCancelBtn" class="w-full bg-transparent hover:bg-gray-50 text-gray-400 font-bold py-3 rounded-xl transition-all duration-200 uppercase tracking-widest text-[10px]">
                Batalkan
            </button>
        </div>
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

    // Global slip data storage
    window.currentSlipData = null;

    // --- Checkbox Management ---
    function updateMoveButton() {
        // Removed - no longer needed
    }

    // Handle checkbox toggle for auto-move functionality
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('slip-checkbox')) {
            // Update toggle button position
            const label = e.target.closest('label');
            if (label) {
                const toggleTrack = label.querySelector('div');
                const toggleButton = toggleTrack ? toggleTrack.querySelector('div') : null;
                const checkmarkIndicator = label.querySelector('span.absolute');
                
                if (toggleButton) {
                    if (e.target.checked) {
                        toggleTrack.style.backgroundColor = 'rgba(34, 197, 94, 0.8)';
                        toggleButton.style.transform = 'translateX(1rem)';
                        if (checkmarkIndicator) checkmarkIndicator.classList.remove('hidden');
                    } else {
                        toggleTrack.style.backgroundColor = 'rgba(75, 85, 99, 0.6)';
                        toggleButton.style.transform = 'translateX(0)';
                        if (checkmarkIndicator) checkmarkIndicator.classList.add('hidden');
                    }
                }
            }
            
            handleSlipToggle(e.target);
        }
    });

    // --- Auto Move/Unmove Slips ---
    async function handleSlipToggle(checkbox) {
        const userId = checkbox.value;
        const username = checkbox.dataset.username;
        const isChecked = checkbox.checked;

        // Disable checkbox during processing
        checkbox.disabled = true;

        try {
            if (isChecked) {
                // Move to ACC folder
                showToast('Memproses file...', 'info');

                const response = await fetch(`/payment-management/move-slip-to-acc?user_id=${userId}`);
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(`File ${username} telah di ACC`, 'success');
                    // Disable preview button when moved to ACC
                    togglePreviewButton(userId, true);
                } else {
                    showToast(`Gagal ACC file ${username}`, 'error');
                    checkbox.checked = false; // Revert on failure
                }
            } else {
                // Move back to Report folder
                showToast('Mengembalikan file...', 'info');

                const response = await fetch(`/payment-management/move-slip-from-acc?user_id=${userId}`);
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(`File ${username} dikembalikan`, 'success');
                    // Enable preview button when moved back to Report
                    togglePreviewButton(userId, false);
                } else {
                    showToast(`Gagal kembalikan file ${username}`, 'error');
                    checkbox.checked = true; // Revert on failure
                }
            }

        } catch (error) {
            console.error('Error toggling slip:', error);
            showToast('Error memproses file', 'error');
            // Revert checkbox state on error
            checkbox.checked = !isChecked;
        } finally {
            // Re-enable checkbox
            checkbox.disabled = false;
            // Update flush button state
            updateFlushButtonState(checkbox);
        }
    }

    // --- Update Flush Button State ---
    function updateFlushButtonState(checkbox) {
        const userId = checkbox.value;
        const flushBtn = document.getElementById(`flush-btn-${userId}`);
        
        if (!flushBtn) return;
        
        if (checkbox.checked) {
            // Enable flush button when checkbox is checked
            flushBtn.disabled = false;
            flushBtn.classList.remove('opacity-30', 'cursor-not-allowed');
            flushBtn.classList.add('hover:bg-red-600/80', 'cursor-pointer');
            flushBtn.title = 'Flush database - PDF akan tetap di ACC sebagai jejak';
        } else {
            // Disable flush button when checkbox is unchecked
            flushBtn.disabled = true;
            flushBtn.classList.add('opacity-30', 'cursor-not-allowed');
            flushBtn.classList.remove('hover:bg-red-600/80', 'cursor-pointer');
            flushBtn.title = 'Pindahkan ke ACC terlebih dahulu sebelum flush';
        }
    }

    // --- Toggle Preview Button ---
    function togglePreviewButton(userId, disable) {
        const buttonId = `slip-btn-${userId}`;
        const button = document.getElementById(buttonId);

        if (!button) return;

        if (disable) {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.title = 'Preview disabled - File in ACC';
        } else {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.title = 'View Slip Gaji';
        }
    }

    // --- View Slip Gaji ---
    async function viewSlipGaji(userId, username) {
        const buttonId = `slip-btn-${userId}`;
        const button = document.getElementById(buttonId);

        if (!button) {
            showToast('Button tidak ditemukan', 'error');
            return;
        }

        // Store original content
        const originalContent = button.innerHTML;

        try {
            // Show loading state
            button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');

            showToast('Memuat PDF...', 'info');

            // Step 1: Get slip data
            const dataResponse = await fetch(`/payment-management/get-slip-data?user_id=${userId}`);
            const dataResult = await dataResponse.json();

            if (!dataResponse.ok || !dataResult.success) {
                throw new Error(dataResult.message || 'Gagal mengambil data slip');
            }

            const filename = dataResult.filename;
            if (!filename) {
                throw new Error('Filename tidak tersedia');
            }

            // Store for download function
            window.currentSlipData = { userId, filename, username };

            // Step 2: Get PDF as base64
            const pdfResponse = await fetch(`/payment-management/get-slip-pdf-base64?user_id=${userId}&filename=${encodeURIComponent(filename)}`);
            const pdfResult = await pdfResponse.json();

            if (!pdfResponse.ok || pdfResult.status !== 200 || !pdfResult.data?.base64) {
                throw new Error(pdfResult.message || 'Gagal memuat PDF');
            }

            // Step 3: Display PDF in modal
            const dataUri = `data:application/pdf;base64,${pdfResult.data.base64}`;
            const pdfEmbed = document.getElementById('pdfEmbed');
            const modalTitle = document.getElementById('slipModalTitle');
            const pdfStatus = document.getElementById('pdfStatus');

            pdfEmbed.src = dataUri;
            modalTitle.textContent = `Slip Gaji - ${username}`;
            pdfStatus.textContent = `PDF Dimuat - ${filename}`;

            // Show modal
            document.getElementById('slipModal').classList.remove('hidden');

            // Ensure sidebar is closed on mobile when modal opens
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar && overlay) {
                sidebar.classList.add('sidebar-closed');
                sidebar.classList.remove('sidebar-open');
                overlay.classList.add('hidden');
            }

            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';

            showToast('PDF berhasil dimuat!', 'success');

        } catch (error) {
            console.error('Error viewing slip gaji:', error);
            showToast(`Error: ${error.message}`, 'error');
        } finally {
            // Reset button state
            if (button) {
                button.innerHTML = originalContent;
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    function closeSlipModal() {
        document.getElementById('slipModal').classList.add('hidden');
        document.getElementById('pdfEmbed').src = '';
        // Clear stored data
        window.currentSlipData = null;
        // Restore body scroll
        document.body.style.overflow = '';
    }

    function downloadSlipFromModal() {
        if (!window.currentSlipData || !window.currentSlipData.filename) {
            showToast('Data slip tidak tersedia', 'error');
            return;
        }

        const { userId, filename } = window.currentSlipData;
        const downloadUrl = `/payment-management/download-slip?user_id=${userId}&filename=${encodeURIComponent(filename)}`;

        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showToast('Download dimulai...', 'success');
    }

    // --- Flush Slip Data ---
    async function flushSlipData(userId, username) {
        showCustomConfirm(
            "Flush Data",
            `Yakin flush data payment untuk ${username}?`,
            async () => {
                showToast('Memproses flush...', 'info');
                try {
                    const response = await fetch(`/payment-management/flush-slip-data?user_id=${userId}`);
                    const result = await response.json();

                    if (response.ok && result.success) {
                        showToast(`Data ${username} berhasil di-flush`, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(`Gagal flush data: ${result.message || 'Unknown error'}`, 'error');
                    }
                } catch (error) {
                    console.error('Error flushing slip data:', error);
                    showToast(`Error: ${error.message}`, 'error');
                }
            }
        );
    }

    // --- EXPERIMENTAL: Bypass Withdrawal Lock ---
    async function bypassWithdrawal(userId, username) {
        showCustomConfirm(
            "[EXPERIMENTAL]",
            `Aktifkan bypass pencairan untuk:<br><br><span class='text-yellow-400 font-bold'>${username}</span><br><br>Bypass berlaku 10 menit.<br>User bisa cairkan tanpa syarat.`,
            async () => {
                const btn = document.getElementById(`bypass-btn-${userId}`);
                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                }

                try {
                    const response = await fetch(`/payment-management/experimental-bypass-withdrawal?user_id=${userId}`);
                    const result = await response.json();

                    if (response.ok && result.success) {
                        showBypassSuccessModal(result.username, result.expires_in_minutes, userId);
                        
                        if (btn) {
                            btn.classList.add('ring-2', 'ring-yellow-300', 'ring-offset-2');
                            btn.style.opacity = '1';
                            btn.title = `Bypass aktif - Expires in: ${result.expires_in_minutes} menit`;
                            
                            setTimeout(() => {
                                if (btn) {
                                    btn.classList.remove('ring-2', 'ring-yellow-300', 'ring-offset-2');
                                    btn.disabled = false;
                                    btn.title = '[EXPERIMENTAL] Bypass withdrawal lock - Aktifkan pencairan tanpa syarat';
                                }
                            }, result.expires_in_minutes * 60 * 1000);
                        }
                    } else {
                        showBypassErrorModal(result.message || 'Unknown error', username);
                        if (btn) {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                        }
                    }
                } catch (error) {
                    console.error('Error activating bypass:', error);
                    showBypassErrorModal(error.message, username);
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                }
            }
        );
    }

    function showBypassSuccessModal(username, expiresInMinutes, userId) {
        const modal = document.getElementById('bypassModal');
        const icon = document.getElementById('bypassModalIcon');
        const title = document.getElementById('bypassModalTitle');
        const message = document.getElementById('bypassModalMessage');
        const userEl = document.getElementById('bypassModalUser');
        const duration = document.getElementById('bypassModalDuration');
        const status = document.getElementById('bypassModalStatus');

        // Update content
        icon.innerHTML = '<i data-lucide="check-circle" class="w-8 h-8 text-green-400"></i>';
        icon.className = 'w-16 h-16 rounded-full flex items-center justify-center bg-green-500/20';
        title.textContent = '✓ Bypass Aktif';
        title.className = 'text-2xl font-bold text-center text-green-400 mb-2';
        message.textContent = `Pencairan telah dibuka untuk user ini tanpa syarat tanggal dan kehadiran.`;
        userEl.textContent = username;
        duration.textContent = `${expiresInMinutes} menit`;
        duration.className = 'text-yellow-400 font-medium';
        status.textContent = '✓ Aktif';
        status.className = 'text-green-400 font-medium';

        // Show modal
        modal.classList.remove('hidden');

        // Update Lucide icons
        lucide.createIcons();
    }

    function showBypassErrorModal(errorMessage, username) {
        const modal = document.getElementById('bypassModal');
        const icon = document.getElementById('bypassModalIcon');
        const title = document.getElementById('bypassModalTitle');
        const message = document.getElementById('bypassModalMessage');
        const userEl = document.getElementById('bypassModalUser');
        const duration = document.getElementById('bypassModalDuration');
        const status = document.getElementById('bypassModalStatus');

        // Update content for error
        icon.innerHTML = '<i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>';
        icon.className = 'w-16 h-16 rounded-full flex items-center justify-center bg-red-500/20';
        title.textContent = '✗ Bypass Gagal';
        title.className = 'text-2xl font-bold text-center text-red-400 mb-2';
        message.textContent = errorMessage;
        userEl.textContent = username;
        duration.textContent = '-';
        duration.className = 'text-white/60';
        status.textContent = '✗ Gagal';
        status.className = 'text-red-400 font-medium';

        // Show modal
        modal.classList.remove('hidden');

        // Update Lucide icons
        lucide.createIcons();
    }

    function closeBypassModal() {
        const modal = document.getElementById('bypassModal');
        modal.classList.add('hidden');
    }

    // --- Custom Confirmation Logic ---
    function showCustomConfirm(title, message, onConfirm) {
        const modal = document.getElementById('confirmModal');
        const content = document.getElementById('confirmModalContent');
        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');
        const okBtn = document.getElementById('confirmOkBtn');
        const cancelBtn = document.getElementById('confirmCancelBtn');
        const closeIcon = document.getElementById('confirmCloseIcon');

        titleEl.textContent = title;
        messageEl.innerHTML = message;

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);

        const closeModal = () => {
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        };

        okBtn.onclick = () => {
            closeModal();
            onConfirm();
        };

        cancelBtn.onclick = closeModal;
        closeIcon.onclick = closeModal;
        modal.onclick = (e) => {
            if (e.target === modal) closeModal();
        };
    }

    // Initialize Lucide icons after DOM load
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Initialize toggles with correct state
        const checkboxes = document.querySelectorAll('.slip-checkbox');
        checkboxes.forEach(checkbox => {
            const label = checkbox.closest('label');
            if (label) {
                const toggleTrack = label.querySelector('div.w-8');
                const toggleButton = toggleTrack ? toggleTrack.querySelector('div.w-3') : null;
                const checkmarkIndicator = label.querySelector('span.absolute');
                
                if (checkbox.checked && toggleButton) {
                    toggleTrack.style.backgroundColor = 'rgba(34, 197, 94, 0.8)';
                    toggleButton.style.transform = 'translateX(1rem)';
                    if (checkmarkIndicator) checkmarkIndicator.classList.remove('hidden');
                } else if (toggleButton) {
                    toggleTrack.style.backgroundColor = 'rgba(75, 85, 99, 0.6)';
                    toggleButton.style.transform = 'translateX(0)';
                    if (checkmarkIndicator) checkmarkIndicator.classList.add('hidden');
                }
            }
        });
    });

    // --- Toast/Alert Functions ---
    window.showToast = function(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastText = document.getElementById('toastText');

        if (!toast || !toastText) {
            console.warn('Toast elements not found');
            return;
        }

        toastText.textContent = message;

        // Remove existing type classes
        toast.classList.remove('bg-green-500', 'bg-red-500', 'bg-blue-500', 'text-white');

        // Add appropriate styling based on type
        let bgClass = 'bg-green-500';
        if (type === 'error') bgClass = 'bg-red-500';
        else if (type === 'info') bgClass = 'bg-blue-500';

        toast.className = `fixed top-6 left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest ${bgClass} text-white`;

        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };
</script>

</body>
</html>
