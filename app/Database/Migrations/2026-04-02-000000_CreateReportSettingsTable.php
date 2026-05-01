<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportSettingsTable extends Migration
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
            'modal_awal' => [
                'type'       => 'BIGINT',
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new \DateTime(),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => new \DateTime(),
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('report_settings');

        // Insert default row
        $this->db->table('report_settings')->insert([
            'modal_awal' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('report_settings');
    }
}
