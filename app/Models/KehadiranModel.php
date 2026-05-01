<?php

namespace App\Models;

use CodeIgniter\Model;

class KehadiranModel extends Model
{
    protected $table      = 'kehadiran';
    protected $primaryKey = 'id';

    // Disarankan menggunakan returnType 'array' atau 'object' secara eksplisit
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'tanggal',
        'status',
        'created_at'
    ];

    /**
     * Tips: Jika kolom di database bernama 'created_at', 
     * lebih baik aktifkan useTimestamps agar CI4 yang mengisinya secara otomatis.
     */
    protected $useTimestamps = true; 
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // Kosongkan jika tidak ada kolom updated_at

    /**
     * Fungsi untuk mengecek apakah user sudah absen hari ini
     * * @param int $user_id
     * @return array|null
     */
    public function cekAbsenHariIni($user_id)
    {
        // Menggunakan date('Y-m-d') sudah benar untuk membandingkan tanggal saja
        return $this->where('user_id', $user_id)
                    ->where('tanggal', date('Y-m-d'))
                    ->first();
    }

    /**
     * Bonus: Fungsi untuk mengambil histori bulanan user tertentu
     */
    public function getHistoriBulanan($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }
}