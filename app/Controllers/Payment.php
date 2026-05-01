<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\KehadiranModel;
use App\Models\CashbonModel;
use App\Models\BonusModel;
use App\Models\SlipGajiModel;
use App\Models\WithdrawalBypassModel;

class Payment extends BaseController
{
    public function index()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('/');
            }

            $userId = session()->get('id');

            // Persiapkan data default
            $data = [
                'user' => [
                    'username' => session()->get('username') ?: 'User',
                    'id' => $userId,
                    'gaji_total' => 0,
                    'nomor_rekening' => '0',
                    'bank_tujuan' => 'N/A'
                ],
                'payments' => [],
                'kehadiran' => [],
                'total_cashbon' => 0,
                'total_bonus' => 0,
                'total_potongan' => 0,
                'withdrawal_date' => date('d/m/Y'),
                'can_withdraw' => false,
                'slip_gaji_sent' => false,
                'slip_in_acc' => false
            ];

            // Coba ambil user data dari database
            try {
                $userModel = new UserModel();
                $user = $userModel->find($userId);
                if ($user) {
                    $data['user'] = $user;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error loading user: ' . $e->getMessage());
                // Gunakan data default jika error
            }

            // Ambil data kehadiran user
            try {
                $kehadiranModel = new KehadiranModel();
                $kehadiran = $kehadiranModel->where('user_id', $userId)
                                           ->orderBy('tanggal', 'DESC')
                                           ->findAll();
                $data['kehadiran'] = $kehadiran ?: [];
            } catch (\Exception $e) {
                log_message('error', 'Error loading kehadiran: ' . $e->getMessage());
                // Gunakan data default jika error
            }

            // Ambil total cashbon user
            try {
                $cashbonModel = new CashbonModel();
                $total_cashbon = $cashbonModel->getTotalCashbon($userId);
                $data['total_cashbon'] = $total_cashbon ?? 0;
            } catch (\Exception $e) {
                log_message('error', 'Error loading cashbon: ' . $e->getMessage());
                // Gunakan data default jika error
            }

            // Ambil total bonus user
            try {
                $bonusModel = new BonusModel();
                $total_bonus = $bonusModel->getTotalBonus($userId);
                $data['total_bonus'] = $total_bonus ?? 0;
            } catch (\Exception $e) {
                log_message('error', 'Error loading bonus: ' . $e->getMessage());
                // Gunakan data default jika error
            }

            // Hitung potongan berdasarkan kehadiran
            $total_potongan = 0;
            if (!empty($data['kehadiran'])) {
                $gaji_total = $data['user']['gaji_total'] ?? 0;
                $gaji_per_hari = $gaji_total / 30; // Hitung gaji per hari (30 hari kerja)

                foreach ($data['kehadiran'] as $k) {
                    $status_lower = strtolower($k['status']);

                    if ($status_lower === 'hadir') {
                        // Hadir = tidak ada potongan
                        $total_potongan += 0;
                    } elseif ($status_lower === 'sakit') {
                        // Sakit = setengah dari 1 hari kerja
                        $total_potongan += ($gaji_per_hari / 2);
                    } elseif ($status_lower === 'izin' || $status_lower === 'alfa') {
                        // Izin/Alfa = 1 hari kerja penuh
                        $total_potongan += $gaji_per_hari;
                    }
                }
            }

            $data['total_potongan'] = round($total_potongan);

            // Cek apakah user sudah mengirim slip gaji
            try {
                $slipGajiModel = new SlipGajiModel();
                $data['slip_gaji_sent'] = $slipGajiModel->hasSentSlip($userId);
                
                // Cek apakah slip sudah di ACC folder
                if ($data['slip_gaji_sent']) {
                    $slip = $slipGajiModel->getSentSlip($userId);
                    if ($slip && isset($slip['filename'])) {
                        $data['slip_in_acc'] = $this->isFileInAccFolder($slip['filename']);
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Error checking slip gaji: ' . $e->getMessage());
                $data['slip_gaji_sent'] = false;
                $data['slip_in_acc'] = false;
            }

            // Hitung tanggal pencairan (akhir bulan, skip weekend)
            $data['withdrawal_date'] = $this->calculateWithdrawalDate();

            // Cek apakah hari ini adalah tanggal pencairan dan user sudah mengirim slip ke ACC folder
            $data['can_withdraw'] = $this->canUserWithdraw($userId, $data['withdrawal_date'], $data['slip_in_acc']);

            return view('payment', $data);
        } catch (\Exception $e) {
            log_message('error', 'Payment::index - ' . $e->getMessage());
            // Don't expose error details to user - log internally only
            return redirect()->to('/dashboard')->with('error', 'Error loading payment page. Please contact administrator.');
        }
    }

    public function rincian()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('/');
            }

            $userId = session()->get('id');

            // Persiapkan data default
            $data = [
                'user' => [
                    'username' => session()->get('username') ?: 'User',
                    'id' => $userId
                ],
                'cashbon' => [],
                'bonus' => [],
                'total_cashbon' => 0,
                'total_bonus' => 0
            ];

            // Coba ambil user data dari database
            try {
                $userModel = new UserModel();
                $user = $userModel->find($userId);
                if ($user) {
                    $data['user'] = $user;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error loading user: ' . $e->getMessage());
            }

            // Ambil data cashbon user
            try {
                $cashbonModel = new CashbonModel();
                $cashbon = $cashbonModel->where('user_id', $userId)
                                       ->orderBy('tanggal', 'DESC')
                                       ->findAll();
                $data['cashbon'] = $cashbon ?: [];
                $data['total_cashbon'] = $cashbonModel->getTotalCashbon($userId) ?? 0;
            } catch (\Exception $e) {
                log_message('error', 'Error loading cashbon: ' . $e->getMessage());
            }

            // Ambil data bonus user
            try {
                $bonusModel = new BonusModel();
                $bonus = $bonusModel->where('user_id', $userId)
                                   ->orderBy('tanggal', 'DESC')
                                   ->findAll();
                $data['bonus'] = $bonus ?: [];
                $data['total_bonus'] = $bonusModel->getTotalBonus($userId) ?? 0;
            } catch (\Exception $e) {
                log_message('error', 'Error loading bonus: ' . $e->getMessage());
            }

            return view('payment-rincian', $data);
        } catch (\Exception $e) {
            log_message('error', 'Payment::rincian - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Internal Server Error');
        }
    }

    public function slip()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userId = session()->get('id');
        
        // Prepare data with defaults
        $data = [
            'user' => [
                'username' => session()->get('username') ?: 'User',
                'id' => $userId,
                'gaji_total' => 0,
                'nomor_rekening' => '0',
                'bank_tujuan' => 'N/A'
            ],
            'total_cashbon' => 0,
            'total_bonus' => 0,
            'total_potongan' => 0,
            'withdrawal_date' => date('d/m/Y'),
            'slip_sent' => false,
            'slip_filename' => '',
            'slip_sent_at' => null
        ];

        // Get user data from database
        try {
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            if ($user) {
                $data['user'] = $user;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error loading user: ' . $e->getMessage());
        }

        // Get total bonus
        try {
            $bonusModel = new BonusModel();
            $total_bonus = $bonusModel->getTotalBonus($userId);
            $data['total_bonus'] = $total_bonus ?? 0;
        } catch (\Exception $e) {
            log_message('error', 'Error loading bonus: ' . $e->getMessage());
        }

        // Get total cashbon
        try {
            $cashbonModel = new CashbonModel();
            $total_cashbon = $cashbonModel->getTotalCashbon($userId);
            $data['total_cashbon'] = $total_cashbon ?? 0;
        } catch (\Exception $e) {
            log_message('error', 'Error loading cashbon: ' . $e->getMessage());
        }

        // Calculate deductions from attendance
        $total_potongan = 0;
        try {
            $kehadiranModel = new KehadiranModel();
            $kehadiran = $kehadiranModel->where('user_id', $userId)
                                       ->orderBy('tanggal', 'DESC')
                                       ->findAll();
            
            if (!empty($kehadiran)) {
                $gaji_total = $data['user']['gaji_total'] ?? 0;
                $gaji_per_hari = $gaji_total / 30;

                foreach ($kehadiran as $k) {
                    $status_lower = strtolower($k['status']);

                    if ($status_lower === 'hadir') {
                        $total_potongan += 0;
                    } elseif ($status_lower === 'sakit') {
                        $total_potongan += $gaji_per_hari * 0.5;
                    } elseif ($status_lower === 'izin') {
                        $total_potongan += $gaji_per_hari * 0.75;
                    } elseif ($status_lower === 'tanpa_keterangan' || $status_lower === 'tk') {
                        $total_potongan += $gaji_per_hari;
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error calculating deductions: ' . $e->getMessage());
        }
        $data['total_potongan'] = $total_potongan;

        // Get slip status
        try {
            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);
            if ($slip) {
                $data['slip_sent'] = true;
                $data['slip_filename'] = $slip['filename'] ?? '';
                $data['slip_sent_at'] = $slip['created_at'] ?? null;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error loading slip status: ' . $e->getMessage());
        }

        return view('payment-slip', $data);
    }

    public function sendSlip()
    {
        try {
            // Check if user is logged in
            if (!session()->get('isLoggedIn')) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 401,
                    'message' => 'Unauthorized'
                ]);
            }

            $userId = session()->get('id');
            $userModel = new UserModel();
            $user = $userModel->find($userId);

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 404,
                    'message' => 'User not found'
                ]);
            }

            // Get PDF data from request (JSON body instead of form data)
            $jsonData = $this->request->getJSON();
            
            if (!$jsonData || !isset($jsonData->pdfData)) {
                log_message('error', 'No pdfData in JSON body');
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 400,
                    'message' => 'No PDF data provided'
                ]);
            }
            
            $pdfData = $jsonData->pdfData;

            // Decode data URI to get binary content
            // Extract base64 part from data URI
            $pdfBinary = null;
            
            if (strpos($pdfData, 'data:') === 0) {
                // Format: data:application/pdf;base64,<base64data>
                // Find the comma that separates metadata from data
                $commaPos = strpos($pdfData, ',');
                if ($commaPos !== false) {
                    $base64Data = substr($pdfData, $commaPos + 1);
                    // Remove any whitespace/newlines that might be in the base64 string
                    $base64Data = preg_replace('/\s+/', '', $base64Data);
                    $pdfBinary = base64_decode($base64Data, true); // strict mode
                } else {
                    log_message('error', 'Invalid data URI format - no comma found');
                }
            } else {
                // Try direct base64 decode
                $base64Data = preg_replace('/\s+/', '', $pdfData);
                $pdfBinary = base64_decode($base64Data, true);
            }

            if ($pdfBinary === false || empty($pdfBinary)) {
                log_message('error', 'PDF binary decode failed or empty. Data length: ' . strlen($pdfData ?? ''));
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 400,
                    'message' => 'Invalid or corrupted PDF data'
                ]);
            }

            // Validate PDF signature (PDF files start with %PDF)
            if (substr($pdfBinary, 0, 4) !== '%PDF') {
                log_message('error', 'Invalid PDF signature. First 20 bytes: ' . bin2hex(substr($pdfBinary, 0, 20)));
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 400,
                    'message' => 'Invalid PDF file signature'
                ]);
            }

            log_message('info', "Payment::sendSlip - PDF decoded successfully. Size: " . strlen($pdfBinary) . " bytes, Signature: " . substr($pdfBinary, 0, 8));

            // Generate filename
            $filename = 'SLIP_GAJI_' . strtoupper($user['username']) . '_' . date('Ymd_His') . '.pdf';
            
            // Upload to Nextcloud - use the same path as PaymentManagement
            $nextcloudBase = "http://192.168.100.20:8080/remote.php/dav/files/masterraden";
            $nextcloudPath = "/workflow/Report/" . urlencode($filename);
            $nextcloudUrl = $nextcloudBase . $nextcloudPath;
            $nextcloudUser = "masterraden";
            $nextcloudPassword = "masterraden";

            log_message('info', "Payment::sendSlip - Starting upload for user $userId to $nextcloudUrl");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $pdfBinary);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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

            log_message('info', "Payment::sendSlip - HTTP $httpCode, Curl Errno: $errno, Size: " . strlen($pdfBinary));

            // Check for curl errors
            if ($errno !== 0) {
                log_message('error', "Curl Error uploading slip ($errno): $curlError");
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 500,
                    'message' => 'Failed to upload PDF',
                    'debug' => "Curl Error: $curlError"
                ]);
            }

            // Check HTTP response code
            if ($httpCode !== 201 && $httpCode !== 204) {
                log_message('error', "Nextcloud upload failed - HTTP $httpCode. Response: " . substr($response, 0, 500));
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 500,
                    'message' => 'Failed to save slip to cloud storage',
                    'debug' => "HTTP $httpCode: " . substr($response, 0, 200)
                ]);
            }

            // Save slip record to database
            try {
                $slipGajiModel = new SlipGajiModel();
                $slipGajiModel->insert([
                    'user_id' => $userId,
                    'filename' => $filename,
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'sent'
                ]);

                log_message('info', "Slip gaji uploaded successfully for user $userId: $filename");

                return $this->response->setStatusCode(200)->setJSON([
                    'status' => 200,
                    'message' => 'Slip gaji berhasil dikirim',
                    'filename' => $filename
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Error saving slip to database: ' . $e->getMessage());
                // Still return success if file was uploaded but database save failed
                return $this->response->setStatusCode(200)->setJSON([
                    'status' => 200,
                    'message' => 'Slip gaji berhasil dikirim',
                    'filename' => $filename,
                    'warning' => 'File uploaded but database record creation failed'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Payment::sendSlip - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'message' => 'Error sending slip: ' . $e->getMessage()
            ]);
        }
    }

    public function getSlipData()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 401,
                    'message' => 'Unauthorized'
                ]);
            }

            $userId = session()->get('id');

            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);

            if ($slip) {
                return $this->response->setJSON([
                    'status' => 200,
                    'data' => [
                        'filename' => $slip['filename']
                    ]
                ]);
            } else {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 404,
                    'message' => 'No slip found'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Payment::getSlipData - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'message' => 'Error retrieving slip data: ' . $e->getMessage()
            ]);
        }
    }

    public function getSlipPdfBase64()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return $this->response->setJSON([
                    'status' => 401,
                    'message' => 'Unauthorized'
                ]);
            }

            $userId = session()->get('id');

            $slipGajiModel = new SlipGajiModel();
            $slip = $slipGajiModel->getSentSlip($userId);

            if (!$slip) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Slip gaji tidak ditemukan'
                ]);
            }

            $filename = $slip['filename'];

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
            log_message('error', 'Payment::getSlipPdfBase64 - ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getSlipPdf()
    {
        // Implementation for getting PDF
        return 'PDF content';
    }

    public function downloadSlip()
    {
        // Implementation for downloading slip
        return 'PDF download';
    }

    public function previewSlip()
    {
        // Implementation for previewing slip
        return 'PDF preview';
    }

    public function checkAccStatus()
    {
        // Implementation for checking ACC status
        return $this->response->setJSON(['success' => true, 'is_in_acc' => false]);
    }

    public function debugNextcloud()
    {
        // Implementation for debugging Nextcloud
        return $this->response->setJSON(['success' => true]);
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

    private function downloadFromNextcloud($filename)
    {
        try {
            $nextcloudUrl = "http://192.168.100.20:8080/remote.php/dav/files/masterraden/workflow/Report/" . urlencode($filename);
            $nextcloudUser = "masterraden";
            $nextcloudPassword = "masterraden";

            log_message('info', "Nextcloud Download Start - Filename: $filename");

            $ch = curl_init();

            // Set all curl options for proper PDF download
            curl_setopt($ch, CURLOPT_URL, $nextcloudUrl);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
                log_message('error', "HTTP $httpCode from Nextcloud - URL: $nextcloudUrl");
                if ($fileContent) {
                    log_message('error', "Response (first 1000 chars): " . substr($fileContent, 0, 1000));
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

    private function canUserWithdraw($userId, $withdrawalDate, $slipInAcc = false)
    {
        // Check if user has an active bypass token from database (EXPERIMENTAL)
        $bypassModel = new WithdrawalBypassModel();
        $activeBypass = $bypassModel->getActiveBypass($userId);
        
        if ($activeBypass) {
            log_message('info', "Withdrawal bypass active for user {$userId}");
            return true; // Bypass is active, allow withdrawal
        }

        // Check if user can withdraw (today is withdrawal date and slip is in ACC folder)
        $today = date('d/m/Y');
        if ($today !== $withdrawalDate) {
            return false;
        }

        // Check if user's slip is in ACC folder on cloud
        // This is the main condition for withdrawal - slip must be confirmed by admin (in ACC folder)
        return $slipInAcc === true;
    }

    private function isFileInAccFolder($filename)
    {
        try {
            $nextcloudBase = "http://192.168.100.20:8080/remote.php/dav/files/masterraden";
            $accPath = "/workflow/Report/acc/" . urlencode($filename);
            $nextcloudUser = "masterraden";
            $nextcloudPassword = "masterraden";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextcloudBase . $accPath);
            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ':' . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
}

