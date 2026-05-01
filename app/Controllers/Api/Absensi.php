<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AttendanceModel;

class Absensi extends ResourceController
{
    public function submit()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

        $json = $this->request->getJSON();
        if (!$json || !isset($json->raw_data)) {
            return $this->response->setJSON(['status' => 'Gagal', 'pesan' => 'Data Kosong']);
        }

        $rawData = trim($json->raw_data);
        $parts = explode(':', $rawData);
        $userId = $parts[0] ?? null;
        $timestamp = $parts[1] ?? null;
        $signature = $parts[2] ?? null;

        if (empty($userId) || empty($timestamp) || empty($signature)) {
            return $this->response->setJSON(['status' => 'Gagal', 'pesan' => 'Format QR tidak valid']);
        }

        try {
            $db = \Config\Database::connect();
            $user = $db->table('users')->where('id', $userId)->get()->getRow();

            if (!$user) {
                return $this->response->setJSON(['status' => 'Gagal', 'pesan' => 'User tidak ditemukan']);
            }

            $expected = hash_hmac('sha256', $userId . $timestamp, $user->secret_key);
            if (!hash_equals($expected, $signature)) {
                return $this->response->setJSON([
                    'status' => 'Ditolak',
                    'pesan'  => 'Kartu Tidak Valid atau Palsu!'
                ]);
            }

            $hariIni = date('Y-m-d');
            $sudahAbsen = $db->table('kehadiran')
                             ->where('user_id', $userId)
                             ->where('tanggal', $hariIni)
                             ->get()
                             ->getRow();

            if ($sudahAbsen) {
                return $this->response->setJSON([
                    'status' => 'Ditolak',
                    'pesan'  => 'Anda sudah absen hari ini. Sampai jumpa besok!'
                ]);
            }

            $db->table('kehadiran')->insert([
                'user_id'    => $userId,
                'tanggal'    => $hariIni,
                'status'     => 'Hadir',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'status' => 'Berhasil',
                'pesan'  => 'Absensi Berhasil untuk ID: ' . $userId
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'Error', 'pesan' => $e->getMessage()]);
        }
    }
}
