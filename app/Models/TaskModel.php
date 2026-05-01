<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $allowedFields = [
    'task_name',
    'consumer',
    'status',
    'date_entry',
    'image', // 🔥 INI WAJIB ADA
    'note'
];
}