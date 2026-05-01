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
        
        if (is_object($user)) {
            $user = (array) $user;
        }

        return view('integration', [
            'title' => 'Integrasi IoT',
            'user'  => $user,
            'role'  => session()->get('role')
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

            // Sanitasi IP
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
     * Cek status koneksi (Hanya ping port)
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

            // Cek koneksi sederhana ke IP & Port
            $connection = @fsockopen($device['device_ip'], $device['device_port'], $errno, $errstr, 2);
            $isOnline = is_resource($connection);
            if ($isOnline) {
                fclose($connection);
            }

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
     * Tampilan Scanner (Langsung ke IP IoT)
     */
    public function scanner()
    {
        $deviceIp = $this->request->getVar('ip');
        $devicePort = $this->request->getVar('port');
        $path = $this->request->getVar('path');
        
        if (empty($path)) {
            $path = ($devicePort == 1984 || $devicePort == 1884) ? '/api/stream.mjpeg?src=kamera_absensi' : '/?action=stream';
        }

        $streamUrl = "http://{$deviceIp}:{$devicePort}{$path}";

        return view('iot_scanner', [
            'streamUrl' => $streamUrl,
            'deviceName' => 'Scanner Mode'
        ]);
    }
}
