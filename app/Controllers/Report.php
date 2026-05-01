<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\KehadiranModel;
use App\Models\UserModel;

class Report extends BaseController
{
    protected $reportModel;
    protected $kehadiranModel;
    protected $userModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->kehadiranModel = new KehadiranModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userId = session()->get('id');

        // Get user
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        // Check if user is admin
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Hanya admin yang bisa akses halaman ini');
        }

        $data['user'] = $user;

        // Get selected date (default = today)
        $selectedDate = $this->request->getGet('date') ?? date('Y-m-d');
        $data['selected_date'] = $selectedDate;

        // Ensure date exists in report_settings
        $this->reportModel->ensureDateExists($selectedDate);

        // Get semua transaksi user MANUAL untuk tanggal tertentu
        $manualTransactions = $this->reportModel->where('user_id', $userId)
            ->where('DATE(tanggal_transaksi)', $selectedDate)
            ->orderBy('tanggal_transaksi', 'DESC')
            ->findAll();
        
        // Get paid payments dengan task details untuk tanggal tertentu
        $db = \Config\Database::connect();
        $paidPayments = $db->table('payments as p')
            ->select('p.id, p.amount, p.payment_date, t.task_name, p.task_id')
            ->join('tasks as t', 'p.task_id = t.id', 'LEFT')
            ->where('p.status', 'paid')
            ->where('DATE(p.payment_date)', $selectedDate)
            ->orderBy('p.payment_date', 'DESC')
            ->get()
            ->getResultArray();
        
        
        // Get cashbon records for selected date
        $cashbonModel = new \App\Models\CashbonModel();
        $dailyCashbon = $cashbonModel->where('DATE(tanggal)', $selectedDate)
            ->join('users', 'users.id = cashbon.user_id')
            ->select('cashbon.*, users.username, users.profile')
            ->findAll();

        // Get bonus records for selected date
        $bonusModel = new \App\Models\BonusModel();
        $dailyBonus = $bonusModel->where('DATE(tanggal)', $selectedDate)
            ->join('users', 'users.id = bonus.user_id')
            ->select('bonus.*, users.username, users.profile')
            ->findAll();

        // Combine dan format semua transaksi (manual + paid payments + cashbon + bonus)
        $allTransactions = [];
        
        // Tambah manual transactions
        foreach ($manualTransactions as $t) {
            $allTransactions[] = [
                'id' => 'manual_' . $t['id'],
                'tanggal_transaksi' => $t['tanggal_transaksi'],
                'nama_transaksi' => $t['nama_transaksi'],
                'harga' => $t['harga'],
                'harga_masuk' => $t['harga_masuk'],
                'tipe_transaksi' => $t['tipe_transaksi'],
                'source' => 'manual',
                'type_display' => 'Manual'
            ];
        }
        
        // Tambah paid payments as "masuk" type
        foreach ($paidPayments as $p) {
            $allTransactions[] = [
                'id' => 'payment_' . $p['id'],
                'tanggal_transaksi' => $p['payment_date'],
                'nama_transaksi' => $p['task_name'] ?? 'Task #' . $p['task_id'],
                'harga' => 0,
                'harga_masuk' => $p['amount'],
                'tipe_transaksi' => 'masuk',
                'source' => 'payment',
                'type_display' => 'Payment'
            ];
        }

        // Tambah Cashbon records
        foreach ($dailyCashbon as $c) {
            $allTransactions[] = [
                'id' => 'cashbon_' . $c['id'],
                'tanggal_transaksi' => $c['tanggal'],
                'nama_transaksi' => 'Cashbon: ' . $c['username'],
                'username' => $c['username'],
                'profile' => $c['profile'],
                'harga' => $c['nominal'],
                'harga_masuk' => 0,
                'tipe_transaksi' => 'cashbon',
                'source' => 'cashbon',
                'type_display' => 'Cashbon'
            ];
        }

        // Tambah Bonus records
        foreach ($dailyBonus as $b) {
            $allTransactions[] = [
                'id' => 'bonus_' . $b['id'],
                'tanggal_transaksi' => $b['tanggal'],
                'nama_transaksi' => 'Bonus: ' . $b['username'],
                'username' => $b['username'],
                'profile' => $b['profile'],
                'harga' => $b['nominal'],
                'harga_masuk' => 0,
                'tipe_transaksi' => 'bonus',
                'source' => 'bonus',
                'type_display' => 'Bonus'
            ];
        }
        
        // Sort by tanggal_transaksi DESC
        usort($allTransactions, function($a, $b) {
            return strtotime($b['tanggal_transaksi']) - strtotime($a['tanggal_transaksi']);
        });
        
        $data['transactions'] = $allTransactions;
        
        // Get summary untuk tanggal tertentu
        // Total transaksi keluar (belanja) untuk hari itu
        $totalBelanja = $db->table('reports')
            ->where('user_id', $userId)
            ->where('tipe_transaksi', 'keluar')
            ->where('DATE(tanggal_transaksi)', $selectedDate)
            ->selectSum('harga', 'total')
            ->get()
            ->getRow();

        // Total transaksi masuk manual (dari reports table) untuk hari itu
        $totalMasukManual = $db->table('reports')
            ->where('user_id', $userId)
            ->where('tipe_transaksi', 'masuk')
            ->where('DATE(tanggal_transaksi)', $selectedDate)
            ->selectSum('harga_masuk', 'total')
            ->get()
            ->getRow();

        // Total dari payments yang status 'paid' untuk hari itu
        $totalMasukPaid = $db->table('payments')
            ->where('status', 'paid')
            ->where('DATE(payment_date)', $selectedDate)
            ->selectSum('amount', 'total_paid')
            ->get()
            ->getRow();

        // Total Cashbon untuk hari itu
        $totalDailyCashbon = $db->table('cashbon')
            ->where('DATE(tanggal)', $selectedDate)
            ->selectSum('nominal', 'total')
            ->get()
            ->getRow();

        // Total Bonus untuk hari itu
        $totalDailyBonus = $db->table('bonus')
            ->where('DATE(tanggal)', $selectedDate)
            ->selectSum('nominal', 'total')
            ->get()
            ->getRow();

        $data['summary'] = [
            'total_belanja' => (int) ($totalBelanja->total ?? 0),
            'total_masuk_manual' => (int) ($totalMasukManual->total ?? 0),
            'total_masuk_paid' => (int) ($totalMasukPaid->total_paid ?? 0),
            'total_masuk' => ((int) ($totalMasukManual->total ?? 0)) + ((int) ($totalMasukPaid->total_paid ?? 0)),
            'total_cashbon' => (int) ($totalDailyCashbon->total ?? 0),
            'total_bonus' => (int) ($totalDailyBonus->total ?? 0),
            'count_belanja' => $db->table('reports')->where('user_id', $userId)->where('tipe_transaksi', 'keluar')->where('DATE(tanggal_transaksi)', $selectedDate)->countAllResults(),
            'count_masuk' => $db->table('reports')->where('user_id', $userId)->where('tipe_transaksi', 'masuk')->where('DATE(tanggal_transaksi)', $selectedDate)->countAllResults() + count($paidPayments),
        ];

        // Get modal awal untuk tanggal tertentu
        $data['modal_awal'] = $this->reportModel->getModalAwalByDate($selectedDate);

        // Attendance stats per user
        $allUsers = $this->userModel->findAll();
        $attendanceRecords = $this->kehadiranModel->where('tanggal', $selectedDate)->findAll();

        $attendanceByUser = [];
        foreach ($attendanceRecords as $record) {
            $attendanceByUser[$record['user_id']] = $record;
        }

        $statusUsers = [];
        $hadirCount = 0;
        $sakitCount = 0;
        $alfaCount = 0;

        foreach ($allUsers as $userItem) {
            $userStatus = 'alfa';
            if (isset($attendanceByUser[$userItem['id']])) {
                $status = strtolower($attendanceByUser[$userItem['id']]['status']);
                if ($status === 'hadir') {
                    $userStatus = 'hadir';
                    $hadirCount++;
                } elseif ($status === 'sakit') {
                    $userStatus = 'sakit';
                    $sakitCount++;
                } else {
                    $userStatus = 'alfa';
                    $alfaCount++;
                }
            } else {
                $userStatus = 'alfa';
                $alfaCount++;
            }

            $statusUsers[] = [
                'username' => $userItem['username'],
                'status' => $userStatus
            ];
        }

        $totalUsers = count($allUsers);
        $attendancePercentage = $totalUsers > 0 ? round(($hadirCount / $totalUsers) * 100, 2) : 0;

        $data['attendance'] = [
            'total_users' => $totalUsers,
            'hadir' => $hadirCount,
            'sakit' => $sakitCount,
            'alfa' => $alfaCount,
            'absent_count' => $sakitCount + $alfaCount,
            'attendance_percentage' => $attendancePercentage,
            'status_users' => $statusUsers,
        ];

        // Hitung balance
        // Balance = Modal Awal + Total Masuk - Total Keluar - Total Cashbon - Total Bonus
        $data['total_balance'] = $data['modal_awal'] + $data['summary']['total_masuk'] - $data['summary']['total_belanja'] - $data['summary']['total_cashbon'] - $data['summary']['total_bonus'];
        $data['all_users'] = $allUsers;

        return view('report', $data);
    }

    public function exportReport()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userId = session()->get('id');
        $user = $this->userModel->find($userId);
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Hanya admin yang bisa akses fitur ini');
        }

        $selectedDate = $this->request->getGet('date') ?? date('Y-m-d');

        $manualTransactions = $this->reportModel->where('user_id', $userId)
            ->where('DATE(tanggal_transaksi)', $selectedDate)
            ->orderBy('tanggal_transaksi', 'DESC')
            ->findAll();

        $db = \Config\Database::connect();
        $paidPayments = $db->table('payments as p')
            ->select('p.id, p.amount, p.payment_date, t.task_name, p.task_id')
            ->join('tasks as t', 'p.task_id = t.id', 'LEFT')
            ->where('p.status', 'paid')
            ->where('DATE(p.payment_date)', $selectedDate)
            ->orderBy('p.payment_date', 'DESC')
            ->get()
            ->getResultArray();

        $allTransactions = [];
        foreach ($manualTransactions as $t) {
            $allTransactions[] = [
                'tanggal_transaksi' => $t['tanggal_transaksi'],
                'nama_transaksi' => $t['nama_transaksi'],
                'harga' => $t['tipe_transaksi'] === 'keluar' ? $t['harga'] : 0,
                'harga_masuk' => $t['tipe_transaksi'] === 'masuk' ? $t['harga_masuk'] : 0,
                'tipe_transaksi' => $t['tipe_transaksi'],
                'source' => 'Manual'
            ];
        }
        foreach ($paidPayments as $p) {
            $allTransactions[] = [
                'tanggal_transaksi' => $p['payment_date'],
                'nama_transaksi' => $p['task_name'] ?? 'Task #' . $p['task_id'],
                'harga' => 0,
                'harga_masuk' => $p['amount'],
                'tipe_transaksi' => 'masuk',
                'source' => 'System'
            ];
        }
        // Sort DESC
        usort($allTransactions, fn($a, $b) => strtotime($b['tanggal_transaksi']) - strtotime($a['tanggal_transaksi']));

        // hitung summary
        $totalBelanja = $db->table('reports')->where('user_id', $userId)->where('tipe_transaksi', 'keluar')->where('DATE(tanggal_transaksi)', $selectedDate)->selectSum('harga', 'total')->get()->getRow();
        $totalMasukManual = $db->table('reports')->where('user_id', $userId)->where('tipe_transaksi', 'masuk')->where('DATE(tanggal_transaksi)', $selectedDate)->selectSum('harga_masuk', 'total')->get()->getRow();
        $totalMasukPaid = $db->table('payments')->where('status', 'paid')->where('DATE(payment_date)', $selectedDate)->selectSum('amount', 'total_paid')->get()->getRow();

        $modalAwal = $this->reportModel->getModalAwalByDate($selectedDate);
        $totalMasuk = ((int) ($totalMasukManual->total ?? 0)) + ((int) ($totalMasukPaid->total_paid ?? 0));
        $totalBalance = $modalAwal + $totalMasuk - (int) ($totalBelanja->total ?? 0);

        $attendance = $this->attendanceDataForDate($selectedDate); // helper kita buat di bawah

        $filename = "report_{$selectedDate}.xls";

        $html = '<html><head><meta charset="UTF-8"/><style>' .
            'table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:12px;}' .
            'th,td{border:1px solid #ccc;padding:6px;}' .
            '.th-title{background:#0d6efd;color:#fff;font-size:16px;font-weight:bold;text-align:center;}' .
            '.th-header{background:#3085d6;color:#fff;font-weight:bold;}' .
            '.val-hadir{background:#d1e7dd;color:#0f5132;}' .
            '.val-sakit{background:#fff3cd;color:#664d03;}' .
            '.val-alfa{background:#f8d7da;color:#842029;}' .
            '</style></head><body>';

        $html .= '<table>';
        $html .= '<tr><th class="th-title" colspan="6">Laporan Transaksi & Kehadiran - ' . $selectedDate . '</th></tr>';
        $html .= '<tr><td colspan="3"><strong>Modal Awal</strong></td><td colspan="3">' . number_format($modalAwal, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Total Masuk</strong></td><td colspan="3">' . number_format($totalMasuk, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Total Belanja</strong></td><td colspan="3">' . number_format((int)$totalBelanja->total, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Balance</strong></td><td colspan="3">' . number_format($totalBalance, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="6"></td></tr>';

        $html .= '<tr><th class="th-header">Tanggal</th><th class="th-header">Nama</th><th class="th-header">Tipe</th><th class="th-header">Harga</th><th class="th-header">Harga Masuk</th><th class="th-header">Sumber</th></tr>';
        foreach ($allTransactions as $trx) {
            $html .= '<tr>' .
                '<td>' . esc($trx['tanggal_transaksi']) . '</td>' .
                '<td>' . esc($trx['nama_transaksi']) . '</td>' .
                '<td>' . esc(ucfirst($trx['tipe_transaksi'])) . '</td>' .
                '<td>' . number_format($trx['harga'], 0, ',', '.') . '</td>' .
                '<td>' . number_format($trx['harga_masuk'], 0, ',', '.') . '</td>' .
                '<td>' . esc($trx['source']) . '</td>' .
                '</tr>';
        }

        $html .= '<tr><td colspan="6"></td></tr>';
        $html .= '<tr><th class="th-header">Total Karyawan</th><th class="th-header">Hadir</th><th class="th-header">Sakit</th><th class="th-header">Alfa</th><th class="th-header">Presentase</th><th class="th-header">Tanggal</th></tr>';
        $html .= '<tr><td>' . $attendance['total_users'] . '</td><td class="val-hadir">' . $attendance['hadir'] . '</td><td class="val-sakit">' . $attendance['sakit'] . '</td><td class="val-alfa">' . $attendance['alfa'] . '</td><td>' . $attendance['attendance_percentage'] . '%</td><td>' . $selectedDate . '</td></tr>';
        $html .= '<tr><td colspan="6"></td></tr>';

        $html .= '<tr><th colspan="2" class="th-header">Username</th><th colspan="4" class="th-header">Status</th></tr>';
        foreach ($attendance['status_users'] as $userStatus) {
            $colorClass = 'val-alfa';
            if ($userStatus['status'] === 'hadir') $colorClass = 'val-hadir';
            elseif ($userStatus['status'] === 'sakit') $colorClass = 'val-sakit';

            $html .= '<tr><td colspan="2">' . esc($userStatus['username']) . '</td><td colspan="4" class="' . $colorClass . '">' . esc(ucfirst($userStatus['status'])) . '</td></tr>';
        }

        $html .= '</table></body></html>';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($html);
    }

    private function attendanceDataForDate($date)
    {
        $allUsers = $this->userModel->findAll();
        $attendanceRecords = $this->kehadiranModel->where('tanggal', $date)->findAll();

        $attendanceByUser = [];
        foreach ($attendanceRecords as $record) {
            $attendanceByUser[$record['user_id']] = $record;
        }

        $statusUsers = [];
        $hadirCount = 0;
        $sakitCount = 0;
        $alfaCount = 0;

        foreach ($allUsers as $userItem) {
            $userStatus = 'alfa';
            if (isset($attendanceByUser[$userItem['id']])) {
                $status = strtolower($attendanceByUser[$userItem['id']]['status']);
                if ($status === 'hadir') {
                    $userStatus = 'hadir';
                    $hadirCount++;
                } elseif ($status === 'sakit') {
                    $userStatus = 'sakit';
                    $sakitCount++;
                } else {
                    $userStatus = 'alfa';
                    $alfaCount++;
                }
            } else {
                $userStatus = 'alfa';
                $alfaCount++;
            }

            $statusUsers[] = [
                'username' => $userItem['username'],
                'status' => $userStatus
            ];
        }

        $totalUsers = count($allUsers);
        $attendancePercentage = $totalUsers > 0 ? round(($hadirCount / $totalUsers) * 100, 2) : 0;

        return [
            'total_users' => $totalUsers,
            'hadir' => $hadirCount,
            'sakit' => $sakitCount,
            'alfa' => $alfaCount,
            'attendance_percentage' => $attendancePercentage,
            'status_users' => $statusUsers,
        ];
    }

    public function updateModalAwal()
    {
        if (!session()->get('isLoggedIn')) {
            return json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $userId = session()->get('id');
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        // Check role
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return $this->response
                ->setStatusCode(403)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode([
                    'status' => 'error',
                    'message' => 'Hanya admin yang bisa mengubah modal awal'
                ]));
        }

        $modalAwal = (int) $this->request->getPost('modal_awal');
        $selectedDate = $this->request->getPost('selected_date') ?? date('Y-m-d');

        // Validasi
        if ($modalAwal < 0) {
            return $this->response
                ->setStatusCode(400)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode([
                    'status' => 'error',
                    'message' => 'Modal awal tidak boleh negatif'
                ]));
        }

        // Update modal awal untuk tanggal tertentu (ke report_settings table)
        if ($this->reportModel->updateModalAwalByDate($selectedDate, $modalAwal)) {
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode([
                    'status' => 'success',
                    'message' => 'Modal awal berhasil diperbarui'
                ]));
        } else {
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode([
                    'status' => 'error',
                    'message' => 'Gagal memperbarui modal awal'
                ]));
        }
    }

    public function addTransaction()
    {
        if (!session()->get('isLoggedIn')) {
            return json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $userId = session()->get('id');
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        // Check role
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Hanya admin yang bisa menambah transaksi']);
        }

        $nama = $this->request->getPost('nama_transaksi');
        $harga_raw = $this->request->getPost('harga');
        $tanggal = $this->request->getPost('tanggal_transaksi');
        $tipe = $this->request->getPost('tipe_transaksi'); // 'masuk', 'keluar', 'cashbon', 'bonus'
        $target_user_id = $this->request->getPost('target_user_id');
        $isKhusus = $this->request->getPost('is_khusus') === 'true';

        // Clean harga from dots
        $harga = (float) str_replace('.', '', $harga_raw);

        // Validasi Dasar
        if (!$harga || !$tanggal || !$tipe) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Nominal, Tanggal, dan Tipe harus diisi']);
        }

        if (!in_array($tipe, ['masuk', 'keluar', 'cashbon', 'bonus'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Tipe transaksi tidak valid']);
        }

        // Logic berdasarkan Tipe
        if ($tipe === 'cashbon' || $tipe === 'bonus') {
            if (!$target_user_id) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Pilih karyawan untuk Cashbon/Bonus']);
            }

            if ($tipe === 'cashbon' && !$isKhusus && $harga > 500000) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Cashbon max 500.000. Untuk lebih, centang Cashbon Khusus.']);
            }

            $model = ($tipe === 'cashbon') ? new \App\Models\CashbonModel() : new \App\Models\BonusModel();
            $saved = $model->insert([
                'user_id' => $target_user_id,
                'nominal' => $harga,
                'tanggal' => $tanggal
            ]);
        } else {
            // Masuk / Keluar
            if (!$nama) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Nama transaksi harus diisi']);
            }

            $saved = $this->reportModel->insert([
                'user_id' => $userId,
                'nama_transaksi' => $nama,
                'harga' => ($tipe === 'keluar') ? $harga : 0,
                'harga_masuk' => ($tipe === 'masuk') ? $harga : 0,
                'tanggal_transaksi' => $tanggal,
                'tipe_transaksi' => $tipe,
            ]);
        }

        if ($saved) {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Transaksi berhasil disimpan']);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan transaksi']);
        }
    }

    public function deleteTransaction($fullId)
    {
        if (!session()->get('isLoggedIn')) {
            return json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $userId = session()->get('id');
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        // Check role
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Hanya admin yang bisa menghapus transaksi']);
        }

        // Parse fullId (e.g., 'manual_1', 'cashbon_5')
        $parts = explode('_', $fullId);
        if (count($parts) < 2) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ID tidak valid']);
        }

        $type = $parts[0];
        $id = $parts[1];

        $deleted = false;
        if ($type === 'manual') {
            $deleted = $this->reportModel->delete($id);
        } elseif ($type === 'cashbon') {
            $cashbonModel = new \App\Models\CashbonModel();
            $deleted = $cashbonModel->delete($id);
        } elseif ($type === 'bonus') {
            $bonusModel = new \App\Models\BonusModel();
            $deleted = $bonusModel->delete($id);
        }

        if ($deleted) {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Transaksi berhasil dihapus']);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Gagal menghapus transaksi']);
        }
    }
}
