<?php

namespace App\Controllers\ApiAndroid;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\UserModel;

class Announcement extends BaseController
{
    protected $announcementModel;
    protected $userModel;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->userModel = new UserModel();
    }

    private function validateUser()
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

        return $user;
    }

    public function list()
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $isAdmin = ($user['role'] === 'admin');
        $announcements = $this->announcementModel->getVisibleAnnouncements($user['id'], $isAdmin);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Success',
            'data' => $announcements,
            'can_create' => ($isAdmin || ($user['can_announce'] ?? 0) == 1),
            'is_admin' => $isAdmin
        ]);
    }

    public function store()
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $isAdmin = ($user['role'] === 'admin');
        $canCreate = ($isAdmin || ($user['can_announce'] ?? 0) == 1);

        if (!$canCreate) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Anda tidak memiliki akses untuk membuat pengumuman'
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';

        if (empty($title) || empty($content)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Judul dan konten wajib diisi'
            ]);
        }

        $data = [
            'user_id' => $user['id'],
            'title' => $title,
            'content' => $content,
            'status' => $isAdmin ? 'approved' : 'pending'
        ];

        if ($this->announcementModel->insert($data)) {
            $msg = $isAdmin ? 'Pengumuman berhasil dipublikasikan' : 'Pengumuman berhasil diajukan dan menunggu persetujuan admin';
            return $this->response->setJSON([
                'status' => 200,
                'message' => $msg
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menyimpan pengumuman'
        ]);
    }

    public function approve($id)
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        if ($user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Hanya admin yang bisa menyetujui'
            ]);
        }

        if ($this->announcementModel->update($id, ['status' => 'approved'])) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Pengumuman disetujui'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menyetujui'
        ]);
    }

    public function reject($id)
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        if ($user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Hanya admin yang bisa menolak'
            ]);
        }

        if ($this->announcementModel->update($id, ['status' => 'rejected'])) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Pengumuman ditolak'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menolak'
        ]);
    }

    public function delete($id)
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) {
            return $user;
        }

        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'Data tidak ditemukan'
            ]);
        }

        // Only admin can delete
        if ($user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Hanya admin yang dapat menghapus pengumuman'
            ]);
        }

        if ($this->announcementModel->delete($id)) {
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Pengumuman dihapus'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menghapus'
        ]);
    }

    public function togglePermission($userId)
    {
        $admin = $this->validateUser();
        if ($admin instanceof \CodeIgniter\HTTP\Response) {
            return $admin;
        }

        if ($admin['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 403,
                'message' => 'Akses ditolak'
            ]);
        }

        $targetUser = $this->userModel->find($userId);
        if (!$targetUser) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'User tidak ditemukan'
            ]);
        }

        $newVal = ($targetUser['can_announce'] ?? 0) == 1 ? 0 : 1;

        if ($this->userModel->update($userId, ['can_announce' => $newVal])) {
            return $this->response->setJSON([
                'status' => 200,
                'success' => true,
                'new_status' => $newVal,
                'message' => 'Izin berhasil diubah'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal mengubah izin'
        ]);
    }
}
