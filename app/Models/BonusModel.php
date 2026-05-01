<?php

namespace App\Models;

use CodeIgniter\Model;

class BonusModel extends Model
{
    protected $table      = 'bonus';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'tanggal',
        'nominal'
    ];

    protected $useTimestamps = false; 

    /**
     * Fungsi untuk mendapatkan total bonus user
     * @param int $user_id
     * @return int
     */
    public function getTotalBonus($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->selectSum('nominal')
                    ->first()['nominal'] ?? 0;
    }

    /**
     * Fungsi untuk mendapatkan semua bonus user
     * @param int $user_id
     * @return array
     */
    public function getBonusByUser($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }
}
