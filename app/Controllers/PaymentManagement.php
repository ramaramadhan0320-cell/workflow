<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\PaymentModel;
use App\Models\KehadiranModel;
use App\Models\CashbonModel;
use App\Models\BonusModel;
use App\Models\WithdrawalBypassModel;

class PaymentManagement extends BaseController
{
    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Access denied. Admin only.');
        }

        // Get all users for payment management
        $users = $userModel->findAll();

        // Get payment data for each user
        $paymentData = [];
        foreach ($users as $u) {
            $uId = $u['id'];

            // Get attendance data
            $kehadiranModel = new KehadiranModel();
            $kehadiran = $kehadiranModel->where('user_id', $uId)->findAll();

            // Calculate deductions
            $totalPotongan = 0;
            if (!empty($kehadiran)) {
                $gajiTotal = $u['gaji_total'] ?? 0;
                $gajiPerHari = $gajiTotal / 30; // Calculate daily wage (30 working days)

                foreach ($kehadiran as $k) {
                    $statusLower = strtolower($k['status']);

                    if ($statusLower === 'hadir') {
                        // Present = no deduction
                        $totalPotongan += 0;
                    } elseif ($statusLower === 'sakit') {
                        // Sick = half of 1 working day
                        $totalPotongan += ($gajiPerHari / 2);
                    } elseif ($statusLower === 'izin' || $statusLower === 'alfa') {
                        // Leave/Absent = 1 full working day
                        $totalPotongan += $gajiPerHari;
                    }
                }
            }

            // Get cashbon and bonus
            $cashbonModel = new CashbonModel();
            $bonusModel = new BonusModel();
            $totalCashbon = $cashbonModel->getTotalCashbon($uId) ?? 0;
            $totalBonus = $bonusModel->getTotalBonus($uId) ?? 0;

            // Calculate final salary
            $gajiBersih = ($u['gaji_total'] ?? 0) - $totalPotongan - $totalCashbon + $totalBonus;

            // Check if slip is in ACC folder
            $isInAcc = false;
            try {
                $slipGajiModel = new \App\Models\SlipGajiModel();
                $slip = $slipGajiModel->getSentSlip($uId);
                if ($slip) {
                    $isInAcc = $this->isFileInAccFolder($slip['filename']);
                }
            } catch (\Exception $e) {
                log_message('error', 'Error checking ACC status for user ' . $uId . ': ' . $e->getMessage());
                $isInAcc = false;
            }

            $paymentData[] = [
                'user' => $u,
                'kehadiran_count' => count($kehadiran),
                'total_potongan' => round($totalPotongan),
                'total_cashbon' => $totalCashbon,
                'total_bonus' => $totalBonus,
                'gaji_bersih' => round($gajiBersih),
                'withdrawal_date' => $this->calculateWithdrawalDate(),
                'is_in_acc' => $isInAcc
            ];
        }

        $data = [
            'title' => 'Payment Management',
            'user' => $user,
            'paymentData' => $paymentData
        ];

        return view('payment_management', $data);
    }

    public function testSlip()
    {
        // Simple test method
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Test endpoint working',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function testGetSlipData($userId)
    {
        // Test method without auth for debugging
        $slipModel = new \App\Models\SlipGajiModel();
        $slip = $slipModel->getSentSlip($userId);
        
        if (!$slip) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No slip found for user ' . $userId,
                'userId' => $userId
            ]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'userId' => $userId,
            'filename' => $slip['filename'],
            'status' => $slip['status'],
            'sent_at' => $slip['sent_at']
        ]);
    }

    public function testNextcloudDownload($filename)
    {
        // Test method without auth for debugging Nextcloud download
        $result = $this->downloadFromNextcloud($filename);
        
        return $this->response->setJSON($result);
    }

    public function getSlipData()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);

            if ($slip) {
                return $this->response->setJSON([
                    'success' => true,
                    'filename' => $slip['filename'],
                    'user_id' => $targetUserId
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'No slip found']);
            }
        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::getSlipData - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error retrieving slip data']);
        }
    }

    public function getSlipPdfBase64()
    {
        try {
            // Check if user is logged in and is admin
            if (!session()->get('isLoggedIn')) {
                return $this->response->setJSON([
                    'status' => 401,
                    'message' => 'Unauthorized'
                ]);
            }

            $userId = session()->get('id');
            $userModel = new UserModel();
            $user = $userModel->find($userId);

            if (!$user || $user['role'] !== 'admin') {
                return $this->response->setJSON([
                    'status' => 403,
                    'message' => 'Access denied. Admin only.'
                ]);
            }

            $targetUserId = $this->request->getVar('user_id');
            $filename = $this->request->getVar('filename');

            log_message('info', "PaymentManagement::getSlipPdfBase64 - targetUserId: $targetUserId, filename: $filename");

            if (!$targetUserId || !$filename) {
                log_message('error', "PaymentManagement::getSlipPdfBase64 - Missing parameters: targetUserId=$targetUserId, filename=$filename");
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'User ID and filename required'
                ]);
            }

            // Validate - admin can access any user's slip
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $sentSlip = $slipGajiModel->getSentSlip($targetUserId);

            log_message('info', "PaymentManagement::getSlipPdfBase64 - sentSlip found: " . ($sentSlip ? 'YES' : 'NO'));

            if (!$sentSlip || $sentSlip['filename'] !== $filename) {
                log_message('error', "PaymentManagement::getSlipPdfBase64 - Slip validation failed. sentSlip: " . json_encode($sentSlip) . ", filename: $filename");
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Slip gaji tidak ditemukan'
                ]);
            }

            log_message('info', "PaymentManagement::getSlipPdfBase64 - Starting download from Nextcloud");

            // Download dari Nextcloud
            $downloadResult = $this->downloadFromNextcloud($filename);

            if (!$downloadResult['success']) {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Gagal load file dari cloud: ' . ($downloadResult['error'] ?? 'Unknown error')
                ]);
            }

            // Convert to base64
            $pdfBase64 = base64_encode($downloadResult['content']);

            return $this->response->setJSON([
                'status' => 200,
                'data' => [
                    'base64' => $pdfBase64,
                    'filename' => $filename
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::getSlipPdfBase64 - ' . $e->getMessage());
            // Don't expose error details to user - log internally only
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'An error occurred while processing your request. Please contact administrator.'
            ]);
        }
    }

    private function downloadFromNextcloud($filename)
    {
        try {
            $nextcloudUrl = env('nextcloud.url', 'http://192.168.100.20:8080') . '/remote.php/dav/files/' . urlencode(env('nextcloud.username', 'masterraden')) . '/workflow/Report/' . urlencode($filename);
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'CHANGE_ME_IN_PRODUCTION');

            log_message('info', "Nextcloud Download Start - Filename: $filename");

            $ch = curl_init();

            // Set all curl options for proper PDF download
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            // SSL verification enabled for production (set to false only in development/testing with proper documentation)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('app.debug', false) ? false : true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, env('app.debug', false) ? false : 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // Let curl handle encoding automatically

            // Add headers for PDF - NO COMPRESSION
            $headers = [
                'Accept: application/pdf, application/octet-stream',
                'Accept-Encoding: identity',
                'User-Agent: CI4-SlipGaji-Viewer/1.0'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $fileContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $errno = curl_errno($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            curl_close($ch);

            // Log details
            log_message('info', "Nextcloud Response - HTTP: $httpCode, Size: " . strlen($fileContent) . " bytes, ContentType: $contentType, ContentLength: $contentLength, CurlErrno: $errno");

            if ($errno !== 0) {
                log_message('error', "Curl Error ($errno): $curlError for file $filename");
            }

            if ($httpCode !== 200) {
                log_message('error', "HTTP $httpCode from Nextcloud");
                if ($fileContent) {
                    log_message('error', "Response (first 500 chars): " . substr($fileContent, 0, 500));
                }
                return [
                    'success' => false,
                    'error' => "HTTP Error $httpCode",
                    'http_code' => $httpCode
                ];
            }

            if (empty($fileContent)) {
                log_message('error', "Empty response from Nextcloud for $filename");
                return [
                    'success' => false,
                    'error' => 'Response kosong'
                ];
            }

            // Validate PDF header
            $pdfHeader = substr($fileContent, 0, 4);
            if ($pdfHeader !== '%PDF') {
                log_message('error', "Invalid PDF header for $filename. Got: " . bin2hex($pdfHeader) . ". Response preview: " . substr($fileContent, 0, 500));
                return [
                    'success' => false,
                    'error' => 'File bukan PDF yang valid. Header: ' . bin2hex($pdfHeader),
                    'header' => $pdfHeader
                ];
            }

            log_message('info', "Nextcloud Download Success - $filename (" . strlen($fileContent) . " bytes)");

            return [
                'success' => true,
                'content' => $fileContent,
                'size' => strlen($fileContent),
                'content_type' => $contentType
            ];

        } catch (\Exception $e) {
            log_message('error', 'Nextcloud download error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function downloadSlip()
    {
        try {
            // Check if user is logged in and is admin
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('/');
            }

            $userId = session()->get('id');
            $userModel = new UserModel();
            $user = $userModel->find($userId);

            if (!$user || $user['role'] !== 'admin') {
                return redirect()->to('/dashboard')->with('error', 'Access denied. Admin only.');
            }

            $targetUserId = $this->request->getVar('user_id');
            $filename = $this->request->getVar('filename');

            if (!$targetUserId || !$filename) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'User ID and filename required'
                ]);
            }

            // Validate - admin can access any user's slip
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $sentSlip = $slipGajiModel->getSentSlip($targetUserId);

            if (!$sentSlip || $sentSlip['filename'] !== $filename) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Slip gaji tidak ditemukan'
                ]);
            }

            // Download dari Nextcloud
            $downloadResult = $this->downloadFromNextcloud($filename);

            if (!$downloadResult['success']) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Gagal download file dari cloud'
                ]);
            }

            // Return file for download
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setHeader('Content-Length', strlen($downloadResult['content']))
                ->setBody($downloadResult['content']);

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::downloadSlip - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error downloading slip: ' . $e->getMessage()
            ]);
        }
    }

    private function calculateWithdrawalDate()
    {
        // Calculate withdrawal date (end of month, skip weekend)
        $now = new \DateTime();
        $endOfMonth = new \DateTime('last day of this month');

        // If today is end of month, use next month
        if ($now->format('Y-m-d') === $endOfMonth->format('Y-m-d')) {
            $endOfMonth->modify('first day of next month');
            $endOfMonth->modify('last day of this month');
        }

        // Skip Saturday (6) and Sunday (0)
        while (in_array($endOfMonth->format('w'), [0, 6])) {
            $endOfMonth->modify('-1 day');
        }

        return $endOfMonth->format('d/m/Y');
    }

    public function testCreateAccFolder()
    {
        // Test method for creating acc folder (no auth for testing)
        log_message('info', 'Testing folder creation...');
        $result = $this->createFolderIfNotExists('/workflow/Report/acc');
        log_message('info', 'Folder creation result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        return $this->response->setJSON([
            'folder_created' => $result,
            'folder_path' => '/workflow/Report/acc',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function testMoveBack($filename)
    {
        // Test method for moving back from acc (no auth for testing)
        log_message('info', 'Testing move back...');
        $result = $this->moveFileFromAccToReport($filename);
        log_message('info', 'Move back result: ' . ($result['success'] ? 'SUCCESS' : 'FAILED'));
        return $this->response->setJSON([
            'move_back_success' => $result['success'],
            'filename' => $filename,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $result
        ]);
    }

    public function moveSlipToAcc()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            // Get slip data
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);

            if (!$slip) {
                return $this->response->setJSON(['success' => false, 'message' => 'No slip found for user']);
            }

            $filename = $slip['filename'];

            // Move file using WebDAV
            $result = $this->moveFileInNextcloud($filename);

            if ($result['success']) {
                // Update database status or add note that file has been moved
                log_message('info', "Slip gaji moved to ACC folder: $filename for user $targetUserId");
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File berhasil dipindahkan ke folder ACC',
                    'filename' => $filename
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal memindahkan file: ' . ($result['error'] ?? 'Unknown error')
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::moveSlipToAcc - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function moveFileInNextcloud($filename)
    {
        try {
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/') . "/remote.php/dav/files/" . urlencode($nextcloudUser);
            $sslVerify = env('nextcloud.ssl_verify', false);
            
            $sourcePath = "/workflow/Report/" . urlencode($filename);
            $destinationPath = "/workflow/Report/acc/" . urlencode($filename);
            $accFolderPath = "/workflow/Report/acc";

            log_message('info', "Nextcloud Move Start - From: $sourcePath To: $destinationPath");

            // First, ensure the acc folder exists
            $this->createFolderIfNotExists($accFolderPath);

            $ch = curl_init();

            // WebDAV MOVE request
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $sourcePath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MOVE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            // Set Destination header
            $headers = [
                'Destination: ' . $nextcloudBase . $destinationPath,
                'User-Agent: CI4-SlipGaji-Mover/1.0'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            log_message('info', "Nextcloud Move Response - HTTP: $httpCode");

            if ($errno !== 0) {
                log_message('error', "Curl Error ($errno): $curlError for file $filename");
                return [
                    'success' => false,
                    'error' => "Curl Error: $curlError"
                ];
            }

            if ($httpCode === 201 || $httpCode === 204) {
                log_message('info', "Nextcloud Move Success - $filename moved to acc folder");
                return [
                    'success' => true,
                    'message' => 'File moved successfully'
                ];
            } else {
                log_message('error', "HTTP $httpCode from Nextcloud MOVE - Source: $sourcePath, Dest: $destinationPath");
                if ($response) {
                    log_message('error', "Response: " . substr($response, 0, 1000));
                }
                return [
                    'success' => false,
                    'error' => "HTTP Error $httpCode",
                    'http_code' => $httpCode
                ];
            }

        } catch (\Exception $e) {
            log_message('error', 'Nextcloud move error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createFolderIfNotExists($folderPath)
    {
        try {
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/') . "/remote.php/dav/files/" . urlencode($nextcloudUser);
            $sslVerify = env('nextcloud.ssl_verify', false);

            log_message('info', "Checking if folder exists: $folderPath");

            $ch = curl_init();

            // First check if folder exists with HEAD request
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $folderPath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200) {
                log_message('info', "Folder $folderPath already exists");
                curl_close($ch);
                return true;
            }

            // Folder doesn't exist, create it with MKCOL
            curl_reset($ch);
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $folderPath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            if ($errno !== 0) {
                log_message('error', "Curl Error creating folder ($errno): $curlError");
                return false;
            }

            if ($httpCode === 201) {
                log_message('info', "Folder $folderPath created successfully");
                return true;
            } else {
                log_message('error', "MKCOL failed for folder $folderPath - HTTP $httpCode, trying fallback method");

                // Fallback: Try to create folder by uploading a dummy file
                $dummyFilePath = $folderPath . '/.temp_folder_marker.txt';
                $dummyContent = 'Temporary file to create folder structure';

                curl_reset($ch);
                curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $dummyFilePath);
                curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dummyContent);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: text/plain',
                    'Content-Length: ' . strlen($dummyContent)
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                $errno = curl_errno($ch);

                if ($errno !== 0) {
                    log_message('error', "Curl Error in fallback folder creation ($errno): $curlError");
                    curl_close($ch);
                    return false;
                }

                if ($httpCode === 201 || $httpCode === 204) {
                    log_message('info', "Folder $folderPath created via fallback method");

                    // Now delete the dummy file
                    curl_reset($ch);
                    curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $dummyFilePath);
                    curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

                    curl_exec($ch);
                    curl_close($ch);

                    log_message('info', "Dummy file deleted after folder creation");
                    return true;
                } else {
                    log_message('error', "Fallback folder creation also failed for $folderPath - HTTP $httpCode");
                    if ($response) {
                        log_message('error', "Response: " . substr($response, 0, 500));
                    }
                    curl_close($ch);
                    return false;
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Error creating folder: ' . $e->getMessage());
            return false;
        }
    }

    public function moveSlipFromAcc()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            // Get slip data
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);

            if (!$slip) {
                return $this->response->setJSON(['success' => false, 'message' => 'No slip found for user']);
            }

            $filename = $slip['filename'];

            // Move file back from ACC to Report folder
            $result = $this->moveFileFromAccToReport($filename);

            if ($result['success']) {
                log_message('info', "Slip gaji moved back to Report folder: $filename for user $targetUserId");
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File berhasil dikembalikan ke folder Report',
                    'filename' => $filename
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal mengembalikan file: ' . ($result['error'] ?? 'Unknown error')
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::moveSlipFromAcc - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function moveFileFromAccToReport($filename)
    {
        try {
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/') . "/remote.php/dav/files/" . urlencode($nextcloudUser);
            $sslVerify = env('nextcloud.ssl_verify', false);

            // Possible source locations (in order of preference)
            $possibleSources = [
                "/workflow/Report/acc/" . urlencode($filename), // Correct ACC location
                "/workflow/" . urlencode($filename),            // Wrong location from previous bug
                "/workflow/Report/" . urlencode($filename),     // Already in Report (shouldn't happen)
            ];

            $destinationPath = "/workflow/Report/" . urlencode($filename);
            $actualSourcePath = null;

            // Find where the file actually is
            foreach ($possibleSources as $sourcePath) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $sourcePath);
                curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $actualSourcePath = $sourcePath;
                    log_message('info', "File found at: $sourcePath");
                    break;
                }
            }

            if (!$actualSourcePath) {
                log_message('error', "File $filename not found in any expected location");
                return [
                    'success' => false,
                    'error' => 'File tidak ditemukan di lokasi yang diharapkan'
                ];
            }

            log_message('info', "Nextcloud Move Back Start - From: $actualSourcePath To: $destinationPath");

            $ch = curl_init();

            // WebDAV MOVE request
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $actualSourcePath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MOVE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            // Set Destination header
            $headers = [
                'Destination: ' . $nextcloudBase . $destinationPath,
                'User-Agent: CI4-SlipGaji-Mover/1.0'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            log_message('info', "Nextcloud Move Back Response - HTTP: $httpCode");

            if ($errno !== 0) {
                log_message('error', "Curl Error ($errno): $curlError for file $filename");
                return [
                    'success' => false,
                    'error' => "Curl Error: $curlError"
                ];
            }

            if ($httpCode === 201 || $httpCode === 204) {
                log_message('info', "Nextcloud Move Back Success - $filename moved back to Report folder");
                return [
                    'success' => true,
                    'message' => 'File moved back successfully'
                ];
            } else {
                log_message('error', "HTTP $httpCode from Nextcloud MOVE BACK - Source: $actualSourcePath, Dest: $destinationPath");
                if ($response) {
                    log_message('error', "Response: " . substr($response, 0, 1000));
                }
                return [
                    'success' => false,
                    'error' => "HTTP Error $httpCode",
                    'http_code' => $httpCode
                ];
            }

        } catch (\Exception $e) {
            log_message('error', 'Nextcloud move back error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function isFileInAccFolder($filename)
    {
        try {
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/') . "/remote.php/dav/files/" . urlencode($nextcloudUser);
            $sslVerify = env('nextcloud.ssl_verify', false);
            $accPath = "/workflow/Report/acc/" . urlencode($filename);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $accPath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode === 200);

        } catch (\Exception $e) {
            log_message('error', 'Error checking if file is in ACC folder: ' . $e->getMessage());
            return false;
        }
    }

    public function flushSlipData()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            $slipGajiModel = new \App\Models\SlipGajiModel();
            
            // Get slip data before deleting (for logging purposes)
            $slip = $slipGajiModel->getSentSlip($targetUserId);
            
            if (!$slip) {
                return $this->response->setJSON(['success' => false, 'message' => 'No slip found for user']);
            }

            $filename = $slip['filename'];

            // Delete the slip record from database (not the PDF file)
            $slipGajiModel->where('user_id', $targetUserId)
                         ->where('status', 'sent')
                         ->delete();

            log_message('info', "Slip gaji data flushed for user $targetUserId - Filename: $filename kept in ACC folder");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data slip berhasil di-flush. PDF tetap tersimpan di ACC folder sebagai jejak.',
                'filename' => $filename,
                'user_id' => $targetUserId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::flushSlipData - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * EXPERIMENTAL: Force unlock withdrawal for a user
     * Bypass both conditions: withdrawal date check and attendance check
     */
    public function experimentalBypassWithdrawal()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            // Check if user exists
            $targetUser = $userModel->find($targetUserId);
            if (!$targetUser) {
                return $this->response->setJSON(['success' => false, 'message' => 'Target user not found']);
            }

            // Log the experimental bypass action
            log_message('info', "EXPERIMENTAL: Withdrawal bypass requested by admin {$user['username']} for user {$targetUser['username']} (ID: {$targetUserId})");

            // Use WithdrawalBypassModel to store bypass in database
            $bypassModel = new WithdrawalBypassModel();
            
            // Create bypass token
            $result = $bypassModel->createBypass($targetUserId, $userId, $user['username'], 10);

            log_message('info', "Bypass token created for user {$targetUserId}: {$result['token']}");

            return $this->response->setJSON([
                'success' => true,
                'message' => "Bypass activated untuk user {$targetUser['username']} - Berlaku 10 menit",
                'username' => $targetUser['username'],
                'user_id' => $targetUserId,
                'expires_in_minutes' => $result['expires_in_minutes']
            ]);

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::experimentalBypassWithdrawal - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * EXPERIMENTAL: Reset withdrawal bypass for a user
     */
    public function experimentalResetBypass()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = session()->get('id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied. Admin only.']);
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User ID required']);
        }

        try {
            // Deactivate bypass from database
            $bypassModel = new WithdrawalBypassModel();
            $bypassModel->deactivateBypass($targetUserId);

            log_message('info', "Bypass reset by admin {$user['username']} for user {$targetUserId}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Bypass telah direset',
                'user_id' => $targetUserId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'PaymentManagement::experimentalResetBypass - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}