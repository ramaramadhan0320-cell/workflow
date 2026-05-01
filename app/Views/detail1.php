<!DOCTYPE html>
<html>
<head>
    <title>Detail task</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icon -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            background-image: url('/images/bg.jpg');
            background-size: cover;
            background-position: center;
        }

        .glass {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .status-pending { color: #d4a853; }
        .status-payment { color: #e74c3c; }
        .status-process { color: #e67e22; }
        .status-finishing { color: #008738; }
        .status-done { color: #005f9e; }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.5);
            border-radius: 10px;
        }
    </style>
</head>

<body class="h-screen flex overflow-hidden">

<main class="flex-1 p-6 overflow-hidden">

    <div class="glass rounded-3xl p-5 h-full flex flex-col overflow-hidden">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-4">

            <!-- BACK -->
            <a href="/dashboard" class="flex items-center gap-2 text-white text-2xl font-light hover:opacity-80">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
                <span>Task list</span>
            </a>

            <!-- DATE -->
            <span class="text-white text-xl"><?= date('d/m/Y') ?></span>

        </div>

        <!-- TABLE HEADER -->
        <div class="bg-white rounded-lg px-6 py-2 mb-2">
            <div class="grid grid-cols-5 gap-5 font-semibold text-gray-800">
                <span>Task Name</span>
                <span>Consumer</span>
                <span>Status</span>
                <span>Date</span>
                <span>Action</span>
            </div>
        </div>

        <!-- DATA -->
        <div class="px-6 flex-1 overflow-y-auto">
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

            <div class="grid grid-cols-5 gap-4 py-3 border-b border-white/30">
                <span class="text-white"><?= $t['task_name'] ?></span>
                <span class="text-white"><?= $t['consumer'] ?></span>
                <span class="<?= $statusClass ?> font-semibold">
                    <?= $t['status'] ?>
                </span>
                <span class="text-white">
                    <?= date('d/m/Y', strtotime($t['date_entry'])) ?>
                </span>

<button 
onclick="openModal(
    '<?= $t['id'] ?>',
    '<?= $t['task_name'] ?>',
    '<?= $t['consumer'] ?>',
    '<?= $t['status'] ?>',
    '<?= $t['date_entry'] ?>',
    '<?= basename($t['image'] ?? '') ?>'
)"
class="justify-self-start w-fit bg-white/90 hover:bg-white text-gray-800 px-4 py-1.5 rounded-full flex items-center gap-2 text-sm transition-all hover:scale-105 active:scale-95 shadow-md">
Detail
<i data-lucide="arrow-right" class="w-4 h-4"></i>
</button>
            </div>

            <?php endforeach; ?>

            <!-- ADD TASK -->
            <div class="flex justify-start mt-6">
                <div class="glass rounded-xl p-4 text-center hover:bg-white/20 transition cursor-pointer w-40">
                    <i data-lucide="badge-plus" class="w-10 h-10 text-white mx-auto mb-2"></i>
                    <span class="text-white text-sm">ADD Task</span>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- ================= MODAL ================= -->
<form id="formUpdate" enctype="multipart/form-data">

<div id="modal" 
     class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50 transition-all duration-200 opacity-0">

    <div class="bg-white rounded-3xl p-8 w-[420px] text-gray-800 relative shadow-2xl transform scale-95 transition-all duration-200">

        <!-- CLOSE -->
        <button type="button" onclick="closeModal()" 
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-lg">
            ✕
        </button>

        <h2 class="text-2xl font-semibold mb-6 flex items-center gap-2">
            <i data-lucide="file-text" class="w-6 h-6"></i>
            Edit Task
        </h2>

        <!-- HIDDEN ID -->
        <input type="hidden" id="task_id" name="id">

        <div class="space-y-4 text-sm">

            <!-- TASK -->
            <div>
                <label class="text-gray-500">Nama pesanan</label>
                <input type="text" id="m_task" name="task_name"
                    class="w-full border rounded px-3 py-2 mt-1">
            </div>

            <!-- CUSTOMER -->
            <div>
                <label class="text-gray-500">Customer</label>
                <input type="text" id="m_consumer" name="consumer"
                    class="w-full border rounded px-3 py-2 mt-1">
            </div>

            <!-- STATUS -->
            <div>
                <label class="text-gray-500">Status</label>
                <select id="m_status" name="status"
                    class="w-full border rounded px-3 py-2 mt-1">

                    <option value="pending">Pending</option>
                    <option value="payment pending">Payment Pending</option>
                    <option value="process">Process</option>
                    <option value="finishing">Finishing</option>
                    <option value="done">Done</option>

                </select>
            </div>

            <!-- DATE -->
            <div>
                <label class="text-gray-500">Date</label>
                <input type="date" id="m_date" name="date_entry"
                    class="w-full border rounded px-3 py-2 mt-1">
            </div>

        </div>

        <!-- UPLOAD IMAGE -->
        <div class="flex justify-center my-6">
            <label class="cursor-pointer text-center">
                <input type="file" name="image" class="hidden" onchange="previewImage(event)">
                
                <div class="w-28 h-28 bg-gray-100 rounded-xl flex items-center justify-center shadow-inner overflow-hidden">
                    <img id="preview" class="hidden w-full h-full object-cover">
                    <i id="iconImg" data-lucide="image" class="w-10 h-10 text-gray-500"></i>
                </div>

                <p class="text-xs text-gray-500 mt-2">Upload Image</p>
            </label>
        </div>

        <!-- BUTTON -->
        <div class="text-center">
            <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-2 rounded-full font-semibold transition">
                UPDATE
            </button>
        </div>

    </div>
</div>

</form>
    </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>
    lucide.createIcons();
document.addEventListener("DOMContentLoaded", function(){

    // ================= MODAL =================
window.openModal = function(id, task, consumer, status, date, image) {
    const modal = document.getElementById('modal');

    document.getElementById('task_id').value = id;
    document.getElementById('m_task').value = task;
    document.getElementById('m_consumer').value = consumer;
    document.getElementById('m_status').value = status;
    document.getElementById('m_date').value = date;

    const preview = document.getElementById('preview');
    const icon = document.getElementById('iconImg');

    if(image){
        preview.src = window.location.origin + '/image/' + image;
        preview.classList.remove('hidden');
        icon.classList.add('hidden');
    } else {
        preview.classList.add('hidden');
        icon.classList.remove('hidden');
    }

    modal.classList.remove('hidden');

    setTimeout(() => {
        modal.classList.remove('opacity-0', 'scale-95');
        modal.classList.add('opacity-100', 'scale-100');
    }, 10);
}

    // ================= PREVIEW IMAGE =================
    window.previewImage = function(event) {
        const file = event.target.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = function(e){
            document.getElementById('preview').src = e.target.result;
            document.getElementById('preview').classList.remove('hidden');
            document.getElementById('iconImg').classList.add('hidden');
        }

        reader.readAsDataURL(file);
    }

    // ================= SUBMIT =================
    const form = document.getElementById('formUpdate');

    if(form){
        form.addEventListener('submit', async function(e){
            e.preventDefault();

            try {
                let formData = new FormData(this);

                let response = await fetch('/task/update', {
                    method: 'POST',
                    body: formData
                });

                // cek response valid
                if (!response.ok) {
                    throw new Error("Server error: " + response.status);
                }

                let result = await response.json();

                if(result.status === 200){
                    alert('Update berhasil');
                    location.reload();
                } else {
                    alert(result.message || 'Gagal update');
                }

            } catch (error) {
                console.error(error);
                alert('Terjadi kesalahan koneksi / server');
            }
        });
    }

    window.closeModal = function() {
    const modal = document.getElementById('modal');

    modal.classList.add('opacity-0', 'scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
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