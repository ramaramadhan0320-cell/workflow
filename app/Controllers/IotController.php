<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class IotController extends Controller
{
    use ResponseTrait;

    public function receiveData()
    {
        try {
            // 1. Tangkap data Base64 dari IoT device
            $raw_data = $this->request->getGet('data');
            if (!$raw_data) {
                return $this->fail('Data parameter is required', 400);
            }

            // 2. Dekripsi Data (AES-256)
            $encrypter = \Config\Services::encrypter();

            // Decode base64 dulu, baru decrypt
            $decrypted_json = $encrypter->decrypt(base64_decode($raw_data));
            $payload = json_decode($decrypted_json, true);

            if (!$payload || !isset($payload['id_user']) || !isset($payload['username']) || !isset($payload['password'])) {
                return $this->fail('Invalid payload structure', 400);
            }

            // 3. Ambil Hash Bcrypt dari Database
            $db = \Config\Database::connect();
            $user = $db->table('users')
                       ->where('username', $payload['username'])
                       ->get()->getRow();

            if (!$user) {
                return $this->fail('User not found', 404);
            }

            // 4. Verifikasi Password Plain vs Bcrypt Hash
            if (!password_verify($payload['password'], $user->password)) {
                return $this->fail('Invalid credentials', 401);
            }

            // 5. Simpan log absensi ke tabel iot_attendance
            $attendanceData = [
                'user_id'    => $payload['id_user'],
                'username'   => $payload['username'],
                'scan_time'  => date('Y-m-d H:i:s'),
                'device_ip'  => $this->request->getIPAddress(),
                'status'     => 'SUCCESS',
                'raw_data'   => $raw_data
            ];

            $db->table('iot_attendance')->insert($attendanceData);

            // 6. Return success response
            return $this->respond([
                'status'  => 'success',
                'message' => 'Access granted for user: ' . $user->username,
                'user_id' => $user->id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            log_message('error', 'IoT API Error: ' . $e->getMessage());
            return $this->fail('Data decryption failed or invalid format', 400);
        }
    }

    // Method untuk testing - generate QR code terenkripsi
    public function generateQr($userId = null)
    {
        if (!$userId) {
            return $this->fail('User ID is required', 400);
        }

        try {
            // Ambil data user dari database
            $db = \Config\Database::connect();
            $user = $db->table('users')->where('id', $userId)->get()->getRow();

            if (!$user) {
                return $this->fail('User not found', 404);
            }

            // Generate password sementara (bisa diganti dengan logic lain)
            $tempPassword = bin2hex(random_bytes(8)); // 16 karakter hex

            $encrypter = \Config\Services::encrypter();

            $data = [
                "id_user"   => (string)$user->id,
                "username"  => $user->username,
                "password"  => $tempPassword
            ];

            // Enkripsi JSON menjadi string acak, lalu base64 agar aman di URL
            $ciphertext = base64_encode($encrypter->encrypt(json_encode($data)));

            return $this->respond([
                'status' => 'success',
                'qr_data' => $ciphertext,
                'temp_password' => $tempPassword, // Untuk testing saja
                'user' => $user->username
            ]);

        } catch (\Exception $e) {
            log_message('error', 'QR Generation Error: ' . $e->getMessage());
            return $this->fail('Failed to generate QR code', 500);
        }
    }

    // Method untuk generate string terenkripsi untuk QR code
    public function generateString()
    {
        try {
            // Get authenticated user instead of hardcoded credentials
            $userId = session()->get('user_id');
            $username = session()->get('username');
            
            if (!$userId || !$username) {
                return $this->fail('User not authenticated', 401);
            }

            $encrypter = \Config\Services::encrypter();

            $data = [
                "id_user"   => $userId,
                "username"  => $username,
                // SECURITY: Never include password in response
                "timestamp" => time()
            ];

            // Enkripsi JSON menjadi string acak, lalu base64 agar aman di URL
            $ciphertext = base64_encode($encrypter->encrypt(json_encode($data)));

            return $this->respond([
                'status' => 'success',
                'encrypted_string' => $ciphertext,
                'user' => $data['username']
            ]);

        } catch (\Exception $e) {
            log_message('error', 'String Generation Error: ' . $e->getMessage());
            return $this->fail('Failed to generate encrypted string', 500);
        }
    }
}