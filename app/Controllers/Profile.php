<?php

namespace App\Controllers;

use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userId = session()->get('id');
        $data['user'] = $this->userModel->find($userId);

        return view('profile', $data);
    }

    public function update()
    {
        $userId = session()->get('id');

        // ❗ HANDLE SESSION HABIS
        if (!$userId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                return $this->response->setJSON([
                    'status' => 401,
                    'message' => 'Session habis'
                ]);
            } else {
                return redirect()->to('/login')->with('error', 'Session habis');
            }
        }

        // ✅ Ambil data form
        $data = [
            'alamat'              => $this->request->getPost('alamat'),
            'tempat_lahir'        => $this->request->getPost('tempat_lahir'),
            'tanggal_lahir'       => $this->request->getPost('tanggal_lahir'),
            'pendidikan_terakhir' => $this->request->getPost('pendidikan_terakhir'),
            'tahun_mulai_bekerja' => $this->request->getPost('tahun_mulai_bekerja'),
            'email'               => $this->request->getPost('email'),
            'bank_tujuan'         => $this->request->getPost('bank_tujuan'),
            'nomor_rekening'      => $this->request->getPost('nomor_rekening'),
        ];

        // 🔥 buang field kosong
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        // ❗ VALIDASI EMAIL
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Format email tidak valid'
                ]);
            } else {
                return redirect()->back()->with('error', 'Format email tidak valid');
            }
        }

        // ❗ VALIDASI NOMOR REKENING
        if (isset($data['bank_tujuan']) && isset($data['nomor_rekening'])) {
            $bank = $data['bank_tujuan'];
            $rekening = $data['nomor_rekening'];
            
            $validationRules = [
                'DANA' => ['min' => 10, 'max' => 13, 'pattern' => '/^(08|\+62)/'],
                'SeaBank' => ['min' => 12, 'max' => 12, 'pattern' => '/^\d{12}$/'],
                'BCA' => ['min' => 10, 'max' => 10, 'pattern' => '/^\d{10}$/'],
                'Mandiri' => ['min' => 13, 'max' => 13, 'pattern' => '/^\d{13}$/'],
                'BNI' => ['min' => 10, 'max' => 10, 'pattern' => '/^\d{10}$/'],
                'BRI' => ['min' => 15, 'max' => 15, 'pattern' => '/^\d{15}$/'],
            ];
            
            if (isset($validationRules[$bank])) {
                $rule = $validationRules[$bank];
                $digitsOnly = preg_replace('/\D/', '', $rekening);
                $digitCount = strlen($digitsOnly);
                
                if ($digitCount < $rule['min'] || $digitCount > $rule['max']) {
                    $errorMsg = "Format nomor rekening {$bank} tidak valid. ";
                    if ($rule['min'] === $rule['max']) {
                        $errorMsg .= "Harus {$rule['min']} digit, Anda {$digitCount} digit";
                    } else {
                        $errorMsg .= "Harus {$rule['min']}-{$rule['max']} digit, Anda {$digitCount} digit";
                    }
                    
                    if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                        return $this->response->setJSON([
                            'status' => 400,
                            'message' => $errorMsg
                        ]);
                    } else {
                        return redirect()->back()->with('error', $errorMsg);
                    }
                }
                
                if (!preg_match($rule['pattern'], $rekening)) {
                    $errorMsg = "Format nomor rekening {$bank} tidak sesuai dengan standar";
                    if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                        return $this->response->setJSON([
                            'status' => 400,
                            'message' => $errorMsg
                        ]);
                    } else {
                        return redirect()->back()->with('error', $errorMsg);
                    }
                }
            }
        }

        // =========================
        // UPLOAD FOTO KE NEXTCLOUD
        // =========================
        $file = $this->request->getFile('profile');

        if ($file && $file->isValid() && !$file->hasMoved()) {

            // ❗ VALIDASI FILE
            $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                    return $this->response->setJSON([
                        'status' => 400,
                        'message' => 'Format gambar tidak valid (jpg/png)'
                    ]);
                } else {
                    return redirect()->back()->with('error', 'Format gambar tidak valid (jpg/png)');
                }
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                    return $this->response->setJSON([
                        'status' => 400,
                        'message' => 'Ukuran gambar maksimal 2MB'
                    ]);
                } else {
                    return redirect()->back()->with('error', 'Ukuran gambar maksimal 2MB');
                }
            }

            // 🔥 Nama file unik
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientName());

            $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
            $nextcloudUser = env('nextcloud.username', 'masterraden');
            $nextcloudPassword = env('nextcloud.password', 'masterraden');
            $sslVerify = env('nextcloud.ssl_verify', false);

            // 🔥 Upload ke Nextcloud WebDAV (user/workflow/profile/)
            $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/profile/" . urlencode($fileName);

            $fp = fopen($file->getTempName(), 'r');
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->getTempName()));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);
            fclose($fp);

            // 🔥 CHECK UPLOAD STATUS (201 = Created, 204 = No Content)
            if ($httpCode == 201 || $httpCode == 204) {
                // ✅ SIMPAN HANYA NAMA FILE
                $data['profile'] = $fileName;
            } else {
                $errorMsg = "Upload ke Nextcloud gagal (HTTP: $httpCode)";
                if ($curlError) {
                    $errorMsg .= " - " . $curlError;
                }
                if ($httpCode == 409) {
                    $errorMsg = "Folder /profile tidak ada di Nextcloud. Buat folder terlebih dahulu.";
                }
                
                if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                    return $this->response->setJSON([
                        'status' => 500,
                        'message' => $errorMsg,
                        'http_code' => $httpCode,
                        'curl_error' => $curlError,
                        'url' => $url
                    ]);
                } else {
                    return redirect()->back()->with('error', $errorMsg);
                }
            }
        }

        // =========================
        // UPDATE DATABASE
        // =========================
        if ($this->userModel->update($userId, $data)) {
            // Cek apakah dari AJAX request
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Profil berhasil diperbarui'
                ]);
            } else {
                // Regular form submission - redirect ke absensi
                return redirect()->to('/absensi')->with('success', 'Profil berhasil diperbarui');
            }
        }

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With')) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Gagal update profil'
            ]);
        } else {
            return redirect()->back()->with('error', 'Gagal update profil');
        }
    }

    public function resetPassword()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Session habis');
        }

        $newPassword = $this->request->getPost('new_password');

        if (!$newPassword) {
            return redirect()->to('/absensi')->with('error', 'Password tidak boleh kosong');
        }

        $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/absensi')->with('success', 'Password berhasil direset');
    }

    public function getProfileImage($filename)
    {
        // Pastikan user login
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setBody('Unauthorized');
        }

        $filename = basename($filename); // Path traversal protection
        
        $nextcloudBase = rtrim(env('nextcloud.url', 'http://192.168.100.20:8080'), '/');
        $nextcloudUser = env('nextcloud.username', 'masterraden');
        $nextcloudPassword = env('nextcloud.password', 'masterraden');
        $sslVerify = env('nextcloud.ssl_verify', false);

        // Fetch dari Nextcloud dengan authentikasi
        $url = $nextcloudBase . "/remote.php/dav/files/" . urlencode($nextcloudUser) . "/workflow/profile/" . urlencode($filename);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $nextcloudUser . ":" . $nextcloudPassword);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : false);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if ($httpCode == 200 && !empty($imageData)) {
            return $this->response
                ->setHeader('Content-Type', $contentType ?: 'image/jpeg')
                ->setBody($imageData);
        }

        return $this->response->setStatusCode(404)->setBody('File not found');
    }
}