<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\KehadiranModel;
use App\Models\UserModel;

class Absensi extends ResourceController
{
    protected function checkAuth()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        return $userId ? $userId : false;
    }

    public function history()
    {
        $userId = $this->checkAuth();
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $model = new KehadiranModel();
        $history = $model->where('user_id', $userId)
                         ->orderBy('tanggal', 'DESC')
                         ->findAll();

        $todayDate = date('Y-m-d');
        $todayAttendance = $model->where('user_id', $userId)
                                 ->where('tanggal', $todayDate)
                                 ->first();

        // Get User Biodata for Display
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        
        // Generate QR Payload (Sync with web logic)
        $qrTimestamp = time();
        $qrPayload = "";
        if ($user) {
            $qrPayload = $user['id'] . ':' . $qrTimestamp . ':' . hash_hmac('sha256', $user['id'] . $qrTimestamp, $user['secret_key'] ?? '');
            unset($user['password']);
            unset($user['secret_key']);
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Berhasil mengambil riwayat absensi',
            'data' => [
                'history' => $history,
                'already_checked_in' => $todayAttendance ? true : false,
                'today_status' => $todayAttendance ? $todayAttendance['status'] : null,
                'user' => $user,
                'qr_payload' => $qrPayload
            ]
        ]);
    }

    public function submit()
    {
        $userId = $this->checkAuth();
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $status = $this->request->getVar('status') ?: 'Hadir';
        $tanggal = date('Y-m-d');
        $jam = date('H:i:s');

        $model = new KehadiranModel();
        
        // Check if already checked in today
        $existing = $model->where('user_id', $userId)
                          ->where('tanggal', $tanggal)
                          ->first();

        if ($existing) {
            return $this->respond([
                'status' => 400,
                'message' => 'Anda sudah melakukan absensi hari ini'
            ], 400);
        }

        $data = [
            'user_id' => $userId,
            'tanggal' => $tanggal,
            'jam_masuk' => $jam,
            'status' => $status
        ];

        if ($model->insert($data)) {
            return $this->respond([
                'status' => 200,
                'message' => 'Absensi berhasil dicatat',
                'data' => $data
            ]);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal mencatat absensi'], 500);
    }
}
