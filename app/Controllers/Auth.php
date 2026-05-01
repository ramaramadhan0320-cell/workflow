<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        return view('login');
    }

    public function process()
    {
        $session = session();
        $model = new UserModel();

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Debug: log untuk check data yang dikirim
        log_message('debug', 'Login attempt - Username: ' . $username);

        $user = $model->where('username', $username)->first();

        if (!$user) {
            log_message('debug', 'User tidak ditemukan: ' . $username);
            return redirect()->back()->with('error', 'Username atau password salah');
        }

        if (!password_verify($password, $user['password'])) {
            log_message('debug', 'Password salah untuk user: ' . $username);
            return redirect()->back()->with('error', 'Username atau password salah');
        }

        // Regenerate session ID for security (prevent session fixation)
        $session->regenerate();

        // Login berhasil
        $session->set([
            'id' => $user['id'],
            'username' => $user['username'],
            'isLoggedIn' => true
        ]);

        log_message('info', 'Login sukses untuk user: ' . $username);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }
}