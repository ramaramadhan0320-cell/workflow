<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWithdrawalBypassTable extends Migration
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
            'admin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'admin_username' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'bypass_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unique' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('admin_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('withdrawal_bypass');
    }

    public function down()
    {
        $this->forge->dropTable('withdrawal_bypass');
    }
}
