<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;
use App\Models\KehadiranModel;

class AutoAlfa extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'auth:auto-alfa';
    protected $description = 'Mengisi status alfa bagi user yang tidak absen kemarin (Skip Sabtu & Minggu).';

    public function run(array $params)
    {
        $userModel = new UserModel();
        $kehadiranModel = new KehadiranModel();

        // 1. Ambil tanggal kemarin
        $kemarin = date('Y-m-d', strtotime('-1 days'));
        
        // 2. Cek hari apa tanggal kemarin tersebut
        // 'N' mengembalikan angka 1 (Senin) sampai 7 (Minggu)
        $hariKemarin = (int)date('N', strtotime($kemarin));

        CLI::write("Menjalankan pengecekan Alfa untuk tanggal: {$kemarin}", 'cyan');

        // 3. Jika kemarin Sabtu (6) atau Minggu (7), batalkan proses
        if ($hariKemarin === 6 || $hariKemarin === 7) {
            $namaHari = ($hariKemarin === 6) ? 'Sabtu' : 'Minggu';
            CLI::write("Tanggal {$kemarin} adalah hari {$namaHari}. Proses Auto-Alfa dihentikan (Libur).", 'white', 'blue');
            return;
        }

        // 4. Ambil semua user aktif
        $users = $userModel->findAll();

        if (empty($users)) {
            CLI::write('Tidak ada user ditemukan.', 'red');
            return;
        }

        $count = 0;
        foreach ($users as $user) {
            // 5. Cek apakah user sudah punya catatan (hadir/sakit/alfa) untuk tanggal kemarin
            $cek = $kehadiranModel->where('user_id', $user['id'])
                                 ->where('tanggal', $kemarin)
                                 ->first();

            // 6. Jika tidak ada data sama sekali, masukkan status 'alfa'
            if (!$cek) {
                $kehadiranModel->insert([
                    'user_id'    => $user['id'],
                    'tanggal'    => $kemarin,
                    'status'     => 'alfa',
                    'created_at' => date('Y-m-d 23:59:59') // Set di akhir hari kemarin
                ]);
                CLI::write("User ID {$user['id']} ({$user['username']}) ditandai Alfa.", 'yellow');
                $count++;
            }
        }

        CLI::write("Proses Selesai! Total {$count} user ditandai Alfa.", 'green');
    }
}