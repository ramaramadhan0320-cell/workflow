<?php

namespace App\Controllers;

use App\Models\UserModel;

class BankFile extends BaseController
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

        $files = $this->getFilesFromNextCloud();

        $data = [
            'title' => 'Bank File',
            'user' => $this->userModel->find(session()->get('id')),
            'files' => $files
        ];

        return view('bank_file', $data);
    }

    public function debug()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $files = $this->getFilesFromNextCloud();

        return $this->response->setJSON([
            'success' => true,
            'files_count' => count($files),
            'files' => $files,
            'path' => 'workflow/Bank file'
        ]);
    }

    private function getFilesFromNextCloud()
    {
        try {
            $nextcloudUrl = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/') . "/remote.php/dav/files/" . urlencode(env('nextcloud.username', 'masterraden')) . "/workflow/Bank%20file/";
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

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

            if ($httpCode !== 207) {
                log_message('warning', "NextCloud PROPFIND failed - HTTP: $httpCode, Response: " . substr($response, 0, 500));
                return [];
            }

            // Parse XML response
            $files = [];
            $xml = simplexml_load_string($response);

            if ($xml !== false) {
                foreach ($xml->children('d', true)->response as $entry) {
                    // Get file info
                    $href = (string)$entry->children('d', true)->href;
                    $propstat = $entry->children('d', true)->propstat;

                    if ($propstat) {
                        $prop = $propstat->children('d', true)->prop;
                        $displayname = (string)$prop->children('d', true)->displayname;
                        $size = (string)$prop->children('d', true)->getcontentlength;
                        $modified = (string)$prop->children('d', true)->getlastmodified;
                        $resourcetype = $prop->children('d', true)->resourcetype;

                        // Skip the root folder itself (href ends with /)
                        if (substr($href, -1) === '/') {
                            continue;
                        }

                        // Skip if it's a folder (has collection element) or if displayname is empty
                        if (empty($displayname) || isset($resourcetype->collection)) {
                            continue;
                        }

                        // Only include files that are directly in the Bank file folder
                        // The href should be like: /remote.php/dav/files/user/workflow/Bank%20file/filename.ext
                        $expectedPath = '/remote.php/dav/files/' . urlencode($nextcloudUser) . '/workflow/Bank%20file/';
                        if (strpos($href, $expectedPath) === false) {
                            continue;
                        }

                        // Parse date
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
            } else {
                log_message('error', 'Failed to parse NextCloud XML response');
            }

            // Sort by modified date (newest first)
            usort($files, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });

            log_message('info', 'Bank File list: Found ' . count($files) . ' files');

            return $files;
        } catch (\Exception $e) {
            log_message('error', 'Bank File list error: ' . $e->getMessage());
            return [];
        }
    }

    public function upload()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $file = $this->request->getFile('file');

        if (!$file || $file->getError() != UPLOAD_ERR_OK) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'File upload error'
            ]);
        }

        // Validate file type
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'cdr', 'ai', 'psd', 'eps', 'zip', 'rar', 'txt'];
        $fileExtension = strtolower($file->getClientExtension());

        if (!in_array($fileExtension, $allowedExtensions)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Tipe file tidak didukung. Gunakan: Dokumen, Foto, atau File Editing'
            ]);
        }

        // Validate file size (max 50MB)
        if ($file->getSize() > 500 * 1024 * 1024) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Ukuran file terlalu besar (max 500MB)'
            ]);
        }

        try {
            // Generate unique filename
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientName());

            // Read file content
            $fileContent = file_get_contents($file->getTempName());

            // Upload to NextCloud WebDAV
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);
            
            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Bank%20file/" . urlencode($fileName);

            // CURL upload
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
            $curlError = curl_error($ch);
            curl_close($ch);

            log_message('info', "Bank File Upload - URL: $nextcloudUrl, HTTP: $httpCode, Error: $curlError");

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File berhasil diunggah ke NextCloud',
                    'filename' => $fileName
                ]);
            } else {
                log_message('error', "NextCloud upload failed - HTTP $httpCode: $response");
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Upload ke NextCloud gagal (HTTP ' . $httpCode . ')'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Bank File upload exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function download($filename)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $filename = basename($filename); // Path traversal protection
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
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Bank File download error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Download gagal'
            ]);
        }
    }

    public function delete($filename)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $filename = basename($filename); // Path traversal protection
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

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File berhasil dihapus'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus file (HTTP ' . $httpCode . ')'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Bank File delete error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Delete gagal'
            ]);
        }
    }
}
