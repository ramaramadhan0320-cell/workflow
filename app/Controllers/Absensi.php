<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\KehadiranModel;

class Absensi extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userModel = new UserModel();
        $userId = session()->get('id');
        $user = $userModel->find($userId);

        if (!$user) {
            return redirect()->to('/')->with('error', 'User tidak ditemukan.');
        }

        if (empty($user['secret_key'])) {
            $secretKey = bin2hex(random_bytes(16));
            $userModel->update($userId, ['secret_key' => $secretKey]);
            $user['secret_key'] = $secretKey;
        }

        $data['user'] = $user;
        $data['username'] = $user['username'];
        $data['qrTimestamp'] = time();
        $data['qrPayload'] = $user['id'] . ':' . $data['qrTimestamp'] . ':' . hash_hmac('sha256', $user['id'] . $data['qrTimestamp'], $user['secret_key']);

        $kehadiranModel = new KehadiranModel();

        // Get semua kehadiran user
        $data['kehadiran'] = $kehadiranModel->where('user_id', $userId)
            ->orderBy('tanggal', 'DESC')
            ->findAll();

        // Cek apakah sudah absen hari ini
        $todayDate = date('Y-m-d');
        $todayAttendance = $kehadiranModel->where('user_id', $userId)
            ->where('tanggal', $todayDate)
            ->first();

        $data['sudah_absen'] = $todayAttendance ? true : false;

        return view('absensi', $data);
    }

    public function absen()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userModel = new UserModel();
        $userId = session()->get('id');
        $user = $userModel->find($userId);
        
        $status = $this->request->getPost('status');
        $tanggal = date('Y-m-d');
        $jam = date('H:i:s');

        $kehadiranModel = new KehadiranModel();
        
        // Cek apakah sudah absen hari ini
        $sudahAbsen = $kehadiranModel->where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->first();

        if ($sudahAbsen) {
            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setHeader('Content-Type', 'application/json; charset=utf-8')
                    ->setBody(json_encode([
                        'status' => 'error',
                        'message' => 'Anda sudah absen hari ini'
                    ]));
            }
            return redirect()->to('/absensi')->with('error', 'Anda sudah absen hari ini');
        }

        $kehadiranModel->insert([
            'user_id' => $userId,
            'tanggal' => $tanggal,
            'jam_masuk' => $jam,
            'status' => $status,
        ]);

        if ($this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode([
                    'status' => 'success',
                    'message' => 'Absensi berhasil dicatat'
                ]));
        }

        return redirect()->to('/absensi')->with('success', 'Absensi berhasil dicatat');
    }
}
