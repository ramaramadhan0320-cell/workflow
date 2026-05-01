<?php

if (!function_exists('generate_encrypted_qr_data')) {
    /**
     * Generate encrypted QR data for IoT device authentication
     *
     * @param int $userId User ID
     * @param string|null $password Custom password (optional, will generate random if null)
     * @return string|null Base64 encoded encrypted data or null on failure
     */
    function generate_encrypted_qr_data($userId, $password = null)
    {
        try {
            // Load database
            $db = \Config\Database::connect();

            // Get user data
            $user = $db->table('users')->where('id', $userId)->get()->getRow();
            if (!$user) {
                return null;
            }

            // Generate temporary password if not provided
            if (!$password) {
                $password = bin2hex(random_bytes(8)); // 16 character hex
            }

            // Prepare data for encryption
            $data = [
                "id_user"   => (string)$user->id,
                "username"  => $user->username,
                "password"  => $password
            ];

            // Encrypt data
            $encrypter = \Config\Services::encrypter();
            $ciphertext = base64_encode($encrypter->encrypt(json_encode($data)));

            return $ciphertext;

        } catch (\Exception $e) {
            log_message('error', 'QR Helper Error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('decrypt_qr_data')) {
    /**
     * Decrypt QR data received from IoT device
     *
     * @param string $encryptedData Base64 encoded encrypted data
     * @return array|null Decrypted payload or null on failure
     */
    function decrypt_qr_data($encryptedData)
    {
        try {
            $encrypter = \Config\Services::encrypter();

            // Decode base64 and decrypt
            $decrypted_json = $encrypter->decrypt(base64_decode($encryptedData));
            $payload = json_decode($decrypted_json, true);

            return $payload;

        } catch (\Exception $e) {
            log_message('error', 'QR Decrypt Error: ' . $e->getMessage());
            return null;
        }
    }
}