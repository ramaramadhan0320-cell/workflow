<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'invoice_number',
        'amount',
        'status',
        'invoice_date',
        'due_date',
        'remarks'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'invoice_number' => 'required|string|max_length[50]',
        'amount' => 'required|numeric',
        'status' => 'required|in_list[pending,paid,partial,overdue]',
        'due_date' => 'permit_empty|valid_date'
    ];

    public function getPaymentsByUser($userId)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function getPaymentById($id)
    {
        return $this->find($id);
    }
}
