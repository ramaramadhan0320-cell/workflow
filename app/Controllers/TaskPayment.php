<?php

namespace App\Controllers;

use App\Models\TaskPaymentModel;
use App\Models\TaskModel;

class TaskPayment extends BaseController
{
    // 🔹 STORE PAYMENT (INSERT OR UPDATE)
    public function store()
    {
        try {
            $db = \Config\Database::connect();
            $taskModel = new TaskModel();

            $taskId = $this->request->getPost('task_id');

            if (!$taskId) {
                return $this->response
                    ->setStatusCode(400)
                    ->setHeader('Content-Type', 'application/json; charset=utf-8')
                    ->setBody(json_encode(['status' => 400, 'message' => 'Task ID tidak ditemukan']));
            }

            // Ambil data dari form
            $amount = $this->request->getPost('amount');
            $paymentMethod = $this->request->getPost('payment_method');
            $status = $this->request->getPost('status');
            $paymentDate = $this->request->getPost('payment_date');

            // Validasi amount
            if (!$amount || $amount <= 0) {
                return $this->response
                    ->setStatusCode(400)
                    ->setHeader('Content-Type', 'application/json; charset=utf-8')
                    ->setBody(json_encode(['status' => 400, 'message' => 'Amount harus lebih besar dari 0']));
            }

            // Check apakah sudah ada payment untuk task ini
            $existingPayment = $db->table('payments')
                ->where('task_id', $taskId)
                ->get()
                ->getRowArray();

            if ($existingPayment) {
                // Jika sudah paid, jangan boleh update
                if ($existingPayment['status'] === 'paid') {
                    return $this->response
                        ->setStatusCode(400)
                        ->setHeader('Content-Type', 'application/json; charset=utf-8')
                        ->setBody(json_encode(['status' => 400, 'message' => 'Data sudah dibayar, tidak bisa diedit']));
                }

                // Update existing payment
                $db->table('payments')->where('task_id', $taskId)->update([
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                    'payment_date' => $paymentDate ?: null,
                ]);
            } else {
                // Insert new payment
                $db->table('payments')->insert([
                    'task_id' => $taskId,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                    'payment_date' => $paymentDate ?: null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Update task status menjadi 'process' jika sebelumnya pending/payment pending
            $task = $taskModel->find($taskId);
            if ($task && in_array(strtolower($task['status']), ['pending', 'payment pending'])) {
                $taskModel->update($taskId, ['status' => 'process']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 200, 'message' => 'Payment berhasil disimpan']));

        } catch (\Exception $e) {
            log_message('error', 'TaskPayment::store - ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    // 🔹 GET PAYMENT DATA
    public function getPayments($taskId)
    {
        try {
            $db = \Config\Database::connect();
            $payments = $db->table('payments')
                ->where('task_id', $taskId)
                ->get()
                ->getResultArray();

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 200, 'data' => $payments]));

        } catch (\Exception $e) {
            log_message('error', 'TaskPayment::getPayments - ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    // 🔹 DELETE PAYMENT
    public function delete($id)
    {
        try {
            $taskPaymentModel = new TaskPaymentModel();

            if (!$taskPaymentModel->delete($id)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setHeader('Content-Type', 'application/json; charset=utf-8')
                    ->setBody(json_encode(['status' => 400, 'message' => 'Gagal menghapus payment']));
            }

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 200, 'message' => 'Payment berhasil dihapus']));

        } catch (\Exception $e) {
            log_message('error', 'TaskPayment::delete - ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }
}
