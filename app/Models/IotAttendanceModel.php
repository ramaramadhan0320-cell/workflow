<?php

namespace App\Models;

use CodeIgniter\Model;

class IotAttendanceModel extends Model
{
    protected $table = 'iot_attendance';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'status',
        'scan_time',
        'image_path',
        'device_id',
        'qr_content',
        'is_valid'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer',
        'status' => 'required|in_list[masuk,pulang,sakit,izin]',
        'scan_time' => 'required',
        'is_valid' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required',
            'integer' => 'User ID must be an integer',
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Status must be masuk, pulang, sakit, or izin',
        ],
        'scan_time' => [
            'required' => 'Scan time is required',
        ],
        'is_valid' => [
            'in_list' => 'Is valid must be 0 or 1',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $afterFind = [];
    protected $afterDelete = [];

    /**
     * Check if user already checked in today
     */
    public function hasCheckedInToday($userId)
    {
        $today = date('Y-m-d');
        return $this->where('user_id', $userId)
                   ->where('status', 'masuk')
                   ->where('DATE(scan_time)', $today)
                   ->countAllResults() > 0;
    }

    /**
     * Get attendance by date range
     */
    public function getAttendanceByDateRange($startDate, $endDate, $userId = null)
    {
        $builder = $this->select('iot_attendance.*, users.username')
                       ->join('users', 'users.id = iot_attendance.user_id')
                       ->where('scan_time >=', $startDate)
                       ->where('scan_time <=', $endDate)
                       ->orderBy('scan_time', 'DESC');

        if ($userId) {
            $builder->where('iot_attendance.user_id', $userId);
        }

        return $builder->findAll();
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats($startDate = null, $endDate = null)
    {
        if (!$startDate) $startDate = date('Y-m-01'); // First day of current month
        if (!$endDate) $endDate = date('Y-m-t'); // Last day of current month

        $result = $this->select('status, COUNT(*) as count')
                      ->where('DATE(scan_time) >=', $startDate)
                      ->where('DATE(scan_time) <=', $endDate)
                      ->groupBy('status')
                      ->findAll();

        $stats = [
            'masuk' => 0,
            'pulang' => 0,
            'sakit' => 0,
            'izin' => 0,
            'total' => 0
        ];

        foreach ($result as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }
}