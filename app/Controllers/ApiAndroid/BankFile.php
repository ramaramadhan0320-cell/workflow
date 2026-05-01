<?php

namespace App\Controllers\ApiAndroid;

use App\Controllers\BaseController;
use App\Models\UserModel;

class BankFile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function validateUser()
    {
        $userId = $this->request->getHeaderLine('X-User-Id');
        if (empty($userId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Missing User ID'
            ]);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Invalid User'
            ]);
        }

        return $user;
    }

    public function list()
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) return $user;

        $files = $this->getFilesFromNextCloud();

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Success',
            'data' => $files,
            'is_admin' => ($user['role'] === 'admin')
        ]);
    }

    private function getFilesFromNextCloud()
    {
        try {
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);
            
            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Bank%20file/";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PROPFIND");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Depth: 1',
                'Content-Type: application/xml'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="UTF-8"?><d:propfind xmlns:d="DAV:"><d:prop><d:displayname/><d:getcontentlength/><d:getlastmodified/><d:resourcetype/></d:prop></d:propfind>');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 207) return [];

            $files = [];
            $xml = simplexml_load_string($response);

            if ($xml !== false) {
                foreach ($xml->children('d', true)->response as $entry) {
                    $href = (string)$entry->children('d', true)->href;
                    $propstat = $entry->children('d', true)->propstat;

                    if ($propstat) {
                        $prop = $propstat->children('d', true)->prop;
                        $displayname = (string)$prop->children('d', true)->displayname;
                        $size = (string)$prop->children('d', true)->getcontentlength;
                        $modified = (string)$prop->children('d', true)->getlastmodified;
                        $resourcetype = $prop->children('d', true)->resourcetype;

                        if (substr($href, -1) === '/' || empty($displayname) || isset($resourcetype->collection)) continue;

                        $expectedPath = '/remote.php/dav/files/' . urlencode($nextcloudUser) . '/workflow/Bank%20file/';
                        if (strpos($href, $expectedPath) === false) continue;

                        $modifiedTime = strtotime($modified);

                        $files[] = [
                            'name' => $displayname,
                            'size' => (int)$size,
                            'modified' => $modifiedTime,
                            'modified_formatted' => date('d/m/Y H:i', $modifiedTime),
                            'href' => $href
                        ];
                    }
                }
            }

            usort($files, function($a, $b) { return $b['modified'] - $a['modified']; });
            return $files;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function upload()
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) return $user;

        $file = $this->request->getFile('file');
        if (!$file || $file->getError() != UPLOAD_ERR_OK) {
            $errCode = $file ? $file->getError() : 'NO_FILE';
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400, 
                'message' => 'File upload error (Code: ' . $errCode . ')'
            ]);
        }

        // Expanded allowed extensions
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'cdr', 'ai', 'psd', 'eps', 'zip', 'rar', 'txt'];
        $fileExtension = strtolower($file->getClientExtension());

        if (!in_array($fileExtension, $allowedExtensions)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400, 
                'message' => 'Tipe file tidak didukung'
            ]);
        }

        if ($file->getSize() > 500 * 1024 * 1024) { // 500MB max
            return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'File terlalu besar (max 500MB)']);
        }

        try {
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientName());
            $fileContent = file_get_contents($file->getTempName());

            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Bank%20file/" . urlencode($fileName);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: ' . $file->getMimeType(),
                'Content-Length: ' . strlen($fileContent)
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->response->setJSON(['status' => 200, 'message' => 'File berhasil diunggah', 'filename' => $fileName]);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Upload gagal']);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function download($filename)
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) return $user;

        try {
            $filename = basename($filename);
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Bank%20file/" . urlencode($filename);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

            $fileContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return $this->response
                    ->setHeader('Content-Type', 'application/octet-stream')
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->setBody($fileContent);
            } else {
                return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'File tidak ditemukan']);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Download gagal']);
        }
    }

    public function delete($filename)
    {
        $user = $this->validateUser();
        if ($user instanceof \CodeIgniter\HTTP\Response) return $user;

        if ($user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => 'Hanya admin yang dapat menghapus file']);
        }

        try {
            $filename = basename($filename);
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Bank%20file/" . urlencode($filename);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->response->setJSON(['status' => 200, 'message' => 'File berhasil dihapus']);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Hapus gagal']);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Delete gagal']);
        }
    }
}
