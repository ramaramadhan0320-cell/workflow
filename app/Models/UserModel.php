<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'username', 
        'password', 
        'profile', 
        'alamat', 
        'tempat_lahir', 
        'tanggal_lahir', 
        'pendidikan_terakhir', 
        'tahun_mulai_bekerja', 
        'email',
        'gaji_total',
        'bank_tujuan',
        'nomor_rekening',
        'role',
        'secret_key',
        'can_announce'
    ];
}
