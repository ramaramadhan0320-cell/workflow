<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskPaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['task_id', 'amount', 'payment_method', 'status', 'payment_date'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $validationRules = [
        'task_id' => 'required|numeric',
        'amount' => 'required|numeric',
        'payment_method' => 'required|in_list[transfer,cash,e-wallet]',
        'status' => 'required|in_list[unpaid,paid]',
        'payment_date' => 'permit_empty|valid_date',
    ];

    public function getPaymentsByTask($taskId)
    {
        return $this->where('task_id', $taskId)->findAll();
    }

    public function getLatestPayment($taskId)
    {
        return $this->where('task_id', $taskId)->orderBy('created_at', 'DESC')->first();
    }
}
