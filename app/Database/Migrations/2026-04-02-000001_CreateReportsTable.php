<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'nama_transaksi' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'harga' => [
                'type' => 'INT',
                'default' => 0,
                'comment' => 'Harga untuk transaksi keluar',
            ],
            'harga_masuk' => [
                'type' => 'INT',
                'default' => 0,
                'comment' => 'Harga untuk transaksi masuk',
            ],
            'tanggal_transaksi' => [
                'type' => 'DATE',
            ],
            'tipe_transaksi' => [
                'type' => 'ENUM',
                'constraint' => ['masuk', 'keluar'],
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reports');
    }

    public function down()
    {
        $this->forge->dropTable('reports');
    }
}
