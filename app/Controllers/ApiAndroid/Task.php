<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TaskModel;

class Task extends ResourceController
{
    protected function checkAuth()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        return $userId ? $userId : false;
    }

    public function list()
    {
        if (!$this->checkAuth()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $model = new TaskModel();
        // Return all tasks for now, ordered by newest
        $tasks = $model->orderBy('id', 'DESC')->findAll();

        return $this->respond([
            'status' => 200,
            'message' => 'Berhasil mengambil daftar tugas',
            'data' => $tasks
        ]);
    }

    public function detail($id)
    {
        if (!$this->checkAuth()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $model = new TaskModel();
        $task = $model->find($id);

        if ($task) {
            return $this->respond([
                'status' => 200,
                'message' => 'Berhasil mengambil detail tugas',
                'data' => $task
            ]);
        }

        return $this->respond(['status' => 404, 'message' => 'Tugas tidak ditemukan'], 404);
    }

    public function store()
    {
        if (!$this->checkAuth()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $model = new TaskModel();
        $data = [
            'task_name'  => $this->request->getPost('task_name'),
            'consumer'   => $this->request->getPost('consumer'),
            'status'     => $this->request->getPost('status'),
            'date_entry' => $this->request->getPost('date_entry'),
            'note'       => $this->request->getPost('note'),
        ];

        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadedName = $this->uploadToNextcloud($file);
            if ($uploadedName) {
                $data['image'] = $uploadedName;
            }
        }

        if ($model->insert($data)) {
            return $this->respond([
                'status' => 200,
                'message' => 'Task berhasil ditambahkan',
                'task_id' => $model->getInsertID()
            ]);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal menambahkan task'], 500);
    }

    public function update($id = null)
    {
        if (!$this->checkAuth()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        if (!$id) $id = $this->request->getPost('id');

        $model = new TaskModel();
        $task = $model->find($id);

        if (!$task) {
            return $this->respond(['status' => 404, 'message' => 'Tugas tidak ditemukan'], 404);
        }

        $data = [
            'task_name'  => $this->request->getPost('task_name') ?? $task['task_name'],
            'consumer'   => $this->request->getPost('consumer') ?? $task['consumer'],
            'status'     => $this->request->getPost('status') ?? $task['status'],
            'date_entry' => $this->request->getPost('date_entry') ?? $task['date_entry'],
            'note'       => $this->request->getPost('note') ?? $task['note'],
        ];

        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadedName = $this->uploadToNextcloud($file);
            if ($uploadedName) {
                $data['image'] = $uploadedName;
            }
        }

        if ($model->update($id, $data)) {
            return $this->respond([
                'status' => 200,
                'message' => 'Tugas berhasil diperbarui',
                'data' => $data
            ]);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal memperbarui tugas'], 500);
    }

    public function delete($id = null)
    {
        if (!$this->checkAuth()) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $model = new TaskModel();
        $task = $model->find($id);

        if (!$task) {
            return $this->respond(['status' => 404, 'message' => 'Tugas tidak ditemukan'], 404);
        }

        // Delete from Nextcloud if image exists
        if (!empty($task['image'])) {
            $this->deleteFromNextcloud($task['image']);
        }

        if ($model->delete($id)) {
            return $this->respond([
                'status' => 200,
                'message' => 'Tugas berhasil dihapus'
            ]);
        }

        return $this->respond(['status' => 500, 'message' => 'Gagal menghapus tugas'], 500);
    }

    private function uploadToNextcloud($file)
    {
        $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientName());
        $filename = basename($fileName);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/{$filename}";

        $fp = fopen($file->getTempName(), 'r');
        if (!$fp) {
            return false;
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->getTempName()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        return ($httpCode == 201 || $httpCode == 204) ? $fileName : null;
    }

    private function deleteFromNextcloud($fileName)
    {
        $filename = basename($fileName);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/{$filename}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_exec($ch);
        curl_close($ch);
    }

    public function getImage($filename = null)
    {
        if (!$filename) return $this->response->setStatusCode(400)->setBody("Filename required");
        
        $filename = basename($filename);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/" . urlencode($filename);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $image = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return $this->response->setStatusCode(500)->setBody('Curl Error: ' . $error_msg);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode != 200) {
            return $this->response->setStatusCode(404)->setBody("Image not found. Code: $httpCode");
        }

        return $this->response
            ->setHeader('Content-Type', $contentType ?: 'image/jpeg')
            ->setBody($image);
    }
}
