<?php

namespace App\Controllers;

use App\Models\IotDeviceModel;
use CodeIgniter\Controller;

class Integration extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find(session()->get('userId'));
        
        return view('integration', [
            'title' => 'Integrasi IoT',
            'user'  => $user,
            'role'  => session()->get('role')
        ]);
    }

    public function getDevices()
    {
        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            return $this->response->setJSON(['success' => true, 'data' => $iotDeviceModel->findAll()]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addDevice()
    {
        try {
            $data = $this->request->getJSON(true);
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $iotDeviceModel->save([
                'device_name' => $data['device_name'],
                'device_ip'   => $data['device_ip'],
                'device_port' => $data['device_port'] ?? 80,
                'stream_path' => $data['stream_path'] ?? '',
                'page_url'    => $data['page_url'] ?? '',
                'status'      => 'offline'
            ]);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteDevice($id)
    {
        try {
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $iotDeviceModel->delete($id);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function checkDeviceStatus()
    {
        try {
            $deviceId = $this->request->getVar('device_id');
            $iotDeviceModel = new \App\Models\IotDeviceModel();
            $device = $iotDeviceModel->find($deviceId);
            
            $connection = @fsockopen($device['device_ip'], $device['device_port'], $errno, $errstr, 1);
            $isOnline = is_resource($connection);
            if ($isOnline) fclose($connection);

            $status = $isOnline ? 'online' : 'offline';
            $iotDeviceModel->update($deviceId, ['status' => $status, 'last_seen' => date('Y-m-d H:i:s')]);

            return $this->response->setJSON(['success' => true, 'status' => $status]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false]);
        }
    }

    public function scanner()
    {
        $deviceIp = $this->request->getVar('ip');
        $devicePort = $this->request->getVar('port');
        $path = $this->request->getVar('path') ?: '/api/stream.mjpeg?src=kamera_absensi';
        
        return view('iot_scanner', [
            'streamUrl' => "http://{$deviceIp}:{$devicePort}{$path}",
            'deviceName' => 'Scanner Mode'
        ]);
    }
}
