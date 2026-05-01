<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles Management</title>
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

        .user-scroll::-webkit-scrollbar { width: 4px; }
        .user-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .role-admin {
            background-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .role-karyawan {
            background-color: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .role-user {
            background-color: rgba(34, 197, 94, 0.2);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
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
            <a href="/roles" class="flex items-center gap-3 bg-white/10 p-2 rounded-lg transition"><i data-lucide="user-check"></i><span>Roles</span></a>
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
            <h1 class="text-xl md:text-2xl">Manajemen Roles & Users</h1>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <!-- Users Table -->
        <div class="glass rounded-3xl p-4 md:p-5 flex-1 flex flex-col overflow-hidden shadow-lg">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-medium">Daftar Users</h2>
                <button onclick="openAddUserModal()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition flex items-center gap-2 shadow-lg">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Tambah User Baru
                </button>
            </div>

            <div class="hidden sm:grid grid-cols-5 gap-4 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                <span>Username</span>
                <span>Role</span>
                <span>Status</span>
                <span>Gaji Total</span>
                <span class="text-right">Aksi</span>
            </div>

            <div class="overflow-y-auto user-scroll flex-1 pr-1">
                <?php if (empty($users)): ?>
                    <div class="flex items-center justify-center h-full">
                        <p class="text-white/40 text-center">Tidak ada users</p>
                    </div>
                <?php else: ?>
                    <?php foreach($users as $u): ?>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2 items-center">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($u['profile'])): ?>
                                    <img src="<?= base_url('profile/image/' . $u['profile']) ?>" class="w-8 h-8 rounded-lg object-cover border border-white/20 shadow-md">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center border border-white/10 text-xs font-bold text-white/40">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium"><?= esc($u['username']) ?></span>
                                </div>
                            </div>
                             <span class="hidden sm:flex">
                                <?php $displayRole = (!empty($u['role'])) ? $u['role'] : 'user'; ?>
                                <span class="role-badge role-<?= esc($displayRole) ?>"><?= esc($displayRole) ?></span>
                            </span>
                            <span class="hidden sm:block text-white/70 text-xs">
                                <?php if ($u['id'] === $user['id']): ?>
                                    <span class="text-green-400">Current</span>
                                <?php else: ?>
                                    <span class="text-white/40">Active</span>
                                <?php endif; ?>
                            </span>
                            <span class="hidden sm:block text-right text-sm text-white/80"><?= number_format($u['gaji_total'] ?? 0, 0, ',', '.') ?></span>
                            <div class="flex gap-2 justify-end flex-wrap sm:flex-nowrap">
                                <span class="sm:hidden role-badge role-<?= esc($displayRole) ?>"><?= esc($displayRole) ?></span>
                                <span class="sm:hidden text-xs text-white/70"><?= number_format($u['gaji_total'] ?? 0, 0, ',', '.') ?></span>
                                <button class="edit-btn bg-blue-500/50 hover:bg-blue-600 p-2 rounded transition" data-id="<?= $u['id'] ?>" data-username="<?= esc($u['username']) ?>" data-role="<?= esc($u['role']) ?>" data-gaji="<?= esc($u['gaji_total'] ?? 0) ?>" title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <?php if ($u['id'] !== $user['id']): ?>
                                    <button class="delete-btn bg-red-500/50 hover:bg-red-600 p-2 rounded transition" data-id="<?= $u['id'] ?>" data-username="<?= esc($u['username']) ?>" title="Hapus">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="p-2 text-white/30 cursor-not-allowed" title="Tidak dapat menghapus akun sendiri">
                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<script>
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
</script>

<!-- Add User Modal -->
<form id="formAddUser" enctype="multipart/form-data">
    <div id="addUserModal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50 transition-all duration-200 opacity-0">
        <div class="bg-white rounded-3xl p-6 md:p-8 w-[90%] max-w-[400px] text-gray-800 relative shadow-2xl transform scale-95 transition-all duration-200">
            <button type="button" onclick="closeAddUserModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">✕</button>
            <h2 id="modalTitle" class="text-xl font-semibold mb-6">Tambah User Baru</h2>
            <input type="hidden" name="user_id" id="user_id" value="">
            
            <div class="space-y-4 text-sm">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Username</label>
                    <input type="text" name="username" id="username" required minlength="3" maxlength="50" 
                        class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500" 
                        placeholder="Username">
                    <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                </div>

                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Password</label>
                    <input type="password" id="password" name="password" minlength="6" 
                        class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500" 
                        placeholder="Minimal 6 karakter (kosongkan jika tidak ingin mengubah)">
                    <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                </div>

                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Role</label>
                    <select name="role" id="role" required 
                        class="w-full border rounded-xl px-2 py-2 mt-1 outline-none focus:border-blue-500">
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">Admin</option>
                        <option value="karyawan">Karyawan</option>
                        <option value="user">User</option>
                    </select>
                    <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                </div>

                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Gaji Total</label>
                    <input type="number" name="gaji_total" id="gaji_total" required min="0" step="1000"
                        class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500"
                        placeholder="Masukkan gaji total">
                    <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                </div>
            </div>

            <button id="submitBtn" type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition mt-6">
                Save Changes
            </button>
        </div>
    </div>
</form>

<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<script>
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Sidebar Toggle ---
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

        // --- Toast Notification ---
        window.showToast = function(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastText = document.getElementById('toastText');
            toastText.textContent = message;
            toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 px-5 py-3 rounded-xl shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest ' + 
                (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        };

        // --- Modal Functions ---
        function clearFormErrors() {
            document.querySelectorAll('#formAddUser .error-message').forEach(el => {
                el.textContent = '';
                el.classList.add('hidden');
            });
        }

        window.openAddUserModal = function() {
            const modal = document.getElementById('addUserModal');
            document.getElementById('modalTitle').textContent = 'Tambah User Baru';
            document.getElementById('user_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('role').value = '';
            document.getElementById('gaji_total').value = '';
            clearFormErrors();

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.bg-white').classList.remove('scale-95');
            }, 10);
        };

        window.openEditUserModal = function(id, username, role, gajiTotal) {
            const modal = document.getElementById('addUserModal');
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('user_id').value = id;
            document.getElementById('username').value = username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('role').value = role;
            document.getElementById('gaji_total').value = gajiTotal;
            clearFormErrors();

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.bg-white').classList.remove('scale-95');
            }, 10);
        };

        window.closeAddUserModal = function() {
            const modal = document.getElementById('addUserModal');
            modal.classList.add('opacity-0');
            modal.querySelector('.bg-white').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        };

        // Close modal when clicking outside
        document.getElementById('addUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddUserModal();
        });

        // --- Form Submission ---
        document.getElementById('formAddUser').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin inline-block mr-2"></i> Menyimpan...';

            try {
                const formData = new FormData(this);
                const userId = formData.get('user_id');
                const endpoint = userId ? '/roles/update' : '/roles/store';

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showToast('User berhasil ditambahkan!', 'success');
                    closeAddUserModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = document.querySelector(`input[name="${field}"], select[name="${field}"]`);
                            if (input) {
                                const errorEl = input.parentElement.querySelector('.error-message');
                                if (errorEl) {
                                    errorEl.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                                    errorEl.classList.remove('hidden');
                                }
                            }
                        });
                        showToast('Terdapat kesalahan validasi', 'error');
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                }
            } catch (err) {
                showToast('Terjadi kesalahan: ' + err.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                lucide.createIcons();
            }
        });

        // --- Edit User ---
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const username = this.dataset.username;
                const role = this.dataset.role;
                const gajiTotal = this.dataset.gaji;
                openEditUserModal(id, username, role, gajiTotal);
            });
        });

        // --- Delete User ---
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;

                if (!confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`)) {
                    return;
                }

                try {
                    const response = await fetch(`/roles/delete/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('User berhasil dihapus!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan: ' + err.message, 'error');
                }
            });
        });

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
