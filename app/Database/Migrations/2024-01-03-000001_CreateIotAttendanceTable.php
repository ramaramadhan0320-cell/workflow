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
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['masuk', 'pulang', 'sakit', 'izin'],
                'default'    => 'masuk',
            ],
            'scan_time' => [
                'type' => 'DATETIME',
            ],
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'qr_content' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'is_valid' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id', false, 'idx_iot_user');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_iot_attendance_user_id');
        $this->forge->createTable('iot_attendance');
    }

    public function down()
    {
        $this->forge->dropTable('iot_attendance');
    }
}