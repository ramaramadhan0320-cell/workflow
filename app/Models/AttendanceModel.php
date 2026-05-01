<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table      = 'attendance_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'username', 'scan_time', 'status', 'device_id'];
}
