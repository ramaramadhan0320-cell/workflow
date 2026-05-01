<?php

namespace App\Models;

use CodeIgniter\Model;

class WithdrawalBypassModel extends Model
{
    protected $table = 'withdrawal_bypass';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'admin_id',
        'admin_username',
        'bypass_token',
        'created_at',
        'expires_at',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public function hasActiveBypass($userId)
    {
        $currentTime = date('Y-m-d H:i:s');
        return $this->where('user_id', $userId)
                   ->where('is_active', true)
                   ->where('expires_at >', $currentTime)
                   ->countAllResults() > 0;
    }

    public function getActiveBypass($userId)
    {
        $currentTime = date('Y-m-d H:i:s');
        return $this->where('user_id', $userId)
                   ->where('is_active', true)
                   ->where('expires_at >', $currentTime)
                   ->first();
    }

    public function createBypass($userId, $adminId, $adminUsername, $expiresInMinutes = 10)
    {
        $bypassToken = bin2hex(random_bytes(16));
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInMinutes} minutes"));

        $data = [
            'user_id' => $userId,
            'admin_id' => $adminId,
            'admin_username' => $adminUsername,
            'bypass_token' => $bypassToken,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
            'is_active' => true
        ];

        $this->insert($data);

        return [
            'token' => $bypassToken,
            'expires_at' => $expiresAt,
            'expires_in_minutes' => $expiresInMinutes
        ];
    }

    public function deactivateBypass($userId)
    {
        return $this->where('user_id', $userId)
                   ->set(['is_active' => false])
                   ->update();
    }

    public function cleanExpiredBypasses()
    {
        $currentTime = date('Y-m-d H:i:s');
        return $this->where('expires_at <=', $currentTime)
                   ->set(['is_active' => false])
                   ->update();
    }
}
