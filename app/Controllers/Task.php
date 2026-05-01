<?php

namespace App\Controllers;

use App\Models\TaskModel;

class Task extends BaseController
{
    // 🔹 HALAMAN LIST
    public function detailList()
    {
        $model = new TaskModel();
        $data['tasks'] = $model->findAll();

        return view('detail', $data);
    }

    // 🔹 DETAIL PER TASK
    public function taskDetail($id)
    {
        $model = new TaskModel();
        $data['task'] = $model->find($id);

        return view('task_detail', $data);
    }

    // 🔹 UPDATE TASK + UPLOAD KE NEXTCLOUD
    public function updateTask()
    {
        $model = new TaskModel();

        $id = $this->request->getPost('id');

        if (!$id) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'ID tidak ditemukan'
            ]);
        }

        // ================= DATA =================
        $data = [
            'task_name' => $this->request->getPost('task_name'),
            'consumer'  => $this->request->getPost('consumer'),
            'status'    => $this->request->getPost('status'),
            'date_entry'=> $this->request->getPost('date_entry'),
            'note'      => $this->request->getPost('note'),
        ];

        // ================= FILE =================
        $file = $this->request->getFile('image');

        if ($file && $file->isValid() && !$file->hasMoved()) {

            // 🔥 nama file unik
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientName());

            $nextcloudUrl = env('nextcloud.url', 'http://192.168.100.20:8080');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPass = env('nextcloud.password', 'masterraden');

            $url = rtrim($nextcloudUrl, '/') . '/remote.php/dav/files/' . urlencode($nextcloudUser) . '/workflow/' . $fileName;

            $fp = fopen($file->getTempName(), 'r');
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPass);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->getTempName()));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                fclose($fp);
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Curl error',
                    'error' => $err
                ]);
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            fclose($fp);

            // 🔥 SUCCESS UPLOAD
            if (in_array($httpCode, [200, 201, 204])) {
                // ✅ SIMPAN HANYA NAMA FILE (PENTING!)
                $data['image'] = $fileName;
            } else {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Upload ke Nextcloud gagal',
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
            }
        }

        // ================= UPDATE DB =================
        $model->update($id, $data);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Update berhasil',
            'data' => $data
        ]);
    }

    // 🔹 PROXY IMAGE (BIAR PRIVATE NEXTCLOUD BISA DI AKSES)
    public function getImage($filename)
    {
        // 🔥 SECURITY (Path Traversal protection)
        $filename = basename($filename);

        $nextcloudUrl  = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPass = env('nextcloud.password', 'masterraden');
        $sslVerify     = env('nextcloud.ssl_verify', false);

        $url = $nextcloudUrl . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/" . $filename;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPass);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);

        $image = curl_exec($ch);

        if (curl_errno($ch)) {
            return $this->response->setStatusCode(500)
                ->setBody('Curl Error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if ($httpCode != 200) {
            return $this->response->setStatusCode(404)
                ->setBody("Image not found. Code: $httpCode");
        }

        return $this->response
            ->setHeader('Content-Type', $contentType ?: 'image/jpeg')
            ->setBody($image);
    }
    private function uploadToNextcloud($file)
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientName());
        
        $nextcloudUrl  = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPass = env('nextcloud.password', 'masterraden');
        $sslVerify     = env('nextcloud.ssl_verify', false);

        $url = $nextcloudUrl . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/{$fileName}";

        $fp = fopen($file->getTempName(), 'r');
        if (!$fp) {
            return false;
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPass);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->getTempName()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if ($curlError) {
            return false;
        }

        if ($httpCode !== 201 && $httpCode !== 204) {
            return false;
        }

        return $fileName;
    }

    public function store()
    {
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
            if ($uploadedName === false) {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Upload ke Nextcloud gagal'
                ]);
            }

            if ($uploadedName) {
                $data['image'] = $uploadedName;
            }
        }

        $model->insert($data);
        $taskId = $model->getInsertID();

        // Cek status - jika process/finishing/done, tampilkan payment popup
        $showPaymentPopup = !in_array(strtolower($data['status']), ['pending', 'payment pending']);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Task berhasil ditambahkan',
            'task_id' => $taskId,
            'show_payment_popup' => $showPaymentPopup
        ]);
    }

    // 🔹 UPDATE STATUS TASK
    public function updateStatus()
    {
        $model = new TaskModel();

        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');

        if (!$id || !$status) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'ID atau status tidak ditemukan'
            ]);
        }

        $model->update($id, ['status' => $status]);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Status berhasil diupdate',
            'id' => $id,
            'status' => $status
        ]);
    }

    // 🔹 DELETE TASK (BERERITAS NEXTCLOUD + DB)
    public function delete($id)
    {
        $model = new TaskModel();

        if (!$id) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'ID tidak ditemukan'
            ]);
        }

        $task = $model->find($id);

        if ($task && !empty($task['image'])) {
            $fileName = basename($task['image']);
            $nextcloudUrl  = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPass = env('nextcloud.password', 'masterraden');
            $sslVerify     = env('nextcloud.ssl_verify', false);
            
            $url = $nextcloudUrl . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/{$fileName}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPass);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($curlError) {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Gagal menghapus file di Nextcloud',
                    'error' => $curlError
                ]);
            }

            // 204 = deleted, 200 = deleted (beberapa config), 404 = already not there
            if (!in_array($httpCode, [200, 204, 404])) {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Nextcloud gagal menghapus file',
                    'httpCode' => $httpCode,
                    'response' => $response
                ]);
            }
        }

        try {
            $model->delete($id);

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Task berhasil dihapus (termasuk file Nextcloud)'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Gagal menghapus task',
                'error' => $e->getMessage()
            ]);
        }
    }
}