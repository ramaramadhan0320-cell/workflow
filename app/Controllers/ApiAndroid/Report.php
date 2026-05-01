<?php

namespace App\Controllers\ApiAndroid;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use App\Models\KehadiranModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class Report extends BaseController
{
    use ResponseTrait;

    protected $reportModel;
    protected $kehadiranModel;
    protected $userModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->kehadiranModel = new KehadiranModel();
        $this->userModel = new UserModel();
    }

    private function checkAuth()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) return null;
        
        $user = $this->userModel->find($userId);
        if (!$user) return null;
        
        return $user;
    }

    public function getDaily()
    {
        $user = $this->checkAuth();
        if (!$user) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $selectedDate = $this->request->getGet('date') ?? date('Y-m-d');
        $db = \Config\Database::connect();

        $this->reportModel->ensureDateExists($selectedDate);

        // Manual transactions
        $manualTransactions = $this->reportModel->where('DATE(tanggal_transaksi)', $selectedDate)
            ->join('users', 'users.id = reports.user_id', 'left')
            ->select('reports.*, users.username, users.profile')
            ->orderBy('reports.tanggal_transaksi', 'DESC')
            ->findAll();

        // Paid payments
        $paidPayments = $db->table('payments as p')
            ->select('p.id, p.amount, p.payment_date, t.task_name, p.task_id')
            ->join('tasks as t', 'p.task_id = t.id', 'LEFT')
            ->where('p.status', 'paid')
            ->where('DATE(p.payment_date)', $selectedDate)
            ->orderBy('p.payment_date', 'DESC')
            ->get()->getResultArray();

        // Cashbon records
        $cashbonModel = new \App\Models\CashbonModel();
        $dailyCashbon = $cashbonModel->where('DATE(tanggal)', $selectedDate)
            ->join('users', 'users.id = cashbon.user_id')
            ->select('cashbon.*, users.username, users.profile')
            ->findAll();

        // Bonus records
        $bonusModel = new \App\Models\BonusModel();
        $dailyBonus = $bonusModel->where('DATE(tanggal)', $selectedDate)
            ->join('users', 'users.id = bonus.user_id')
            ->select('bonus.*, users.username, users.profile')
            ->findAll();

        $allTransactions = [];
        foreach ($manualTransactions as $t) {
            $allTransactions[] = [
                'id'     => 'manual_' . $t['id'],
                'raw_id' => $t['id'],
                'time'   => date('H:i', strtotime($t['tanggal_transaksi'])),
                'title'  => $t['nama_transaksi'],
                'username' => $t['username'] ?? 'System',
                'profile'  => $t['profile'] ?? null,
                'amount' => (int)($t['tipe_transaksi'] === 'masuk' ? $t['harga_masuk'] : $t['harga']),
                'type'   => $t['tipe_transaksi'],
                'source' => 'manual',
            ];
        }
        foreach ($paidPayments as $p) {
            $allTransactions[] = [
                'id'     => 'payment_' . $p['id'],
                'raw_id' => $p['id'],
                'time'   => date('H:i', strtotime($p['payment_date'])),
                'title'  => $p['task_name'] ?? 'Task #' . $p['task_id'],
                'amount' => (int)$p['amount'],
                'type'   => 'masuk',
                'source' => 'payment',
            ];
        }
        foreach ($dailyCashbon as $c) {
            $allTransactions[] = [
                'id'     => 'cashbon_' . $c['id'],
                'raw_id' => $c['id'],
                'time'   => date('H:i', strtotime($c['tanggal'])),
                'title'  => 'Cashbon: ' . $c['username'],
                'username' => $c['username'],
                'profile'  => $c['profile'],
                'amount' => (int)$c['nominal'],
                'type'   => 'cashbon',
                'source' => 'cashbon',
            ];
        }
        foreach ($dailyBonus as $b) {
            $allTransactions[] = [
                'id'     => 'bonus_' . $b['id'],
                'raw_id' => $b['id'],
                'time'   => date('H:i', strtotime($b['tanggal'])),
                'title'  => 'Bonus: ' . $b['username'],
                'username' => $b['username'],
                'profile'  => $b['profile'],
                'amount' => (int)$b['nominal'],
                'type'   => 'bonus',
                'source' => 'bonus',
            ];
        }
        usort($allTransactions, fn($a, $b) => strcmp($b['time'], $a['time']));

        // Summary calculations
        $totalBelanja    = $db->table('reports')->where('tipe_transaksi', 'keluar')->where('DATE(tanggal_transaksi)', $selectedDate)->selectSum('harga', 'total')->get()->getRow();
        $totalMasukManual = $db->table('reports')->where('tipe_transaksi', 'masuk')->where('DATE(tanggal_transaksi)', $selectedDate)->selectSum('harga_masuk', 'total')->get()->getRow();
        $totalMasukPaid   = $db->table('payments')->where('status', 'paid')->where('DATE(payment_date)', $selectedDate)->selectSum('amount', 'total_paid')->get()->getRow();
        
        $totalDailyCashbon = $db->table('cashbon')->where('DATE(tanggal)', $selectedDate)->selectSum('nominal', 'total')->get()->getRow();
        $totalDailyBonus = $db->table('bonus')->where('DATE(tanggal)', $selectedDate)->selectSum('nominal', 'total')->get()->getRow();

        $modalAwal   = (int)($this->reportModel->getModalAwalByDate($selectedDate) ?? 0);
        $totalMasuk  = ((int)($totalMasukManual->total ?? 0)) + ((int)($totalMasukPaid->total_paid ?? 0));
        $totalKeluar = (int)($totalBelanja->total ?? 0);
        $totalCashbon = (int)($totalDailyCashbon->total ?? 0);
        $totalBonus = (int)($totalDailyBonus->total ?? 0);
        
        // Balance = Modal Awal + Total Masuk - Total Keluar - Total Cashbon - Total Bonus
        $saldo       = $modalAwal + $totalMasuk - $totalKeluar - $totalCashbon - $totalBonus;

        // Attendance
        $allUsers         = $this->userModel->findAll();
        $attendanceRecords = $this->kehadiranModel->where('tanggal', $selectedDate)->findAll();
        $attendanceByUser  = [];
        foreach ($attendanceRecords as $record) {
            $attendanceByUser[$record['user_id']] = $record;
        }

        $hadirCount = $sakitCount = $alfaCount = 0;
        $statusUsers = [];
        foreach ($allUsers as $u) {
            $status = 'alfa';
            if (isset($attendanceByUser[$u['id']])) {
                $s = strtolower($attendanceByUser[$u['id']]['status']);
                if ($s === 'hadir') { $status = 'hadir'; $hadirCount++; }
                elseif ($s === 'sakit') { $status = 'sakit'; $sakitCount++; }
                else { $alfaCount++; }
            } else {
                $alfaCount++;
            }
            $statusUsers[] = ['username' => $u['username'], 'status' => $status];
        }

        return $this->respond([
            'status' => 200,
            'data' => [
                'date' => $selectedDate,
                'summary' => [
                    'modal_awal'          => $modalAwal,
                    'total_masuk'         => $totalMasuk,
                    'total_masuk_manual'  => (int)($totalMasukManual->total ?? 0),
                    'total_masuk_paid'    => (int)($totalMasukPaid->total_paid ?? 0),
                    'total_keluar'        => $totalKeluar,
                    'total_cashbon'       => $totalCashbon,
                    'total_bonus'         => $totalBonus,
                    'count_keluar'        => $db->table('reports')->where('tipe_transaksi', 'keluar')->where('DATE(tanggal_transaksi)', $selectedDate)->countAllResults(),
                    'saldo'               => $saldo,
                ],
                'attendance' => [
                    'total'       => count($allUsers),
                    'hadir'       => $hadirCount,
                    'sakit'       => $sakitCount,
                    'alfa'        => $alfaCount,
                    'percentage'  => count($allUsers) > 0 ? round(($hadirCount / count($allUsers)) * 100, 1) : 0,
                    'status_users' => $statusUsers,
                ],
                'users' => $allUsers,
                'transactions' => $allTransactions,
            ]
        ]);
    }

    public function addTransaction()
    {
        $user = $this->checkAuth();
        if (!$user) return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);

        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $tipe  = strtolower($input['tipe_transaksi'] ?? '');
            $nama  = $input['nama_transaksi'] ?? null;
            $harga = (int)($input['harga'] ?? 0);
            $tanggal = $input['tanggal_transaksi'] ?? date('Y-m-d');
            $targetUserId = $input['target_user_id'] ?? null;

            if (!$tipe || $harga <= 0) {
                return $this->respond(['status' => 400, 'message' => 'Data tidak lengkap'], 400);
            }

            if ($tipe === 'cashbon' || $tipe === 'bonus') {
                if (!$targetUserId) {
                    return $this->respond(['status' => 400, 'message' => 'Pilih karyawan untuk Cashbon/Bonus'], 400);
                }
                $model = ($tipe === 'cashbon') ? new \App\Models\CashbonModel() : new \App\Models\BonusModel();
                $model->insert([
                    'user_id' => $targetUserId,
                    'nominal' => $harga,
                    'tanggal' => $tanggal . ' ' . date('H:i:s')
                ]);
            } else {
                if (!$nama) {
                    return $this->respond(['status' => 400, 'message' => 'Nama transaksi harus diisi'], 400);
                }
                $this->reportModel->ensureDateExists($tanggal);
                $this->reportModel->insert([
                    'user_id'           => $user['id'],
                    'nama_transaksi'    => $nama,
                    'harga'             => $tipe === 'keluar' ? $harga : 0,
                    'harga_masuk'       => $tipe === 'masuk' ? $harga : 0,
                    'tipe_transaksi'    => $tipe,
                    'tanggal_transaksi' => $tanggal . ' ' . date('H:i:s'),
                ]);
            }

            return $this->respond(['status' => 200, 'message' => 'Transaksi berhasil ditambahkan']);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function deleteTransaction($fullId)
    {
        $user = $this->checkAuth();
        if (!$user) return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);

        try {
            $parts = explode('_', $fullId);
            if (count($parts) < 2) {
                return $this->respond(['status' => 400, 'message' => 'ID tidak valid'], 400);
            }

            $type = $parts[0];
            $id = $parts[1];

            if ($type === 'manual') {
                $this->reportModel->delete($id);
            } elseif ($type === 'cashbon') {
                $cashbonModel = new \App\Models\CashbonModel();
                $cashbonModel->delete($id);
            } elseif ($type === 'bonus') {
                $bonusModel = new \App\Models\BonusModel();
                $bonusModel->delete($id);
            } else {
                return $this->respond(['status' => 400, 'message' => 'Tipe transaksi tidak bisa dihapus'], 400);
            }

            return $this->respond(['status' => 200, 'message' => 'Transaksi berhasil dihapus']);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateModalAwal()
    {
        $user = $this->checkAuth();
        if (!$user) return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);

        try {
            $input      = $this->request->getJSON(true) ?? $this->request->getPost();
            $modalAwal  = (int)($input['modal_awal'] ?? 0);
            $date       = $input['date'] ?? date('Y-m-d');

            $this->reportModel->ensureDateExists($date);
            $this->reportModel->updateModalAwalByDate($date, $modalAwal);

            return $this->respond(['status' => 200, 'message' => 'Modal awal berhasil disimpan']);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function exportDaily()
    {
        $user = $this->checkAuth();
        if (!$user) return $this->response->setStatusCode(401)->setBody('Unauthorized');

        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $db = \Config\Database::connect();

        // 1. Data Gathering
        $manual = $this->reportModel->where('DATE(tanggal_transaksi)', $date)->findAll();
        $paid = $db->table('payments as p')
            ->select('p.amount, p.payment_date, t.task_name, p.task_id')
            ->join('tasks as t', 'p.task_id = t.id', 'LEFT')
            ->where('p.status', 'paid')
            ->where('DATE(p.payment_date)', $date)
            ->orderBy('p.payment_date', 'DESC')
            ->get()->getResultArray();

        $allTransactions = [];
        foreach ($manual as $t) {
            $allTransactions[] = [
                'tanggal_transaksi' => $t['tanggal_transaksi'],
                'nama_transaksi' => $t['nama_transaksi'],
                'harga' => $t['tipe_transaksi'] === 'keluar' ? $t['harga'] : 0,
                'harga_masuk' => $t['tipe_transaksi'] === 'masuk' ? $t['harga_masuk'] : 0,
                'tipe_transaksi' => $t['tipe_transaksi'],
                'source' => 'Manual'
            ];
        }
        foreach ($paid as $p) {
            $allTransactions[] = [
                'tanggal_transaksi' => $p['payment_date'],
                'nama_transaksi' => $p['task_name'] ?? 'Task #' . $p['task_id'],
                'harga' => 0,
                'harga_masuk' => $p['amount'],
                'tipe_transaksi' => 'masuk',
                'source' => 'System'
            ];
        }
        usort($allTransactions, fn($a, $b) => strtotime($b['tanggal_transaksi']) - strtotime($a['tanggal_transaksi']));

        $totalBelanja = $db->table('reports')->where('tipe_transaksi', 'keluar')->where('DATE(tanggal_transaksi)', $date)->selectSum('harga', 'total')->get()->getRow();
        $totalMasukManual = $db->table('reports')->where('tipe_transaksi', 'masuk')->where('DATE(tanggal_transaksi)', $date)->selectSum('harga_masuk', 'total')->get()->getRow();
        $totalMasukPaid = $db->table('payments')->where('status', 'paid')->where('DATE(payment_date)', $date)->selectSum('amount', 'total_paid')->get()->getRow();

        $modalAwal = (int)($this->reportModel->getModalAwalByDate($date) ?? 0);
        $totalMasukValue = ((int) ($totalMasukManual->total ?? 0)) + ((int) ($totalMasukPaid->total_paid ?? 0));
        $totalBelanjaValue = (int) ($totalBelanja->total ?? 0);
        $totalBalance = $modalAwal + $totalMasukValue - $totalBelanjaValue;

        $attendance = $this->attendanceDataForDate($date);

        // 2. Generate HTML (Match Website exportReport)
        $filename = "Report_" . $date . ".xls";
        
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
        $html .= '<tr><th class="th-title" colspan="6">Laporan Transaksi & Kehadiran - ' . $date . '</th></tr>';
        $html .= '<tr><td colspan="3"><strong>Modal Awal</strong></td><td colspan="3">' . number_format($modalAwal, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Total Masuk</strong></td><td colspan="3">' . number_format($totalMasukValue, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Total Belanja</strong></td><td colspan="3">' . number_format($totalBelanjaValue, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="3"><strong>Balance</strong></td><td colspan="3">' . number_format($totalBalance, 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td colspan="6"></td></tr>';

        $html .= '<tr><th class="th-header">Tanggal</th><th class="th-header">Nama</th><th class="th-header">Tipe</th><th class="th-header">Harga</th><th class="th-header">Harga Masuk</th><th class="th-header">Sumber</th></tr>';
        foreach ($allTransactions as $trx) {
            $html .= '<tr>' .
                '<td>' . $trx['tanggal_transaksi'] . '</td>' .
                '<td>' . $trx['nama_transaksi'] . '</td>' .
                '<td>' . ucfirst($trx['tipe_transaksi']) . '</td>' .
                '<td>' . number_format($trx['harga'], 0, ',', '.') . '</td>' .
                '<td>' . number_format($trx['harga_masuk'], 0, ',', '.') . '</td>' .
                '<td>' . $trx['source'] . '</td>' .
                '</tr>';
        }

        $html .= '<tr><td colspan="6"></td></tr>';
        $html .= '<tr><th class="th-header">Total Karyawan</th><th class="th-header">Hadir</th><th class="th-header">Sakit</th><th class="th-header">Alfa</th><th class="th-header">Presentase</th><th class="th-header">Tanggal</th></tr>';
        $html .= '<tr><td>' . $attendance['total_users'] . '</td><td class="val-hadir">' . $attendance['hadir'] . '</td><td class="val-sakit">' . $attendance['sakit'] . '</td><td class="val-alfa">' . $attendance['alfa'] . '</td><td>' . $attendance['attendance_percentage'] . '%</td><td>' . $date . '</td></tr>';
        $html .= '<tr><td colspan="6"></td></tr>';

        $html .= '<tr><th colspan="2" class="th-header">Username</th><th colspan="4" class="th-header">Status</th></tr>';
        foreach ($attendance['status_users'] as $su) {
            $colorClass = 'val-alfa';
            if ($su['status'] === 'hadir') $colorClass = 'val-hadir';
            elseif ($su['status'] === 'sakit') $colorClass = 'val-sakit';

            $html .= '<tr><td colspan="2">' . $su['username'] . '</td><td colspan="4" class="' . $colorClass . '">' . ucfirst($su['status']) . '</td></tr>';
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
}
