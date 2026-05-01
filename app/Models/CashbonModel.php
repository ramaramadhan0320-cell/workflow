<?php

namespace App\Models;

use CodeIgniter\Model;

class CashbonModel extends Model
{
    protected $table      = 'cashbon';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'tanggal',
        'nominal'
    ];

    protected $useTimestamps = false; 

    /**
     * Fungsi untuk mendapatkan total cashbon user
     * @param int $user_id
     * @return int
     */
    public function getTotalCashbon($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->selectSum('nominal')
                    ->first()['nominal'] ?? 0;
    }

    /**
     * Fungsi untuk mendapatkan semua cashbon user
     * @param int $user_id
     * @return array
     */
    public function getCashbonByUser($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }
}
