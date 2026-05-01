<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIotAttendanceTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('username');
        $this->forge->createTable('iot_attendance');
    }

    public function down()
    {
        $this->forge->dropTable('iot_attendance');
    }
}
