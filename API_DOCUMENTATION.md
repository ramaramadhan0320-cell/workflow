# IoT Device API Documentation

## Overview
API untuk perangkat IoT (seperti ESP32-CAM) mengirim data terenkripsi ke aplikasi web CodeIgniter 4.

## Konfigurasi
- **Encryption Key**: Sudah dikonfigurasi di `.env`
- **Driver**: OpenSSL
- **Helper**: `iot_helper` sudah di-autoload

## API Endpoints

### 1. Receive Data from IoT Device
```
GET /api/iot/receive-data?data={encrypted_data}
```

**Parameter:**
- `data`: Base64 encoded encrypted JSON data

**Response Success:**
```json
{
    "status": "success",
    "message": "Access granted for user: username",
    "user_id": 1,
    "timestamp": "2026-04-04 17:43:17"
}
```

**Response Error:**
```json
{
    "status": 400,
    "message": "Invalid payload structure"
}
```

### 2. Generate QR Code Data (Testing)
```
GET /api/iot/generate-qr/{user_id}
```

**Response:**
```json
{
    "status": "success",
    "qr_data": "base64_encoded_encrypted_data",
    "temp_password": "generated_password",
    "user": "username"
}
```

## Cara Penggunaan

### 1. Generate QR Data untuk User
```php
// Di Controller atau Helper
$userId = 1;
$qrData = generate_encrypted_qr_data($userId);
// Hasil: base64 string yang bisa dijadikan QR code
```

### 2. ESP32-CAM Mengirim Data
```cpp
// ESP32 Code Example
String encryptedData = "base64_from_qr_code";
String url = "http://192.168.2.6:8080/api/iot/receive-data?data=" + encryptedData;
HTTPClient http;
http.begin(url);
int httpCode = http.GET();
// Process response...
```

## Struktur Data Terenkripsi
```json
{
    "id_user": "1",
    "username": "admin",
    "password": "temporary_password"
}
```

## Database Tables
- `iot_attendance`: Log absensi dari perangkat IoT
- `users`: Tabel user untuk verifikasi kredensial

## Security Features
- AES-256 encryption menggunakan OpenSSL
- Bcrypt password verification
- CSRF bypass untuk API endpoints
- IP logging untuk tracking device

## Testing
1. Generate QR: `http://192.168.2.6:8080/api/iot/generate-qr/1`
2. Test API: `http://192.168.2.6:8080/api/iot/receive-data?data={generated_qr_data}`