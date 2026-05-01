<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PaymentModel;
use App\Models\WithdrawalBypassModel;
use App\Models\SlipGajiModel;
use App\Models\KehadiranModel;
use App\Models\UserModel;
use App\Models\CashbonModel;
use App\Models\BonusModel;

class Payment extends ResourceController
{
    public function store()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            $db = \Config\Database::connect();
            $taskModel = new \App\Models\TaskModel();

            $input = $this->request->getPost();
            $taskId = $input['task_id'] ?? null;
            $amount = $input['amount'] ?? 0;
            $paymentMethod = $input['payment_method'] ?? 'transfer';
            $status = $input['status'] ?? 'unpaid';
            $paymentDate = $input['payment_date'] ?? null;

            if (!$taskId) {
                return $this->respond(['status' => 400, 'message' => 'Task ID tidak ditemukan'], 400);
            }

            if ($amount <= 0) {
                return $this->respond(['status' => 400, 'message' => 'Amount harus lebih besar dari 0'], 400);
            }

            $existingPayment = $db->table('payments')->where('task_id', $taskId)->get()->getRowArray();

            if ($existingPayment) {
                if ($existingPayment['status'] === 'paid') {
                    return $this->respond(['status' => 400, 'message' => 'Data sudah dibayar, tidak bisa diedit'], 400);
                }

                $db->table('payments')->where('task_id', $taskId)->update([
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                    'payment_date' => $paymentDate ?: null,
                ]);
            } else {
                $db->table('payments')->insert([
                    'task_id' => $taskId,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                    'payment_date' => $paymentDate ?: null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Update task status based on payment status
            $task = $taskModel->find($taskId);
            if ($task && in_array(strtolower($task['status']), ['pending', 'payment pending'])) {
                $newTaskStatus = ($status === 'paid') ? 'process' : 'payment pending';
                $taskModel->update($taskId, ['status' => $newTaskStatus]);
            }

            return $this->respond(['status' => 200, 'message' => 'Payment berhasil disimpan']);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function detail($taskId)
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            $db = \Config\Database::connect();
            $payment = $db->table('payments')->where('task_id', $taskId)->get()->getRowArray();

            return $this->respond([
                'status' => 200,
                'data' => $payment ? [$payment] : []
            ]);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function history()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);
            if (!$user) {
                return $this->respond(['status' => 404, 'message' => 'User tidak ditemukan'], 404);
            }

            // Get Attendance for deductions calculation
            $kehadiranModel = new \App\Models\KehadiranModel();
            $kehadiran = $kehadiranModel->where('user_id', $userId)
                                       ->orderBy('tanggal', 'DESC')
                                       ->findAll();

            // Get Cashbon details
            $cashbonModel = new \App\Models\CashbonModel();
            $cashbon = $cashbonModel->where('user_id', $userId)
                                   ->orderBy('tanggal', 'DESC')
                                   ->findAll();
            $total_cashbon = $cashbonModel->getTotalCashbon($userId);

            // Get Bonus details
            $bonusModel = new \App\Models\BonusModel();
            $bonus = $bonusModel->where('user_id', $userId)
                               ->orderBy('tanggal', 'DESC')
                               ->findAll();
            $total_bonus = $bonusModel->getTotalBonus($userId);

            // Calculate Deductions (Logic from web controller)
            $total_potongan = 0;
            $gaji_total = $user['gaji_total'] ?? 0;
            $gaji_per_hari = $gaji_total / 30;

            foreach ($kehadiran as $k) {
                $status_lower = strtolower($k['status']);
                if ($status_lower === 'sakit') {
                    $total_potongan += ($gaji_per_hari / 2);
                } elseif ($status_lower === 'izin' || $status_lower === 'alfa') {
                    $total_potongan += $gaji_per_hari;
                }
            }

            $gaji_bersih = $gaji_total + $total_bonus - $total_cashbon - $total_potongan;

            // Check if user's slip is in ACC folder
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);
            $slip_in_acc = false;
            if ($slip && isset($slip['filename'])) {
                $slip_in_acc = $this->isFileInAccFolder($slip['filename']);
            }

            $withdrawal_date = $this->calculateWithdrawalDate();
            $can_withdraw = $this->canUserWithdraw($userId, $withdrawal_date, $slip_in_acc);

            return $this->respond([
                'status' => 200,
                'data' => [
                    'user' => [
                        'username' => $user['username'],
                        'bank_tujuan' => $user['bank_tujuan'] ?? 'N/A',
                        'nomor_rekening' => $user['nomor_rekening'] ?? '0',
                    ],
                    'total_gaji' => (int)$gaji_total,
                    'total_cashbon' => (int)$total_cashbon,
                    'total_bonus' => (int)$total_bonus,
                    'cashbon' => $cashbon ?: [],
                    'bonus' => $bonus ?: [],
                    'total_potongan' => round($total_potongan),
                    'gaji_bersih' => round($gaji_bersih),
                    'date' => date('d/m/Y'),
                    'estimasi_cair' => $withdrawal_date,
                    'can_withdraw' => $can_withdraw,
                    'slip_sent' => ($slip !== null),
                    'slip_filename' => $slip['filename'] ?? null,
                    'slip_sent_at' => $slip['created_at'] ?? null,
                    'kehadiran' => $kehadiran
                ]
            ]);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getSlip()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            $slipGajiModel = new \App\Models\SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);

            if (!$slip) {
                return $this->respond(['status' => 404, 'message' => 'Slip gaji tidak ditemukan'], 404);
            }

