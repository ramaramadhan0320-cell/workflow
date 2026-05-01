<?php

namespace App\Models;

use CodeIgniter\Model;

class IotDeviceModel extends Model
{
    protected $table = 'iot_devices';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['device_name', 'device_ip', 'device_port', 'stream_path', 'status', 'last_seen', 'location'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all active IoT devices
     */
    public function getAllDevices()
    {
        return $this->findAll();
    }

    /**
     * Get device by ID
     */
    public function getDeviceById($id)
    {
        return $this->find($id);
    }

    /**
     * Get device by IP address
     */
    public function getDeviceByIp($ip)
    {
        return $this->where('device_ip', $ip)->first();
    }

    /**
     * Get device by IP and port
     */
    public function getDeviceByIpPort($ip, $port)
    {
        return $this->where('device_ip', $ip)->where('device_port', $port)->first();
    }

    /**
     * Add new IoT device
     */
    public function addDevice($data)
    {
        $deviceData = [
            'device_name' => $data['device_name'] ?? 'IoT Device',
            'device_ip' => $data['device_ip'],
            'device_port' => $data['device_port'] ?? 80,
            'status' => 'offline',
            'location' => $data['location'] ?? 'Unknown',
        ];

        return $this->insert($deviceData);
    }

    /**
     * Update device status
     */
    public function updateDeviceStatus($id, $status)
    {
        return $this->update($id, [
            'status' => $status,
            'last_seen' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if device is reachable
     */
    public function checkDeviceStatus($ip, $port = 80)
    {
        $timeout = 2;
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * Get device stream URL
     */
    public function getStreamUrl($id)
    {
        $device = $this->find($id);
        if (!$device) {
            return null;
        }

        $path = !empty($device['stream_path']) ? $device['stream_path'] : '/stream';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return "http://{$device['device_ip']}:{$device['device_port']}{$path}";
    }

    /**
     * Get device info URL
     */
    public function getStatusUrl($id)
    {
        $device = $this->find($id);
        if (!$device) {
            return null;
        }
        return "http://{$device['device_ip']}:{$device['device_port']}/status";
    }

    /**
     * Get device base page URL
     */
    public function getDevicePageUrl($id)
    {
        $device = $this->find($id);
        if (!$device) {
            return null;
        }
        return "http://{$device['device_ip']}:{$device['device_port']}";
    }

    /**
     * Delete device
     */
    public function removeDevice($id)
    {
        return $this->delete($id);
    }

    /**
     * Get online devices count
     */
    public function getOnlineDevicesCount()
    {
        return $this->where('status', 'online')->countAllResults();
    }

    /**
     * Get offline devices count
     */
    public function getOfflineDevicesCount()
    {
        return $this->where('status', 'offline')->countAllResults();
    }
}
