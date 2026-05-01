<?php

namespace App\Controllers;

use App\Models\IotDeviceModel;
use CodeIgniter\Controller;

class Integration extends BaseController
{
    /**
     * Tampilan utama integrasi IoT
     */
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find(session()->get('userId'));

        return view('integration', [
            'title' => 'Integrasi IoT',
            'user'  => $user
        ]);
    }

    /**
     * Ambil daftar perangkat dari database
     */
    public function getDevices()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $devices = $iotDeviceModel->getAllDevices();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $devices
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get devices error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Tambah perangkat baru
     */
    public function addDevice()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['device_name']) || empty($data['device_ip'])) {
                return $this->response->setJSON(['success' => false, 'message' => 'Name and IP are required']);
            }

            // Sanitasi IP (Hapus port jika user memasukkannya di kolom IP)
            if (strpos($data['device_ip'], ':') !== false) {
                $parts = explode(':', $data['device_ip']);
                $data['device_ip'] = $parts[0];
                if (empty($data['device_port'])) $data['device_port'] = $parts[1];
            }

            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $iotDeviceModel->addDevice([
                'device_name' => $data['device_name'],
                'device_ip'   => $data['device_ip'],
                'device_port' => $data['device_port'] ?? 80,
                'stream_path' => $data['stream_path'] ?? '',
                'page_url'    => $data['page_url'] ?? '',
                'location'    => $data['location'] ?? '',
                'status'      => 'offline'
            ]);

            return $this->response->setJSON(['success' => true, 'message' => 'Device added successfully']);
        } catch (\Exception $e) {
            log_message('error', 'Add device error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus perangkat
     */
    public function deleteDevice($id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $iotDeviceModel->delete($id);
            return $this->response->setJSON(['success' => true, 'message' => 'Device deleted']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Cek status koneksi satu perangkat
     */
    public function checkDeviceStatus()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $deviceId = $this->request->getVar('device_id');
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $device = $iotDeviceModel->find($deviceId);

            if (!$device) {
                return $this->response->setJSON(['success' => false, 'message' => 'Device not found']);
            }

            // Gunakan CURL agar bisa mendukung Proxy V2Ray jika ada
            $ch = curl_init();
            $targetUrl = "http://{$device['device_ip']}:{$device['device_port']}";
            curl_setopt($ch, CURLOPT_URL, $targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_NOBODY, true); // Hanya cek header (cepat)

            $proxyServer = env('IOT_PROXY_SERVER');
            $proxyPort = env('IOT_PROXY_PORT');
            if (!empty($proxyServer) && !empty($proxyPort)) {
                curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
            }

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $isOnline = ($httpCode > 0); // Jika ada respon, dianggap online
            curl_close($ch);

            $newStatus = $isOnline ? 'online' : 'offline';
            $iotDeviceModel->updateDeviceStatus($deviceId, $newStatus);

            return $this->response->setJSON([
                'success' => true,
                'status' => $newStatus,
                'last_seen' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Ambil log aktivitas terbaru
     */
    public function getRecentActivities()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $activities = [];

            // Get last 5 updated devices
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
        
        // Cek fallback path jika kosong
        if (empty($path)) {
            $path = ($devicePort == 1984 || $devicePort == 1884) ? '/api/stream.mjpeg?src=kamera_absensi' : '/?action=stream';
        }

        // Gunakan PROXY STREAM untuk video agar tidak diblokir browser
        $targetUrl = "http://{$deviceIp}:{$devicePort}{$path}";
        $streamUrl = base_url('/integration/stream?url=') . urlencode($targetUrl);

        return view('iot_scanner', [
            'streamUrl' => $streamUrl,
            'deviceName' => 'Scanner Mode'
        ]);
    }

    /**
     * Proxy untuk mengambil tampilan web IoT agar tidak diblokir browser (Mixed Content/PNA)
     * URL: /integration/proxy?url=http://192.168.1.1
     */
    public function proxy()
    {
        if (session_get_cookie_params()) {
            session_write_close();
        }

        $targetUrl = $this->request->getVar('url');
        if (!$targetUrl) return "No URL provided";
        if (strpos($targetUrl, 'http') !== 0) $targetUrl = 'http://' . $targetUrl;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $proxyServer = env('IOT_PROXY_SERVER');
            $proxyPort = env('IOT_PROXY_PORT');
            if (!empty($proxyServer) && !empty($proxyPort)) {
                curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
            }

            $response = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                return "Proxy Error: " . $error_msg;
            }

            curl_close($ch);

            if (strpos($contentType, 'text/html') !== false) {
                $baseUrl = rtrim($targetUrl, '/') . '/';
                $response = str_replace('<head>', "<head>\n    <base href=\"$baseUrl\">", $response);
            }

            return $this->response->setStatusCode($httpCode)->setContentType($contentType)->setBody($response);

        } catch (\Exception $e) {
            return "Proxy Exception: " . $e->getMessage();
        }
    }

    /**
     * Proxy khusus untuk Real-time Video Streaming (MJPEG)
     */
    public function stream()
    {
        if (session_get_cookie_params()) {
            session_write_close();
        }

        $targetUrl = $this->request->getVar('url');
        if (!$targetUrl) return "No URL provided";
        if (strpos($targetUrl, 'http') !== 0) $targetUrl = 'http://' . $targetUrl;

        set_time_limit(0);
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: multipart/x-mixed-replace; boundary=frame');
        header('Cache-Control: no-cache');
        header('Connection: close');
        header('Pragma: no-cache');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024);
        
        $proxyServer = env('IOT_PROXY_SERVER');
        $proxyPort = env('IOT_PROXY_PORT');
        if (!empty($proxyServer) && !empty($proxyPort)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
            if (stripos($header, 'Content-Type') !== false) header($header);
            return strlen($header);
        });

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            echo $data;
            flush();
            if (connection_aborted()) return 0;
            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
        exit;
    }
}
