<?php

namespace App\Controllers;

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

    private function checkAuth()
    {
        $userId = session()->get('id');
        if (!$userId) return null;
        return $this->userModel->find($userId);
    }

    public function index()
    {
        $user = $this->checkAuth();
        if (!$user) return redirect()->to('/login');

        $isAdmin = (($user['role'] ?? '') === 'admin');
        $data = [
            'user' => $user,
            'announcements' => $this->announcementModel->getVisibleAnnouncements($user['id'], $isAdmin),
            'can_create' => ($isAdmin || ($user['can_announce'] ?? 0) == 1)
        ];

        return view('announcements/index', $data);
    }

    public function store()
    {
        $user = $this->checkAuth();
        if (!$user) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $isAdmin = (($user['role'] ?? '') === 'admin');
        $canCreate = ($isAdmin || ($user['can_announce'] ?? 0) == 1);

        if (!$canCreate) {
            return $this->response->setJSON(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membuat pengumuman']);
        }

        $title = $this->request->getPost('title');
        $content = $this->request->getPost('content');

        if (empty($title) || empty($content)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Judul dan konten wajib diisi']);
        }

        $data = [
            'user_id' => $user['id'],
            'title' => $title,
            'content' => $content,
            'status' => $isAdmin ? 'approved' : 'pending' // Admin auto-approves their own
        ];

        if ($this->announcementModel->insert($data)) {
            $msg = $isAdmin ? 'Pengumuman berhasil dipublikasikan' : 'Pengumuman berhasil diajukan dan menunggu persetujuan admin';
            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyimpan pengumuman']);
    }

    public function approve($id)
    {
        $user = $this->checkAuth();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya admin yang bisa menyetujui']);
        }

        if ($this->announcementModel->update($id, ['status' => 'approved'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Pengumuman disetujui']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyetujui']);
    }

    public function reject($id)
    {
        $user = $this->checkAuth();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya admin yang bisa menolak']);
        }

        if ($this->announcementModel->update($id, ['status' => 'rejected'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Pengumuman ditolak']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal menolak']);
    }

    public function delete($id)
    {
        $user = $this->checkAuth();
        if (!$user) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $announcement = $this->announcementModel->find($id);
        if (!$announcement) return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);

        // Only admin can delete
        if (($user['role'] ?? '') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya admin yang dapat menghapus pengumuman']);
        }

        if ($this->announcementModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Pengumuman dihapus']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal menghapus']);
    }

    public function permissions()
    {
        $user = $this->checkAuth();
        if (!$user || ($user['role'] ?? '') !== 'admin') return redirect()->to('/dashboard');

        $data = [
            'user' => $user,
            'users' => $this->userModel->findAll()
        ];

        return view('announcements/permissions', $data);
    }

    public function togglePermission($userId)
    {
        $admin = $this->checkAuth();
        if (!$admin || ($admin['role'] ?? '') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $targetUser = $this->userModel->find($userId);
        if (!$targetUser) return $this->response->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);

        $newVal = ($targetUser['can_announce'] ?? 0) == 1 ? 0 : 1;

        if ($this->userModel->update($userId, ['can_announce' => $newVal])) {
            return $this->response->setJSON(['success' => true, 'new_status' => $newVal]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengubah izin']);
    }
}
