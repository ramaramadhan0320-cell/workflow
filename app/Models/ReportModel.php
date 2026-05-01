<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'nama_transaksi',
        'harga',
        'harga_masuk',
        'tanggal_transaksi',
        'tipe_transaksi',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Get all transactions untuk user
    public function getUserTransactions($userId)
    {
        return $this->where('user_id', $userId)
            ->orderBy('tanggal_transaksi', 'DESC')
            ->findAll();
    }

    // Get summary laporan
    public function getReportSummary($userId)
    {
        // Total transaksi keluar (belanja)
        $totalBelanja = $this->where('user_id', $userId)
            ->where('tipe_transaksi', 'keluar')
            ->selectSum('harga', 'total')
            ->first();

        // Total transaksi masuk manual (dari reports table)
        $totalMasukManual = $this->where('user_id', $userId)
            ->where('tipe_transaksi', 'masuk')
            ->selectSum('harga_masuk', 'total')
            ->first();

        // Total dari payments yang status 'paid' (uang masuk otomatis)
        $db = \Config\Database::connect();
        $paymentResult = $db->table('payments')
            ->where('status', 'paid')
            ->selectSum('amount', 'total_paid')
            ->get()
            ->getRow();

        $totalMasukPaid = (int) ($paymentResult->total_paid ?? 0);

        // Count transaksi
        $countBelanja = $this->where('user_id', $userId)
            ->where('tipe_transaksi', 'keluar')
            ->countAllResults();

        $countMasuk = $this->where('user_id', $userId)
            ->where('tipe_transaksi', 'masuk')
            ->countAllResults();

        return [
            'total_belanja' => (int) ($totalBelanja['total'] ?? 0),
            'total_masuk_manual' => (int) ($totalMasukManual['total'] ?? 0),
            'total_masuk_paid' => $totalMasukPaid,
            'total_masuk' => ((int) ($totalMasukManual['total'] ?? 0)) + $totalMasukPaid,
            'count_belanja' => $countBelanja,
            'count_masuk' => $countMasuk,
        ];
    }

    // Get paid payments dengan task details
    public function getPaidPaymentsWithTasks()
    {
        $db = \Config\Database::connect();
        return $db->table('payments as p')
            ->select('p.id, p.amount, p.payment_date, t.task_name, p.task_id')
            ->join('tasks as t', 'p.task_id = t.id', 'LEFT')
            ->where('p.status', 'paid')
            ->orderBy('p.payment_date', 'DESC')
            ->get()
            ->getResultArray();
    }

    // Get modal awal untuk tanggal tertentu (per-date dari report_settings)
    public function getModalAwalByDate($date)
    {
        $db = \Config\Database::connect();
        $result = $db->table('report_settings')
            ->select('modal_awal')
            ->where('report_date', $date)
            ->get()
            ->getRow();
        
        return (int) ($result->modal_awal ?? 0);
    }

    // Update modal awal untuk tanggal tertentu (ke report_settings)
    public function updateModalAwalByDate($date, $amount)
    {
        $db = \Config\Database::connect();
        
        // Pastikan entry untuk tanggal itu ada
        $this->ensureDateExists($date);
        
        // Update modal awal
        return $db->table('report_settings')
            ->where('report_date', $date)
            ->update(['modal_awal' => $amount, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    // Pastikan entry untuk tanggal ada di report_settings
    public function ensureDateExists($date)
    {
        $db = \Config\Database::connect();
        
        $exists = $db->table('report_settings')
            ->where('report_date', $date)
            ->get()
            ->getRow();
        
        if (!$exists) {
            $db->table('report_settings')->insert([
                'report_date' => $date,
                'modal_awal' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

