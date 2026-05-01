<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class IotAttendanceSeeder extends Seeder
{
    public function run()
    {
        $forge = \Config\Database::forge();

        // Create iot_attendance table
        $forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'scan_time' => [
                'type' => 'DATETIME',
            ],
            'device_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['SUCCESS', 'FAILED'],
                'default'    => 'SUCCESS',
            ],
            'raw_data' => [
                'type' => 'TEXT',
                'null' => true,
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

        $forge->addKey('id', true);
        $forge->addKey('user_id');
        $forge->addKey('username');

        $forge->createTable('iot_attendance', true); // true = IF NOT EXISTS

        echo "Table iot_attendance created successfully!\n";
    }
}
