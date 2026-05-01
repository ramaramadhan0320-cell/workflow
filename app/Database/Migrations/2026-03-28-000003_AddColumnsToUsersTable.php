<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToUsersTable extends Migration
{
    public function up()
    {
        // Add new columns to users table
        $this->forge->addColumn('users', [
            'profile' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tempat_lahir' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'tanggal_lahir' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'pendidikan_terakhir' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'tahun_mulai_bekerja' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'unique'     => false,
            ],
            'gaji_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'null'       => false,
            ],
            'bank_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'DANA, BCA, Mandiri, BNI, BRI, SeaBank',
            ],
            'nomor_rekening' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        // Drop the columns if rolling back
        $this->forge->dropColumn('users', [
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
        ]);
    }
}
