<?php

namespace App\Controllers\ApiAndroid;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Roles extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function validateAdmin()
    {
        $userId = $this->request->getHeaderLine('X-User-Id');
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'message' => 'Unauthorized'
            ]);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'message' => 'User not found'
            ]);
        }

        if (($user['role'] ?? '') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.'
            ]);
        }

        return $user;
    }

    public function list()
    {
        $admin = $this->validateAdmin();
        if ($admin instanceof \CodeIgniter\HTTP\Response) {
            return $admin; // Unauthorized or Forbidden
        }

        $users = $this->userModel->findAll();

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Success',
            'data' => $users,
            'current_user_id' => $admin['id']
        ]);
    }

    public function store()
    {
        $admin = $this->validateAdmin();
        if ($admin instanceof \CodeIgniter\HTTP\Response) {
            return $admin;
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[admin,karyawan,user]',
            'gaji_total' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal: ' . implode(', ', $this->validator->getErrors()),
                'errors' => $this->validator->getErrors()
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $data = [
            'username' => $input['username'] ?? null,
            'password' => isset($input['password']) ? password_hash($input['password'], PASSWORD_DEFAULT) : null,
            'role' => $input['role'] ?? null,
            'gaji_total' => $input['gaji_total'] ?? null,
            'can_announce' => $input['can_announce'] ?? 0
        ];

        if ($this->userModel->insert($data)) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'User berhasil ditambahkan',
                'user_id' => $this->userModel->getInsertID()
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'message' => 'Gagal menambahkan user'
            ]);
        }
    }

    public function update($id)
    {
        $admin = $this->validateAdmin();
        if ($admin instanceof \CodeIgniter\HTTP\Response) {
            return $admin;
        }

        $id = (int)$id;
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'User ID dibutuhkan'
            ]);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'User tidak ditemukan'
            ]);
        }

        $rules = [
            'username' => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
            'role' => 'required|in_list[admin,karyawan,user]',
            'gaji_total' => 'required|numeric'
        ];

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $password = $input['password'] ?? null;
        if (!empty($password)) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal: ' . implode(', ', $this->validator->getErrors()),
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'username' => $input['username'] ?? null,
            'role' => $input['role'] ?? null,
            'gaji_total' => $input['gaji_total'] ?? null,
            'can_announce' => $input['can_announce'] ?? 0
        ];

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($this->userModel->update($id, $data)) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'User berhasil diperbarui'
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'message' => 'Gagal memperbarui user'
            ]);
        }
    }

    public function delete($id)
    {
        $admin = $this->validateAdmin();
        if ($admin instanceof \CodeIgniter\HTTP\Response) {
            return $admin;
        }

        // Prevent deleting self
        if ((int)$id === (int)$admin['id']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Tidak dapat menghapus akun Anda sendiri'
            ]);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'User tidak ditemukan'
            ]);
        }

        if ($this->userModel->delete($id)) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'User berhasil dihapus'
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'message' => 'Gagal menghapus user'
            ]);
        }
    }
}
