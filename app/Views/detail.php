<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Task</title>
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

        .status-pending { color: #d4a853; }
        .status-payment { color: #e74c3c; }
        .status-process { color: #e67e22; }
        .status-finishing { color: #008738; }
        .status-done { color: #005f9e; }

        .task-scroll::-webkit-scrollbar { width: 4px; }
        .task-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        /* Styling untuk disabled inputs */
        input:disabled, select:disabled {
            background-color: #f3f4f6 !important;
            opacity: 0.6;
            cursor: not-allowed;
        }

        input:disabled::placeholder {
            color: #9ca3af;
        }
    </style>
</head>

<body class="h-screen flex flex-col md:flex-row text-white font-light">

<main class="flex-1 p-4 md:p-6 h-screen flex flex-col overflow-hidden">

    <div class="glass rounded-[32px] p-5 md:p-8 flex-1 flex flex-col overflow-hidden">

        <div class="flex justify-between items-center mb-6 shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('dashboard') ?>" class="flex items-center gap-2 text-white/70 hover:text-white transition">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    <h1 class="text-xl md:text-2xl">Task List</h1>
                </a>
            </div>
            <span id="currentDate" class="text-white/70 text-sm">--/--/----</span>
        </div>

        <div class="glass rounded-3xl p-4 md:p-5 mb-8 flex flex-col flex-1 overflow-hidden shadow-lg">
            
            <div class="hidden sm:grid grid-cols-5 gap-4 bg-white/5 rounded-xl px-4 py-2 mb-2 text-[10px] font-bold uppercase tracking-widest opacity-60">
                <span>Task</span>
                <span>Consumer</span>
                <span>Status</span>
                <span>Date</span>
                <span class="text-right">Action</span>
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
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 py-3 border-b border-white/5 hover:bg-white/5 transition px-2 items-center">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium"><?= $t['task_name'] ?></span>
                            <span class="text-white/40 text-[9px] sm:hidden"><?= $t['consumer'] ?></span>
                        </div>
                        
                        <span class="hidden sm:block text-white/70 text-xs"><?= $t['consumer'] ?></span>
                        
                        <span class="<?= $statusClass ?> text-[10px] font-bold uppercase tracking-tighter flex items-center">
                            ● <?= $t['status'] ?>
                        </span>
                        
                        <span class="hidden sm:block text-white/40 text-[10px] font-mono">
                            <?= date('d/m/Y', strtotime($t['date_entry'])) ?>
                        </span>

                        <div class="flex gap-2 justify-end">
                            <button onclick="openModal('<?= $t['id'] ?>', '<?= $t['task_name'] ?>', '<?= $t['consumer'] ?>', '<?= $t['status'] ?>', '<?= $t['date_entry'] ?>', '<?= basename($t['image'] ?? '') ?>', '<?= addslashes($t['note'] ?? '') ?>')"
                                    class="bg-white/10 hover:bg-white hover:text-gray-900 px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition">
                                Detail
                            </button>
                            <button onclick="openPaymentModal('<?= $t['id'] ?>')"
                                    class="bg-blue-500/20 hover:bg-blue-500 text-white px-3 py-1 rounded-lg text-[10px] font-bold uppercase transition">
                                Payment
                            </button>
                            <button onclick="deleteTask('<?= $t['id'] ?>', '<?= $t['task_name'] ?>')"
                                    class="bg-red-500/20 hover:bg-red-500 text-white px-2 py-1 rounded-lg transition">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex justify-start shrink-0">
            <div onclick="openAddModal()" class="glass rounded-2xl p-4 text-center hover:bg-white/20 transition group cursor-pointer border border-white/5 shadow-md w-32 md:w-40">
                <i data-lucide="badge-plus" class="w-7 h-7 md:w-10 md:h-10 mx-auto mb-2 opacity-80 group-hover:scale-110 transition"></i>
                <span class="text-[10px] md:text-xs uppercase tracking-widest block">Add Task</span>
            </div>
        </div>

    </div>
</main>

<form id="formUpdate" enctype="multipart/form-data">
    <div id="modal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50 transition-all duration-200 opacity-0">
        <div class="bg-white rounded-3xl p-6 md:p-8 w-[90%] max-w-[400px] text-gray-800 relative shadow-2xl transform scale-95 transition-all duration-200">
            <button type="button" onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">✕</button>
            <h2 id="modalTitle" class="text-xl font-semibold mb-6 flex items-center gap-2">Edit Task</h2>
            
            <input type="hidden" id="task_id" name="id">
            <div class="space-y-4 text-sm max-h-[60vh] overflow-y-auto pr-2">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Nama Pesanan</label>
                    <input type="text" id="m_task" name="task_name" class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Customer</label>
                    <input type="text" id="m_consumer" name="consumer" class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-widest">Status</label>
                        <select id="m_status" name="status" class="w-full border rounded-xl px-2 py-2 mt-1 outline-none">
                            <option value="pending">Pending</option>
                            <option value="payment pending">Payment Pending</option>
                            <option value="process">Process</option>
                            <option value="finishing">Finishing</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-widest">Date</label>
                        <input type="date" id="m_date" name="date_entry" class="w-full border rounded-xl px-2 py-2 mt-1 outline-none">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Note</label>
                    <textarea id="m_note" name="note" class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500 resize-none" rows="3" placeholder="Tambahkan catatan..."></textarea>
                </div>
            </div>

            <div class="flex flex-col items-center my-6">
                <label class="cursor-pointer">
                    <input type="file" name="image" class="hidden" onchange="previewImage(event)">
                    <div class="w-24 h-24 bg-gray-100 rounded-2xl flex items-center justify-center overflow-hidden border border-gray-200">
                        <img id="preview" class="hidden w-full h-full object-cover">
                        <i id="iconImg" data-lucide="image" class="w-8 h-8 text-gray-400"></i>
                    </div>
                </label>
                <span class="text-[10px] text-gray-400 mt-2 uppercase">Upload Image</span>
            </div>

            <div id="loadingSpinner" class="hidden text-center mb-4">
                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                    <div id="progressBar" class="bg-blue-500 h-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-[10px] font-bold text-blue-500 mt-1">0%</p>
            </div>

            <button id="submitBtn" type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition">
                Save Changes
            </button>
        </div>
    </div>
</form>

<div id="confirmModal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-[60]">
    <div class="bg-white rounded-2xl p-6 w-[320px] text-gray-800 text-center shadow-xl">
        <h3 class="font-bold mb-2">Hapus Task?</h3>
        <p id="confirmText" class="text-xs text-gray-500 mb-6"></p>
        <div class="flex gap-2">
            <button id="confirmCancel" class="flex-1 py-2 rounded-lg text-gray-400 text-xs uppercase font-bold">Batal</button>
            <button id="confirmYes" class="flex-1 py-2 bg-red-500 text-white rounded-lg text-xs uppercase font-bold shadow-lg shadow-red-200">Hapus</button>
        </div>
    </div>
</div>

<form id="formPayment" enctype="multipart/form-data">
    <div id="paymentModal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-50 transition-all duration-200 opacity-0">
        <div class="bg-white rounded-3xl p-6 md:p-8 w-[90%] max-w-[500px] text-gray-800 relative shadow-2xl transform scale-95 transition-all duration-200">
            <button type="button" onclick="closePaymentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">✕</button>
            <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                <i data-lucide="credit-card" class="w-5 h-5"></i> Isi Data Payment
            </h2>
            
            <input type="hidden" id="payment_task_id" name="task_id">
            <div class="space-y-4 text-sm">
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Amount</label>
                    <input type="text" id="m_amount" name="amount" class="w-full border rounded-xl px-3 py-2 mt-1 outline-none focus:border-blue-500" placeholder="0" required oninput="formatRupiahInput(this)">
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Metode Pembayaran</label>
                    <select id="m_payment_method" name="payment_method" class="w-full border rounded-xl px-2 py-2 mt-1 outline-none">
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Cash</option>
                        <option value="e-wallet">E-Wallet</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Status Pembayaran</label>
                    <select id="m_payment_status" name="status" class="w-full border rounded-xl px-2 py-2 mt-1 outline-none">
                        <option value="unpaid">Belum Dibayar</option>
                        <option value="paid">Sudah Dibayar</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 uppercase tracking-widest">Tanggal Pembayaran</label>
                    <input type="date" id="m_payment_date" name="payment_date" class="w-full border rounded-xl px-2 py-2 mt-1 outline-none">
                </div>
            </div>

            <div id="paymentLoadingSpinner" class="hidden text-center mb-4 mt-4">
                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                    <div id="paymentProgressBar" class="bg-blue-500 h-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="paymentProgressText" class="text-[10px] font-bold text-blue-500 mt-1">0%</p>
            </div>

            <button id="paymentSubmitBtn" type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition mt-4">
                Simpan Payment
            </button>
        </div>
    </div>
</form>

<div id="toast" class="fixed top-6 left-1/2 -translate-x-1/2 hidden px-5 py-3 rounded-xl text-white shadow-lg transition-all z-[100] text-xs font-bold uppercase tracking-widest">
    <span id="toastText"></span>
</div>

<script>
    lucide.createIcons();

    document.addEventListener("DOMContentLoaded", function() {
        
        // --- MODAL FUNCTIONS ---
        window.openModal = function(id, task, consumer, status, date, image, note) {
            const modal = document.getElementById('modal');
            document.getElementById('modalTitle').innerText = 'Edit Task';
            document.getElementById('task_id').value = id;
            document.getElementById('m_task').value = task;
            document.getElementById('m_consumer').value = consumer;
            document.getElementById('m_status').value = status;
            document.getElementById('m_date').value = date;
            document.getElementById('m_note').value = note || '';

            const preview = document.getElementById('preview');
            const icon = document.getElementById('iconImg');
            if(image && image !== 'null'){
                preview.src = window.location.origin + '/image/' + image;
                preview.classList.remove('hidden');
                icon.classList.add('hidden');
            } else {
                preview.classList.add('hidden');
                icon.classList.remove('hidden');
            }

            // Logic untuk enable/disable status berdasarkan current status
            const statusSelect = document.getElementById('m_status');
            const currentStatus = status.toLowerCase();
            
            // Reset semua options
            Array.from(statusSelect.options).forEach(option => {
                option.disabled = false;
            });

            // Jika status = pending atau payment pending, disable semua status options
            if (['pending', 'payment pending'].includes(currentStatus)) {
                Array.from(statusSelect.options).forEach(option => {
                    option.disabled = true;
                });
            } 
            // Jika status = process/finishing/done, hanya enable ketiga status tersebut
            else if (['process', 'finishing', 'done'].includes(currentStatus)) {
                Array.from(statusSelect.options).forEach(option => {
                    if (['process', 'finishing', 'done'].includes(option.value.toLowerCase())) {
                        option.disabled = false;
                    } else {
                        option.disabled = true;
                    }
                });
            }

            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0', 'scale-95'), 10);
            setTimeout(() => modal.classList.add('opacity-100', 'scale-100'), 10);
        }

        window.openAddModal = function() {
            const modal = document.getElementById('modal');
            document.getElementById('modalTitle').innerText = 'Add Task';
            document.getElementById('formUpdate').reset();
            document.getElementById('task_id').value = '';
            document.getElementById('m_note').value = '';
            document.getElementById('preview').classList.add('hidden');
            document.getElementById('iconImg').classList.remove('hidden');

            // Hanya enable pending dan payment pending untuk new task
            const statusSelect = document.getElementById('m_status');
            Array.from(statusSelect.options).forEach(option => {
                if (['pending', 'payment pending'].includes(option.value.toLowerCase())) {
                    option.disabled = false;
                } else {
                    option.disabled = true;
                }
            });
            statusSelect.value = 'pending'; // Set default ke pending

            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0', 'scale-95'), 10);
            setTimeout(() => modal.classList.add('opacity-100', 'scale-100'), 10);
        }

        window.closeModal = function() {
            const modal = document.getElementById('modal');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        }

        window.openPaymentModal = function(taskId) {
            const paymentModal = document.getElementById('paymentModal');
            document.getElementById('payment_task_id').value = taskId;
            
            // Fetch existing payment data
            fetch(`/payment/${taskId}`)
                .then(response => response.json())
                .then(result => {
                    const paymentSubmitBtn = document.getElementById('paymentSubmitBtn');
                    const amountInput = document.getElementById('m_amount');
                    const methodSelect = document.getElementById('m_payment_method');
                    const statusSelect = document.getElementById('m_payment_status');
                    const dateInput = document.getElementById('m_payment_date');
                    
                    if (result.status === 200 && result.data && result.data.length > 0) {
                        // Ada data payment, populate form
                        const payment = result.data[0];
                        amountInput.value = payment.amount ? new Intl.NumberFormat('id-ID').format(payment.amount) : '';
                        methodSelect.value = payment.payment_method || 'transfer';
                        statusSelect.value = payment.status || 'unpaid';
                        dateInput.value = payment.payment_date || '';
                        
                        // Jika sudah dibayar (paid), set read-only
                        if (payment.status === 'paid') {
                            amountInput.disabled = true;
                            methodSelect.disabled = true;
                            statusSelect.disabled = true;
                            dateInput.disabled = true;
                            paymentSubmitBtn.disabled = true;
                            paymentSubmitBtn.classList.add('opacity-50');
                            paymentSubmitBtn.innerHTML = '<i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>Sudah Dibayar (Read Only)';
                            lucide.createIcons();
                        } else {
                            // Enable semua field jika belum paid
                            amountInput.disabled = false;
                            methodSelect.disabled = false;
                            statusSelect.disabled = false;
                            dateInput.disabled = false;
                            paymentSubmitBtn.disabled = false;
                            paymentSubmitBtn.classList.remove('opacity-50');
                            paymentSubmitBtn.innerHTML = 'Simpan Payment';
                        }
                    } else {
                        // Tidak ada data, reset form ke empty
                        amountInput.value = '';
                        methodSelect.value = 'transfer';
                        statusSelect.value = 'unpaid';
                        dateInput.value = '';
                        
                        // Enable semua field
                        amountInput.disabled = false;
                        methodSelect.disabled = false;
                        statusSelect.disabled = false;
                        dateInput.disabled = false;
                        paymentSubmitBtn.disabled = false;
                        paymentSubmitBtn.classList.remove('opacity-50');
                        paymentSubmitBtn.innerHTML = 'Simpan Payment';
                    }
                })
                .catch(error => {
                    console.error('Error fetching payment:', error);
                    // Reset form jika error
                    document.getElementById('m_amount').value = '';
                    document.getElementById('m_payment_method').value = 'transfer';
                    document.getElementById('m_payment_status').value = 'unpaid';
                    document.getElementById('m_payment_date').value = '';
                });

            paymentModal.classList.remove('hidden');
            setTimeout(() => paymentModal.classList.remove('opacity-0', 'scale-95'), 10);
            setTimeout(() => paymentModal.classList.add('opacity-100', 'scale-100'), 10);
        }

        window.closePaymentModal = function() {
            const paymentModal = document.getElementById('paymentModal');
            paymentModal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => paymentModal.classList.add('hidden'), 200);
        }

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

        // --- SUBMIT LOGIC ---
        const form = document.getElementById('formUpdate');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const taskId = document.getElementById('task_id').value;
            const loadingSpinner = document.getElementById('loadingSpinner');
            const submitBtn = document.getElementById('submitBtn');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            // Proses submit normal
            loadingSpinner.classList.remove('hidden');
            submitBtn.disabled = true;

            let progress = 0;
            const interval = setInterval(() => {
                if(progress < 90) {
                    progress += 10;
                    progressBar.style.width = progress + '%';
                    progressText.innerText = progress + '%';
                }
            }, 100);

            try {
                let formData = new FormData(this);
                let url = taskId ? '/task/update' : '/task/store';

                let response = await fetch(url, { method: 'POST', body: formData });
                let result = await response.json();

                clearInterval(interval);
                progressBar.style.width = '100%';
                progressText.innerText = '100%';

                if(result.status === 200) {
                    showToast('success', 'Data Saved');
                    setTimeout(() => {
                        // Jika task baru, buka payment modal
                        if (!taskId) {
                            closeModal();
                            openPaymentModal(result.task_id);
                        } else {
                            location.reload();
                        }
                    }, 800);
                } else {
                    showToast('error', result.message || 'Error');
                    loadingSpinner.classList.add('hidden');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                showToast('error', 'Server Error');
                loadingSpinner.classList.add('hidden');
                submitBtn.disabled = false;
            }
        });

        // --- DELETE LOGIC ---
        window.deleteTask = function(id, name) {
            const confirmModal = document.getElementById('confirmModal');
            document.getElementById('confirmText').innerText = `Hapus task "${name}"?`;
            confirmModal.classList.remove('hidden');

            document.getElementById('confirmYes').onclick = async () => {
                confirmModal.classList.add('hidden');
                try {
                    const response = await fetch(`/task/delete/${id}`, { method: 'DELETE' });
                    const result = await response.json();
                    if(result.status === 200) {
                        showToast('success', 'Deleted');
                        setTimeout(() => location.reload(), 500);
                    }
                } catch (e) { showToast('error', 'Failed'); }
            };
            document.getElementById('confirmCancel').onclick = () => confirmModal.classList.add('hidden');
        }

        function showToast(type, msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toastText').innerText = msg;
            toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
            toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
            setTimeout(() => toast.classList.add('hidden'), 2500);
        }

        // --- PAYMENT FORM LOGIC ---
        const formPayment = document.getElementById('formPayment');
        formPayment.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const paymentSubmitBtn = document.getElementById('paymentSubmitBtn');
            // Cegah submit jika dalam status read-only (paid)
            if (paymentSubmitBtn.disabled) {
                showToast('error', 'Data sudah dibayar, tidak bisa diedit');
                return;
            }
            
            const paymentLoadingSpinner = document.getElementById('paymentLoadingSpinner');
            const paymentProgressBar = document.getElementById('paymentProgressBar');
            const paymentProgressText = document.getElementById('paymentProgressText');
            const taskId = document.getElementById('payment_task_id').value;
            const paymentStatus = document.getElementById('m_payment_status').value;

            paymentLoadingSpinner.classList.remove('hidden');
            paymentSubmitBtn.disabled = true;

            let progress = 0;
            const interval = setInterval(() => {
                if(progress < 90) {
                    progress += 10;
                    paymentProgressBar.style.width = progress + '%';
                    paymentProgressText.innerText = progress + '%';
                }
            }, 100);

            try {
                let formData = new FormData(this);
                // Pre-process amount to remove dots before sending
                formData.set('amount', getRawValue(formData.get('amount')));
                
                let response = await fetch('/payment/store', { method: 'POST', body: formData });
                let result = await response.json();

                clearInterval(interval);
                paymentProgressBar.style.width = '100%';
                paymentProgressText.innerText = '100%';

                if(result.status === 200) {
                    // Tentukan task status berdasarkan payment status
                    let newTaskStatus = '';
                    if (paymentStatus === 'paid') {
                        newTaskStatus = 'process';
                    } else if (paymentStatus === 'unpaid') {
                        newTaskStatus = 'payment pending';
                    }

                    // Update task status sesuai payment status
                    try {
                        let updateData = new FormData();
                        updateData.append('id', taskId);
                        updateData.append('status', newTaskStatus);
                        
                        let updateResponse = await fetch('/task/update-status', { 
                            method: 'POST', 
                            body: updateData 
                        });
                        let updateResult = await updateResponse.json();
                        
                        if(updateResult.status === 200) {
                            showToast('success', 'Payment Berhasil & Status Updated');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showToast('success', 'Payment Berhasil Disimpan');
                            setTimeout(() => location.reload(), 800);
                        }
                    } catch (updateError) {
                        console.error('Error updating status:', updateError);
                        showToast('success', 'Payment Berhasil Disimpan');
                        setTimeout(() => location.reload(), 800);
                    }
                } else {
                    showToast('error', result.message || 'Error');
                    paymentLoadingSpinner.classList.add('hidden');
                    paymentSubmitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', 'Server Error');
                paymentLoadingSpinner.classList.add('hidden');
                paymentSubmitBtn.disabled = false;
            }
        });

        // --- HELPERS ---
        window.formatRupiahInput = function(el) {
            let val = el.value.replace(/[^0-9]/g, '');
            if (val === '') {
                el.value = '';
                return;
            }
            el.value = new Intl.NumberFormat('id-ID').format(val);
        }

        window.getRawValue = function(val) {
            return val.replace(/\./g, '');
        }

    });

    // Update local date every 60 seconds
    function updateLocalDate() {
        const dateEl = document.getElementById('currentDate');
        if (!dateEl) return;
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        dateEl.textContent = `${day}/${month}/${year}`;
    }

    // Initial call and set interval
    updateLocalDate();
    setInterval(updateLocalDate, 60000);
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