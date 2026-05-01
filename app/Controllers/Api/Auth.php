<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class Auth extends ResourceController
{
    public function login()
    {
        $model = new UserModel();

        // Ambil JSON atau POST
        $input = $this->request->getJSON(true);

        if (!$input) {
            $input = $this->request->getPost();
        }

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
            return $this->respond([
                'status' => 200,
                'message' => 'Login berhasil',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
        }

        return $this->respond([
            'status' => 401,
            'message' => 'Username atau password salah'
        ], 401);
    }
}