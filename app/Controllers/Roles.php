<?php

namespace App\Controllers;

use App\Models\UserModel;

class Roles extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        // Check if user is admin
        $userId = session()->get('id');
        $currentUser = $this->userModel->find($userId);
        
        if (($currentUser['role'] ?? '') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
        }

        $data['users'] = $this->userModel->findAll();
        $data['user'] = $currentUser;

        return view('roles', $data);
    }

    public function store()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $currentUser = $this->userModel->find($userId);

        if (($currentUser['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Forbidden']);
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[admin,karyawan,user]',
            'gaji_total' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role'),
            'gaji_total' => $this->request->getPost('gaji_total')
        ];

        if ($this->userModel->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'User berhasil ditambahkan',
                'user_id' => $this->userModel->getInsertID()
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal menambahkan user'
            ]);
        }
    }

    public function update()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $currentUser = $this->userModel->find($userId);

        if (($currentUser['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Forbidden']);
        }

        $id = (int)$this->request->getPost('user_id');
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'User ID dibutuhkan']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);
        }

        $rules = [
            'username' => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
            'role' => 'required|in_list[admin,karyawan,user]',
            'gaji_total' => 'required|numeric'
        ];

        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'role' => $this->request->getPost('role'),
            'gaji_total' => $this->request->getPost('gaji_total')
        ];

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($this->userModel->update($id, $data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'User berhasil diperbarui']);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Gagal memperbarui user']);
        }
    }

    public function delete($id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $currentUser = $this->userModel->find($userId);

        if (($currentUser['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Forbidden']);
        }

        // Prevent deleting self
        if ((int)$id === (int)$userId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun Anda sendiri'
            ]);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ]);
        }

        if ($this->userModel->delete($id)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'User berhasil dihapus'
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus user'
            ]);
        }
    }
}