            $filename = $slip['filename'];

            // Download dari Nextcloud (logic identical to web controller)
            $downloadResult = $this->downloadFromNextcloud($filename);

            if (!$downloadResult['success']) {
                return $this->respond([
                    'status' => 500,
                    'message' => 'Gagal load file dari cloud: ' . ($downloadResult['error'] ?? 'Unknown error')
                ], 500);
            }

            // Return base64 for Flutter to display
            return $this->respond([
                'status' => 200,
                'data' => [
                    'base64' => base64_encode($downloadResult['content']),
                    'filename' => $filename
                ]
            ]);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function withdraw()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            // Check if user can withdraw
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);
            $slip_in_acc = false;
            if ($slip && isset($slip['filename'])) {
                $slip_in_acc = $this->isFileInAccFolder($slip['filename']);
            }

            $withdrawal_date = $this->calculateWithdrawalDate();
            $can_withdraw = $this->canUserWithdraw($userId, $withdrawal_date, $slip_in_acc);

            if (!$can_withdraw) {
                return $this->respond([
                    'status' => 403,
                    'message' => 'Pencairan dikunci. Pastikan hari ini adalah tanggal pencairan dan slip gaji Anda sudah diverifikasi Admin.'
                ], 403);
            }

            // Logic penarikan sederhana
            return $this->respond([
                'status' => 200,
                'message' => 'Permintaan penarikan gaji telah dikirim ke Admin. Silakan tunggu konfirmasi.'
            ]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function canUserWithdraw($userId, $withdrawalDate, $slipInAcc = false)
    {
        $bypassModel = new WithdrawalBypassModel();
        $activeBypass = $bypassModel->getActiveBypass($userId);
        
        if ($activeBypass) {
            return true;
        }

        $today = date('d/m/Y');
        if ($today !== $withdrawalDate) {
            return false;
        }

        return $slipInAcc === true;
    }

    private function isFileInAccFolder($filename)
    {
        try {
            $filename = basename($filename);
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);
            
            $accPath = "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Report/acc/" . urlencode($filename);

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
            return false;
        }
    }

    private function downloadFromNextcloud($filename)
    {
        // Re-use logic from web Payment controller
        try {
            $filename = basename($filename);
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

            $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Report/" . urlencode($filename);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $fileContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP Error $httpCode"];
            }

            return ['success' => true, 'content' => $fileContent];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function calculateWithdrawalDate()
    {
        // Calculate withdrawal date (end of month, skip weekends)
        $date = new \DateTime('last day of this month');
        $dayOfWeek = $date->format('N'); // 1 = Monday, 7 = Sunday

        // If Saturday (6) or Sunday (7), move to Friday
        if ($dayOfWeek >= 6) {
            $date->modify('-' . ($dayOfWeek - 5) . ' days');
        }

        return $date->format('d/m/Y');
    }
    public function sendSlip()
    {
        $userId = $this->request->getHeaderLine('X-User-Id') ?: $this->request->getVar('user_id');
        if (!$userId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        try {
            $userModel = new UserModel();
            $user = $userModel->find($userId);

            if (!$user) {
                return $this->respond(['status' => 404, 'message' => 'User not found'], 404);
            }

            // Get PDF data from request
            $jsonData = $this->request->getJSON();
            
            if (!$jsonData || !isset($jsonData->pdfData)) {
                return $this->respond(['status' => 400, 'message' => 'No PDF data provided'], 400);
            }
            
            $pdfData = $jsonData->pdfData;

            // Decode data URI to get binary content
            $pdfBinary = null;
            
            if (strpos($pdfData, 'data:') === 0) {
                $commaPos = strpos($pdfData, ',');
                if ($commaPos !== false) {
                    $base64Data = substr($pdfData, $commaPos + 1);
                    $base64Data = preg_replace('/\s+/', '', $base64Data);
                    $pdfBinary = base64_decode($base64Data, true);
                }
            } else {
                $base64Data = preg_replace('/\s+/', '', $pdfData);
                $pdfBinary = base64_decode($base64Data, true);
            }

            if ($pdfBinary === false || empty($pdfBinary)) {
                return $this->respond(['status' => 400, 'message' => 'Invalid or corrupted PDF data'], 400);
            }

            // Validate PDF signature
            if (substr($pdfBinary, 0, 4) !== '%PDF') {
                return $this->respond(['status' => 400, 'message' => 'Invalid PDF file signature'], 400);
            }

            // Generate filename
            $filename = 'SLIP_GAJI_' . strtoupper($user['username']) . '_' . date('Ymd_His') . '.pdf';
            
            // Upload to Nextcloud
            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);
            
            $nextcloudPath = "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Report/" . urlencode($filename);
            $nextcloudUrl = $nextcloudBase . $nextcloudPath;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $pdfBinary);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/pdf',
                'Content-Length: ' . strlen($pdfBinary)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            if ($errno !== 0) {
                return $this->respond(['status' => 500, 'message' => 'Failed to upload PDF', 'debug' => $curlError], 500);
            }

            if ($httpCode !== 201 && $httpCode !== 204) {
                return $this->respond(['status' => 500, 'message' => 'Failed to save slip to cloud storage', 'debug' => "HTTP $httpCode"], 500);
            }

            // Save slip record to database
            $slipGajiModel = new SlipGajiModel();
            $slipGajiModel->insert([
                'user_id' => $userId,
                'filename' => $filename,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'sent'
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Slip gaji berhasil dikirim ke Admin',
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
