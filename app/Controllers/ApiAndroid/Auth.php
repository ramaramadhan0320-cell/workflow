<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class Auth extends ResourceController
{
    public function login()
    {
        $model = new UserModel();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        $username = $input['username'] ?? null;
        $password = $input['password'] ?? null;

        if (!$username || !$password) {
            return $this->respond([
                'status' => 400,
                'message' => 'Username dan password wajib diisi'
            ], 400);
        }

        $user = $model->where('username', $username)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Generate API Token
            $token = base64_encode($user['id'] . ':' . $user['secret_key']);

            // Hilangkan field sensitive
            unset($user['password']);
            unset($user['secret_key']);

            return $this->respond([
                'status' => 200,
                'message' => 'Login berhasil',
                'data' => $user,
                'token' => $token // 🔥 Kirim token ke client
            ]);
        }

        return $this->respond([
            'status' => 401,
            'message' => 'Username atau password salah'
        ], 401);
    }

    public function profile()
    {
        $model = new UserModel();
        // ⚠️ SECURITY WARNING: X-User-Id header-based auth is weak and can be spoofed
        // TODO: Implement JWT tokens for proper API authentication
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');

        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized. User ID required'], 401);
        }

        $user = $model->find($userId);
        if ($user) {
            unset($user['password']);
            unset($user['secret_key']);
            return $this->respond([
                'status' => 200,
                'message' => 'Profile retrieved successfully',
                'data' => $user
            ]);
        }

        return $this->respond(['status' => 404, 'message' => 'User not found'], 404);
    }

    public function updateProfile()
    {
        // ⚠️ SECURITY WARNING: X-User-Id header-based auth is weak and can be spoofed
        // TODO: Implement proper authentication/authorization mechanism
        $userId = $this->request->getHeaderLine('X-User-Id');
        if (!$userId) return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);

        $model = new UserModel();
        $data = [
            'alamat' => $this->request->getPost('alamat'),
            'tempat_lahir' => $this->request->getPost('tempat_lahir'),
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir'),
            'pendidikan_terakhir' => $this->request->getPost('pendidikan_terakhir'),
            'tahun_mulai_bekerja' => $this->request->getPost('tahun_mulai_bekerja'),
            'email' => $this->request->getPost('email'),
        ];

        // Filter null values
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        // Handle Profile Photo Upload
        $file = $this->request->getFile('profile');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName = time() . '_' . $file->getRandomName();
            $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
            $nextcloudUrl = env('nextcloud.url', 'http://192.168.100.20:8080');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPass = env('nextcloud.password', 'CHANGE_ME_IN_PRODUCTION');

            $url = rtrim($nextcloudUrl, '/') . '/remote.php/dav/files/' . urlencode($nextcloudUser) . '/workflow/profile/' . $fileName;

            $fp = fopen($file->getTempName(), 'r');
            $ch = curl_init($url);
            // Use basic auth with credentials from environment variables
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPass);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->getTempName()));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Enable TLS verification; if connecting to HTTP, this will be ignored
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if (in_array($httpCode, [200, 201, 204])) {
                $data['profile'] = $fileName;
            }
        }

        if ($model->update($userId, $data)) {
            return $this->respond(['status' => 200, 'message' => 'Profil berhasil diperbarui']);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal memperbarui profil'], 500);
    }

    public function resetPassword()
    {
        $userId = $this->request->getHeaderLine('X-User-Id');
        if (!$userId) return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $newPassword = $input['new_password'] ?? null;

        if (!$newPassword) {
            return $this->respond(['status' => 400, 'message' => 'Password baru wajib diisi'], 400);
        }

        $model = new UserModel();
        if ($model->update($userId, ['password' => password_hash($newPassword, PASSWORD_DEFAULT)])) {
            return $this->respond(['status' => 200, 'message' => 'Password berhasil direset']);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal meriset password'], 500);
    }

    public function getProfileImage($filename)
    {
        $filename = basename($filename);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        // Fetch from Nextcloud with authentication
        $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/profile/" . urlencode($filename);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if ($httpCode == 200 && !empty($imageData)) {
            return $this->response
                ->setHeader('Content-Type', $contentType ?: 'image/jpeg')
                ->setBody($imageData);
        }

        return $this->response->setStatusCode(404)->setBody('File not found');
    }
}
