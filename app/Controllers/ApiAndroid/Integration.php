<?php

namespace App\Controllers\ApiAndroid;

use CodeIgniter\RESTful\ResourceController;
use App\Models\IotDeviceModel;
use App\Models\IotAttendanceModel;
use App\Models\UserModel;
use App\Models\KehadiranModel;

class Integration extends ResourceController
{
    protected $format = 'json';

    public function getSummary()
    {
        try {
            $iotDeviceModel = new IotDeviceModel();
            $iotAttendanceModel = new IotAttendanceModel();

            $devices = $iotDeviceModel->findAll();
            $totalDevices = count($devices);
            $onlineDevices = count(array_filter($devices, function($device) {
                return $device['status'] === 'online';
            }));

            $todayAttendance = $iotAttendanceModel->where('DATE(scan_time)', date('Y-m-d'))->countAllResults();

            return $this->respond([
                'status' => 200,
                'data' => [
                    'total_devices' => $totalDevices,
                    'online_devices' => $onlineDevices,
                    'today_attendance' => $todayAttendance
                ]
            ]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDevices()
    {
        try {
            $iotDeviceModel = new IotDeviceModel();
            $devices = $iotDeviceModel->findAll();

            $formattedDevices = array_map(function ($device) use ($iotDeviceModel) {
                $device['stream_url'] = "http://{$device['device_ip']}:{$device['device_port']}{$device['stream_path']}";
                return $device;
            }, $devices);

            return $this->respond([
                'status' => 200,
                'data' => $formattedDevices
            ]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function addDevice()
    {
        try {
            $deviceIp = $this->request->getVar('device_ip');
            $deviceName = $this->request->getVar('device_name') ?: 'IoT Device';
            $devicePort = $this->request->getVar('device_port') ?: 80;
            $streamPath = $this->request->getVar('stream_path') ?: '/stream';
            $location = $this->request->getVar('location') ?: 'Unknown';

            if (!$deviceIp) {
                return $this->respond(['status' => 400, 'message' => 'Device IP is required'], 400);
            }

            $iotDeviceModel = new IotDeviceModel();
            
            // Check reachable
            $isOnline = $this->pingDevice($deviceIp, $devicePort);
            $status = $isOnline ? 'online' : 'offline';

            $data = [
                'device_name' => $deviceName,
                'device_ip' => $deviceIp,
                'device_port' => $devicePort,
                'stream_path' => $streamPath,
                'status' => $status,
                'location' => $location,
                'last_seen' => date('Y-m-d H:i:s')
            ];

            if ($iotDeviceModel->insert($data)) {
                return $this->respond(['status' => 200, 'message' => 'Device added successfully', 'data' => $data]);
            }
            return $this->respond(['status' => 500, 'message' => 'Failed to save device'], 500);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function removeDevice()
    {
        try {
            $deviceId = $this->request->getVar('device_id');
            if (!$deviceId) return $this->respond(['status' => 400, 'message' => 'Device ID required'], 400);

            $iotDeviceModel = new IotDeviceModel();
            if ($iotDeviceModel->delete($deviceId)) {
                return $this->respond(['status' => 200, 'message' => 'Device removed successfully']);
            }
            return $this->respond(['status' => 500, 'message' => 'Failed to remove device'], 500);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function refreshDevices()
    {
        try {
            $iotDeviceModel = new IotDeviceModel();
            $devices = $iotDeviceModel->findAll();
            $stats = ['online' => 0, 'offline' => 0];

            foreach ($devices as $device) {
                $isOnline = $this->pingDevice($device['device_ip'], $device['device_port']);
                $status = $isOnline ? 'online' : 'offline';
                $iotDeviceModel->update($device['id'], [
                    'status' => $status,
                    'last_seen' => $isOnline ? date('Y-m-d H:i:s') : $device['last_seen']
                ]);
                $stats[$status]++;
            }

            return $this->respond(['status' => 200, 'message' => 'Refreshed successfully', 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRecentActivities()
    {
        try {
            $iotAttendanceModel = new IotAttendanceModel();
            $recentAttendance = $iotAttendanceModel->select('iot_attendance.*, users.username, iot_devices.device_name')
                ->join('users', 'users.id = iot_attendance.user_id')
                ->join('iot_devices', 'iot_devices.id = iot_attendance.device_id', 'left')
                ->orderBy('scan_time', 'DESC')
                ->limit(15)
                ->find();

            return $this->respond(['status' => 200, 'data' => $recentAttendance]);
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint untuk IoT Device mengirimkan Heartbeat (Pesan Hidup)
     * URL: /api-android/integration/heartbeat
     */
    public function heartbeat()
    {
        try {
            $deviceIp = $this->request->getVar('device_ip');
            
            if (!$deviceIp) {
                return $this->respond(['status' => 400, 'message' => 'Device IP required'], 400);
            }

            $iotDeviceModel = new IotDeviceModel();
            $device = $iotDeviceModel->where('device_ip', $deviceIp)->first();

            if (!$device) {
                return $this->respond(['status' => 404, 'message' => 'Device not registered'], 404);
            }

            $iotDeviceModel->update($device['id'], [
                'status' => 'online',
                'last_seen' => date('Y-m-d H:i:s')
            ]);

            return $this->respond(['status' => 200, 'message' => 'Heartbeat received']);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint untuk IoT Device mengirimkan data (Push Model)
     * URL: /api-android/integration/push
     */
    public function push()
    {
        try {
            $deviceIp = $this->request->getVar('device_ip');
            $qrData = $this->request->getVar('qr_data'); // Data mentah dari scanner (ID User)
            
            if (!$deviceIp || !$qrData) {
                return $this->respond(['status' => 400, 'message' => 'Missing device_ip or qr_data'], 400);
            }

            $iotDeviceModel = new IotDeviceModel();
            $device = $iotDeviceModel->where('device_ip', $deviceIp)->first();

            if (!$device) {
                return $this->respond(['status' => 404, 'message' => 'Device not registered'], 404);
            }

            // Update status device
            $iotDeviceModel->update($device['id'], [
                'status' => 'online',
                'last_seen' => date('Y-m-d H:i:s')
            ]);

            // Proses Absensi berdasarkan qr_data (misal: ID user 42)
            $userModel = new UserModel();
            $user = $userModel->find($qrData);

            if (!$user) {
                return $this->respond(['status' => 404, 'message' => 'User ID ' . $qrData . ' not found'], 404);
            }

            // Simpan ke log aktivitas IoT
            $iotAttendanceModel = new IotAttendanceModel();
            $iotAttendanceModel->insert([
                'device_id' => $device['id'],
                'user_id' => $user['id'],
                'scan_time' => date('Y-m-d H:i:s'),
                'raw_data' => $qrData
            ]);

            // Simpan ke tabel kehadiran utama
            $kehadiranModel = new KehadiranModel();
            $kehadiranModel->insert([
                'user_id' => $user['id'],
                'status' => 'masuk',
                'keterangan' => 'Absensi IoT: ' . $device['device_name'],
                'tanggal' => date('Y-m-d'),
                'jam' => date('H:i:s')
            ]);

            return $this->respond([
                'status' => 200, 
                'message' => 'Attendance recorded: ' . $user['username'],
                'user' => $user['username']
            ]);

        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy untuk mengambil tampilan web IoT agar tidak diblokir browser (Mixed Content/PNA)
     * URL: /integration/proxy?url=http://192.168.1.1
     */
    public function proxy()
    {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Workflow-Proxy/1.0');
            
            // Bypass SSL check jika ada
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                return "Proxy Error: " . $error_msg;
            }

            curl_close($ch);

            // Jika konten adalah HTML, kita perlu menyuntikkan <base> tag agar link internal tidak rusak
            if (strpos($contentType, 'text/html') !== false) {
                $baseUrl = rtrim($targetUrl, '/') . '/';
                $response = str_replace('<head>', "<head>\n    <base href=\"$baseUrl\">", $response);
            }

            return $this->response
                ->setStatusCode($httpCode)
                ->setContentType($contentType)
                ->setBody($response);

        } catch (\Exception $e) {
            return "Proxy Exception: " . $e->getMessage();
        }
    }

    private function pingDevice($ip, $port)
    {
        $timeout = 2;
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }
}
