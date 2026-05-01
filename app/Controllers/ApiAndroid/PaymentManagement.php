<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\KehadiranModel;
use App\Models\CashbonModel;
use App\Models\BonusModel;
use App\Models\SlipGajiModel;
use App\Models\WithdrawalBypassModel;

class PaymentManagement extends ResourceController
{
    public function list()
    {
        $adminId = $this->request->getHeaderLine('X-User-Id');
        if (!$adminId) {
            return $this->respond(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $userModel = new UserModel();
        $admin = $userModel->find($adminId);
        if (!$admin || $admin['role'] !== 'admin') {
            return $this->respond(['status' => 403, 'message' => 'Access denied. Admin only.'], 403);
        }

        $users = $userModel->findAll();
        $paymentData = [];

        foreach ($users as $u) {
            $uId = $u['id'];

            // Kehadiran
            $kehadiranModel = new KehadiranModel();
            $kehadiran = $kehadiranModel->where('user_id', $uId)->findAll();

            $totalPotongan = 0;
            $gajiTotal = $u['gaji_total'] ?? 0;
            $gajiPerHari = $gajiTotal / 30;

            foreach ($kehadiran as $k) {
                $statusLower = strtolower($k['status']);
                if ($statusLower === 'sakit') {
                    $totalPotongan += ($gajiPerHari / 2);
                } elseif ($statusLower === 'izin' || $statusLower === 'alfa') {
                    $totalPotongan += $gajiPerHari;
                }
            }

            // Cashbon & Bonus
            $cashbonModel = new CashbonModel();
            $bonusModel = new BonusModel();
            $totalCashbon = $cashbonModel->getTotalCashbon($uId) ?? 0;
            $totalBonus = $bonusModel->getTotalBonus($uId) ?? 0;

            $gajiBersih = $gajiTotal - $totalPotongan - $totalCashbon + $totalBonus;

            // Bypass Status
            $bypassModel = new WithdrawalBypassModel();
            $bypass = $bypassModel->where('user_id', $uId)->where('expires_at >', date('Y-m-d H:i:s'))->first();

            // Slip Status
            $isInAcc = false;
            $slipFilename = null;
            $slipSent = false;
            try {
                $slipGajiModel = new SlipGajiModel();
                $slip = $slipGajiModel->getSentSlip($uId);
                if ($slip) {
                    $slipSent = true;
                    $slipFilename = $slip['filename'];
                    $isInAcc = $this->isFileInAccFolder($slip['filename']);
                }
            } catch (\Exception $e) {
                $isInAcc = false;
            }

            $paymentData[] = [
                'user_id' => $uId,
                'username' => $u['username'],
                'full_name' => $u['full_name'] ?? $u['username'],
                'profile' => $u['profile'],
                'gaji_total' => (int)$gajiTotal,
                'total_potongan' => round($totalPotongan),
                'total_cashbon' => (int)$totalCashbon,
                'total_bonus' => (int)$totalBonus,
                'gaji_bersih' => round($gajiBersih),
                'is_in_acc' => $isInAcc,
                'slip_sent' => $slipSent,
                'slip_filename' => $slipFilename,
                'has_bypass' => ($bypass != null)
            ];
        }

        return $this->respond([
            'status' => 200,
            'data' => $paymentData,
            'withdrawal_date' => $this->calculateWithdrawalDate()
        ]);
    }

    public function moveToAcc()
    {
        $adminId = $this->request->getHeaderLine('X-User-Id');
        $targetUserId = $this->request->getVar('user_id');

        if (!$targetUserId) {
            return $this->respond(['status' => 400, 'message' => 'User ID required'], 400);
        }

        try {
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);

            if (!$slip) {
                return $this->respond(['status' => 404, 'message' => 'No slip found for user'], 404);
            }

            $filename = $slip['filename'];
            $result = $this->moveFileInNextcloud($filename, true); // true = to acc

            if ($result['success']) {
                return $this->respond(['status' => 200, 'message' => 'Slip moved to ACC folder']);
            } else {
                return $this->respond(['status' => 500, 'message' => 'Failed to move: ' . ($result['error'] ?? 'Unknown')], 500);
            }
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function moveFromAcc()
    {
        $adminId = $this->request->getHeaderLine('X-User-Id');
        $targetUserId = $this->request->getVar('user_id');

        if (!$targetUserId) {
            return $this->respond(['status' => 400, 'message' => 'User ID required'], 400);
        }

        try {
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);

            if (!$slip) {
                return $this->respond(['status' => 404, 'message' => 'No slip found for user'], 404);
            }

            $filename = $slip['filename'];
            $result = $this->moveFileInNextcloud($filename, false); // false = from acc

            if ($result['success']) {
                return $this->respond(['status' => 200, 'message' => 'Slip moved back to Report folder']);
            } else {
                return $this->respond(['status' => 500, 'message' => 'Failed to move: ' . ($result['error'] ?? 'Unknown')], 500);
            }
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function flushSlipData()
    {
        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->respond(['status' => 400, 'message' => 'User ID required'], 400);
        }

        try {
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($targetUserId);
            if (!$slip) {
                return $this->respond(['status' => 404, 'message' => 'No slip found for user'], 404);
            }

            $slipGajiModel->where('user_id', $targetUserId)->where('status', 'sent')->delete();
            return $this->respond(['status' => 200, 'message' => 'Data slip berhasil di-flush. PDF tetap tersimpan di cloud.']);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function bypassWithdrawal()
    {
        $adminId = $this->request->getHeaderLine('X-User-Id');
        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->respond(['status' => 400, 'message' => 'User ID required'], 400);
        }

        try {
            $userModel = new UserModel();
            $admin = $userModel->find($adminId);
            $bypassModel = new WithdrawalBypassModel();
            $result = $bypassModel->createBypass($targetUserId, $adminId, $admin['username'], 10);
            return $this->respond(['status' => 200, 'message' => "Bypass activated untuk 10 menit"]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function resetBypass()
    {
        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->respond(['status' => 400, 'message' => 'User ID required'], 400);
        }

        try {
            $bypassModel = new WithdrawalBypassModel();
            $bypassModel->deactivateBypass($targetUserId);
            return $this->respond(['status' => 200, 'message' => 'Bypass telah direset']);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSlipPreview()
    {
        $targetUserId = $this->request->getVar('user_id');
        $filename = $this->request->getVar('filename');

        if (!$targetUserId || !$filename) {
            return $this->respond(['status' => 400, 'message' => 'User ID and filename required'], 400);
        }

        try {
            // Check in Report folder first, then ACC
            $result = $this->downloadFromNextcloud($filename);
            
            if (!$result['success']) {
                // Try from ACC folder
                $result = $this->downloadFromNextcloud("acc/" . $filename);
            }

            if (!$result['success']) {
                return $this->respond(['status' => 500, 'message' => 'Gagal load file: ' . ($result['error'] ?? 'Unknown')], 500);
            }

            return $this->respond([
                'status' => 200,
                'data' => [
                    'base64' => base64_encode($result['content']),
                    'filename' => $filename
                ]
            ]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    private function downloadFromNextcloud($path)
    {
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        // Path is already built in getSlipPreview (e.g., "acc/file.pdf" or "file.pdf")
        // But we should extract the filename to prevent traversal within the path argument
        $filename = basename($path);
        if (strpos($path, 'acc/') === 0) {
            $safePath = "acc/" . $filename;
        } else {
            $safePath = $filename;
        }

        $nextcloudUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/Report/" . $safePath;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
        curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPassword");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'content' => $content];
        }
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }

    private function moveFileInNextcloud($filename, $toAcc = true)
    {
        $filename = basename($filename);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);
        
        $baseDavUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser);

        if ($toAcc) {
            $sourcePath = "/workflow/Report/" . urlencode($filename);
            $destPath = "/workflow/Report/acc/" . urlencode($filename);
            // Ensure folder exists
            $this->createFolder("/workflow/Report/acc");
        } else {
            $sourcePath = "/workflow/Report/acc/" . urlencode($filename);
            $destPath = "/workflow/Report/" . urlencode($filename);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseDavUrl . $sourcePath);
        curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPassword");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MOVE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Destination: $baseDavUrl$destPath"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201 || $httpCode === 204) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }

    private function createFolder($path)
    {
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);
        
        $baseDavUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseDavUrl . $path);
        curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPassword");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_exec($ch);
        curl_close($ch);
    }

    private function isFileInAccFolder($filename)
    {
        $filename = basename($filename);
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);
        
        $baseDavUrl = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser);
        $path = "/workflow/Report/acc/" . urlencode($filename);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseDavUrl . $path);
        curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPassword");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    }

    private function calculateWithdrawalDate()
    {
        $endOfMonth = new \DateTime('last day of this month');
        while (in_array($endOfMonth->format('w'), [0, 6])) {
            $endOfMonth->modify('-1 day');
        }
        return $endOfMonth->format('d/m/Y');
    }
}
