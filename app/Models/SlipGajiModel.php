<?php

namespace App\Models;

use CodeIgniter\Model;

class SlipGajiModel extends Model
{
    protected $table      = 'slip_gaji';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['user_id', 'filename', 'status', 'sent_at', 'created_at', 'updated_at'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get latest slip gaji for a user
     */
    public function getLatestSlip($userId)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Check if user has sent slip
     */
    public function hasSentSlip($userId)
    {
        $slip = $this->where('user_id', $userId)
                     ->where('status', 'sent')
                     ->orderBy('sent_at', 'DESC')
                     ->first();
        
        return $slip !== null;
    }

    /**
     * Get sent slip if exists
     */
    public function getSentSlip($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'sent')
                    ->orderBy('sent_at', 'DESC')
                    ->first();
    }

    /**
     * Mark slip as sent
     */
    public function markAsSent($userId, $filename)
    {
        return $this->insert([
            'user_id'  => $userId,
            'filename' => $filename,
            'status'   => 'sent',
            'sent_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update slip status
     */
    public function updateStatus($userId, $status)
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'sent')
                    ->set(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
}
