<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TaskModel;
use App\Models\KehadiranModel;
use App\Models\UserModel;

class Dashboard extends ResourceController
{
    public function summary()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');

        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized. Memerlukan user_id'], 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->respond(['status' => 404, 'message' => 'User tidak ditemukan'], 404);
        }

        $taskModel = new TaskModel();
        // Ambil 5 tugas terbaru
        $recentTasks = $taskModel->orderBy('id', 'DESC')->limit(5)->find();

        $kehadiranModel = new KehadiranModel();
        $recentKehadiran = $kehadiranModel->where('user_id', $userId)
                                          ->orderBy('tanggal', 'DESC')
                                          ->limit(5)
                                          ->find();

        return $this->respond([
            'status' => 200,
            'message' => 'Dashboard summary berhasil diambil',
            'data' => [
                'user' => [
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'gaji_total' => $user['gaji_total']
                ],
                'tasks_count' => $taskModel->countAllResults(),
                'recent_tasks' => $recentTasks,
                'recent_attendance' => $recentKehadiran
            ]
        ]);
    }
}
