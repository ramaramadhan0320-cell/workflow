<?php

namespace App\Controllers;

class Integration extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        // Get current user untuk check role
        $userModel = new \App\Models\UserModel();
        $userId = session()->get('id');
        $data['user'] = $userModel->find($userId);
        $data['username'] = session()->get('username');

        return view('integration', $data);
    }

    public function processAbsensi()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid method']);
        }

        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $qrData = $json['qr_data'] ?? '';
        $deviceIp = $json['device_ip'] ?? '';

        if (empty($qrData)) {
            return $this->response->setJSON(['success' => false, 'message' => 'QR data is required']);
        }

        try {
            // Parse QR data (assuming format: USER_ID|TIMESTAMP|LOCATION)
            // Example: "1|2024-01-03 08:30:00|Office Main Entrance"
            $qrParts = explode('|', $qrData);
            if (count($qrParts) < 2) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid QR format']);
            }

            $userId = $qrParts[0];
            $timestamp = $qrParts[1] ?? date('Y-m-d H:i:s');
            $location = $qrParts[2] ?? 'ESP32 Cam';

            // Get user data
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
            }

            // Check if already absen today using IotAttendanceModel
            $iotAttendanceModel = new \App\Models\IotAttendanceModel();
            if ($iotAttendanceModel->hasCheckedInToday($userId)) {
                $existing = $iotAttendanceModel->where('user_id', $userId)
                                              ->where('status', 'masuk')
                                              ->where('DATE(scan_time)', date('Y-m-d'))
                                              ->first();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User already checked in today at ' . date('H:i', strtotime($existing['scan_time']))
                ]);
            }

            // Insert IoT absensi record
            $data = [
                'user_id' => $userId,
                'status' => 'masuk',
                'scan_time' => date('Y-m-d H:i:s'),
                'device_id' => $deviceIp,
                'qr_content' => $qrData,
                'is_valid' => 1
            ];

            if ($iotAttendanceModel->insert($data)) {
                // Also insert to regular kehadiran table for compatibility
                $kehadiranModel = new \App\Models\KehadiranModel();
                $kehadiranData = [
                    'user_id' => $userId,
                    'tanggal' => date('Y-m-d H:i:s'),
                    'status' => 'present'
                ];
                $kehadiranModel->insert($kehadiranData);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => "Absensi IoT berhasil untuk {$user['username']} pada " . date('d/m/Y H:i') . " - Status: Masuk"
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to save IoT absensi']);
            }

        } catch (\Exception $e) {
            log_message('error', 'Absensi processing error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Get all IoT devices
     */
    public function getDevices()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        $iotDeviceModel = new \App\Models\IotDeviceModel();
        $devices = $iotDeviceModel->getAllDevices();

        $devices = array_map(function ($device) use ($iotDeviceModel) {
            $device['stream_url'] = $iotDeviceModel->getStreamUrl($device['id']);
            $device['page_url'] = $iotDeviceModel->getDevicePageUrl($device['id']);
            return $device;
        }, $devices);

        return $this->response->setJSON([
            'success' => true,
            'data' => $devices,
            'count' => count($devices)
        ]);
    }

    /**
     * Add new IoT device
     */
    public function addDevice()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid method']);
        }

        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceIp = $json['device_ip'] ?? '';
        $deviceName = $json['device_name'] ?? 'IoT Device';
        $devicePort = !empty($json['device_port']) ? intval($json['device_port']) : 80;
        $streamPath = trim($json['stream_path'] ?? '/stream');
        $location = $json['location'] ?? 'Unknown';

        if ($streamPath === '') {
            $streamPath = '/stream';
        }
        if ($streamPath[0] !== '/') {
            $streamPath = '/' . $streamPath;
        }

        if (empty($deviceIp)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Device IP is required']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();

            // Auto-fix jika user memasukkan port di kolom IP (misal 192.168.1.1:1984)
            if (strpos($deviceIp, ':') !== false) {
                $parts = explode(':', $deviceIp);
                $deviceIp = $parts[0];
                $devicePort = intval($parts[1]);
            }

            // Check if device already exists on same IP and port
            $existing = $iotDeviceModel->getDeviceByIpPort($deviceIp, $devicePort);
            if ($existing) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Device dengan IP dan port ini sudah terdaftar'
                ]);
            }

            // Check if device is reachable
            $isReachable = $iotDeviceModel->checkDeviceStatus($deviceIp, $devicePort);
            $status = $isReachable ? 'online' : 'offline';

            // Insert device
            $deviceData = [
                'device_name' => $deviceName,
                'device_ip' => $deviceIp,
                'device_port' => $devicePort,
                'stream_path' => $streamPath,
                'status' => $status,
                'location' => $location,
                'last_seen' => date('Y-m-d H:i:s')
            ];

            if ($iotDeviceModel->insert($deviceData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Device added successfully',
                    'status' => $status,
                    'device' => $iotDeviceModel->getDeviceByIp($deviceIp)
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to add device: ' . json_encode($iotDeviceModel->errors())
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Add device error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove IoT device
     */
    public function removeDevice()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid method']);
        }

        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = $json['device_id'] ?? '';

        if (empty($deviceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Device ID is required']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $device = $iotDeviceModel->find($deviceId);

            if (!$device) {
                return $this->response->setJSON(['success' => false, 'message' => 'Device not found']);
            }

            if ($iotDeviceModel->removeDevice($deviceId)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Device removed successfully'
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to remove device']);
            }

        } catch (\Exception $e) {
            log_message('error', 'Remove device error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Check device status
     */
    public function checkDeviceStatus()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid method']);
        }

        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = $json['device_id'] ?? '';

        if (empty($deviceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Device ID is required']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $device = $iotDeviceModel->find($deviceId);

            if (!$device) {
                return $this->response->setJSON(['success' => false, 'message' => 'Device not found']);
            }

            // Check if device is reachable
            $isOnline = $iotDeviceModel->checkDeviceStatus($device['device_ip'], $device['device_port']);
            $status = $isOnline ? 'online' : 'offline';

            // Update device status
            $iotDeviceModel->updateDeviceStatus($deviceId, $status);

            return $this->response->setJSON([
                'success' => true,
                'status' => $status,
                'device' => $iotDeviceModel->find($deviceId)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Check device status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Get dashboard summary
     */
    public function getSummary()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $iotAttendanceModel = new \App\Models\IotAttendanceModel();

            $devices = $iotDeviceModel->getAllDevices();
            $totalDevices = count($devices);
            $onlineDevices = count(array_filter($devices, function($device) {
                return $device['status'] === 'online';
            }));

            $todayAttendance = $iotAttendanceModel->where('DATE(scan_time)', date('Y-m-d'))->countAllResults();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'total_devices' => $totalDevices,
                    'online_devices' => $onlineDevices,
                    'today_attendance' => $todayAttendance
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get summary error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotAttendanceModel = new \App\Models\IotAttendanceModel();
            $iotDeviceModel = new \App\Models\IotDeviceModel();

            // Get recent attendance activities
            $recentAttendance = $iotAttendanceModel->select('iot_attendance.*, users.username, iot_devices.device_name')
                ->join('users', 'users.id = iot_attendance.user_id')
                ->join('iot_devices', 'iot_devices.id = iot_attendance.device_id', 'left')
                ->orderBy('scan_time', 'DESC')
                ->limit(10)
                ->find();

            $activities = [];

            foreach ($recentAttendance as $attendance) {
                $activities[] = [
                    'type' => 'attendance',
                    'message' => "Absensi IoT: {$attendance['username']} - " . ucfirst($attendance['status']),
                    'time' => $attendance['scan_time'],
                    'device' => $attendance['device_name'] ?: 'Unknown Device',
                    'color' => 'green'
                ];
            }

            // Get recent device activities (connections, disconnections)
            $recentDevices = $iotDeviceModel->orderBy('updated_at', 'DESC')->limit(5)->find();

            foreach ($recentDevices as $device) {
                if ($device['status'] === 'online') {
                    $activities[] = [
                        'type' => 'device',
                        'message' => "Device connected: {$device['device_name']}",
                        'time' => $device['last_seen'],
                        'device' => $device['device_ip'] . ':' . $device['device_port'],
                        'color' => 'blue'
                    ];
                }
            }

            // Sort activities by time
            usort($activities, function($a, $b) {
                return strtotime($b['time']) - strtotime($a['time']);
            });

            // Take only first 10
            $activities = array_slice($activities, 0, 10);

            return $this->response->setJSON([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get recent activities error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Refresh all device status - Check connectivity and update database
     */
    public function refreshAllDeviceStatus()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $devices = $iotDeviceModel->getAllDevices();
            
            $updates = [
                'online' => 0,
                'offline' => 0,
                'checked' => 0
            ];

            foreach ($devices as $device) {
                // Check if device is reachable
                $isOnline = $iotDeviceModel->checkDeviceStatus($device['device_ip'], $device['device_port']);
                $newStatus = $isOnline ? 'online' : 'offline';
                
                // Update device status in database
                $iotDeviceModel->updateDeviceStatus($device['id'], $newStatus);
                
                // Count statistics
                $updates['checked']++;
                if ($newStatus === 'online') {
                    $updates['online']++;
                } else {
                    $updates['offline']++;
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'All device status refreshed',
                'data' => $updates
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Refresh device status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    public function scanner()
    {
        $deviceIp = $this->request->getVar('ip');
        $devicePort = $this->request->getVar('port');
        $path = $this->request->getVar('path');
        
        // Logika Pintar: Jika path kosong, berikan default berdasarkan port
        if (empty($path) || $path === '/stream') {
            if ($devicePort == '1984') {
                $path = '/api/stream.mjpeg?src=kamera_absensi';
            } else {
                $path = '/?action=stream';
            }
        }

        if ($path[0] !== '/') $path = '/' . $path;
        $targetUrl = "http://{$deviceIp}:{$devicePort}{$path}";

        $streamUrl = base_url('/integration/stream?url=') . urlencode($targetUrl);

        return view('iot_scanner', [
            'streamUrl' => $streamUrl
        ]);
    }

    /**
     * Proxy untuk mengambil tampilan web IoT agar tidak diblokir browser (Mixed Content/PNA)
     * URL: /integration/proxy?url=http://192.168.1.1
     */
    public function proxy()
    {
        // PENTING: Lepaskan kunci session agar aplikasi tidak freeze/hang
        // karena ini adalah request yang mungkin memakan waktu lama.
        if (session_get_cookie_params()) {
            session_write_close();
        }

        $targetUrl = $this->request->getVar('url');
        
        if (!$targetUrl) {
            return "No URL provided";
        }

        // Pastikan targetUrl diawali http
        if (strpos($targetUrl, 'http') !== 0) {
            $targetUrl = 'http://' . $targetUrl;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Beri waktu 5 detik untuk jabat tangan
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Workflow-Proxy/1.0');
            
            // Penting untuk Docker & Jaringan Lokal
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Paksa IPv4
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                $error_no = curl_errno($ch);
                curl_close($ch);
                return "Proxy Connection Error (#$error_no): " . $error_msg . " (Target: $targetUrl). Pastikan Docker container memiliki akses ke jaringan lokal.";
            }

            curl_close($ch);

            // Jika konten adalah HTML, kita perlu menyuntikkan <base> tag dan me-rewrite URL stream & API
            if (strpos($contentType, 'text/html') !== false) {
                $baseUrl = rtrim($targetUrl, '/') . '/';
                $response = str_replace('<head>', "<head>\n    <base href=\"$baseUrl\">", $response);
                
                // Jalur Proxy resmi kita
                $proxyStreamUrl = base_url('/integration/stream?url=');
                $proxyApiUrl = base_url('/api-android/integration/push');

                // 1. Rewrite URL Stream di Javascript (Lebih fleksibel terhadap spasi/kutip)
                // Pola: const/var/let streamUrl = "..."
                $newStreamValue = $proxyStreamUrl . urlencode($baseUrl . '?action=stream');
                $response = preg_replace('/(const|var|let)\s+streamUrl\s*=\s*["\'][^"\']+["\']\s*;?/i', '$1 streamUrl = "' . $newStreamValue . '";', $response);
                
                // 2. Rewrite URL API di Javascript
                // Pola: const/var/let apiUrl = "..."
                $response = preg_replace('/(const|var|let)\s+apiUrl\s*=\s*["\'][^"\']+["\']\s*;?/i', '$1 apiUrl = "' . $proxyApiUrl . '";', $response);

                // 3. Fallback: Ganti tag img manual
                $response = str_replace('src="/?action=stream"', 'src="' . $newStreamValue . '"', $response);
            }

            return $this->response
                ->setStatusCode($httpCode)
                ->setContentType($contentType)
                ->setBody($response);

        } catch (\Exception $e) {
            return "Proxy Exception: " . $e->getMessage();
        }
    }

    /**
     * Proxy khusus untuk Real-time Video Streaming (MJPEG)
     * Mengalirkan data langsung dari OpenWrt ke Browser tanpa menunggu buffer selesai.
     */
    public function stream()
    {
        if (session_get_cookie_params()) {
            session_write_close();
        }

        $targetUrl = $this->request->getVar('url');
        if (!$targetUrl) return "No URL provided";
        if (strpos($targetUrl, 'http') !== 0) $targetUrl = 'http://' . $targetUrl;

        // 1. Matikan limit waktu
        set_time_limit(0);
        
        // 2. Header anti-buffer paling agresif
        header('X-Accel-Buffering: no'); 
        header('Content-Type: multipart/x-mixed-replace; boundary=boundarydonotcross');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('Pragma: no-cache');

        if (ob_get_level()) ob_end_clean();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        
        // Proxy Header dari Kamera ke Browser
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
            header($header);
            return strlen($header);
        });

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            echo $data;
            if (connection_aborted()) return 0;
            flush();
            return strlen($data);
        });
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);

        curl_exec($ch);
        curl_close($ch);
        exit;
    }
}
