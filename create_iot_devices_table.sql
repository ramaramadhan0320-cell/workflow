-- Buat tabel iot_devices untuk menyimpan data perangkat IoT
CREATE TABLE IF NOT EXISTS iot_devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_name VARCHAR(100) NOT NULL,
  device_ip VARCHAR(15) NOT NULL,
  device_port INT DEFAULT 80,
  stream_path VARCHAR(100) DEFAULT '/stream',
  status ENUM('online', 'offline') DEFAULT 'offline',
  last_seen DATETIME,
  location VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_device_ip (device_ip, device_port)
);

-- Insert sample device (opsional)
INSERT INTO iot_devices (device_name, device_ip, device_port, status, location) 
VALUES ('ESP32_Absensi_01', '192.168.100.88', 80, 'online', 'Main Entrance');
