<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Year extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'year_id' => ['type' => 'INT', 'constraint' => 1, 'auto_increment' => true],
            'year' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('year_id', true);
        $this->forge->createTable('year');
    }

    public function down()
    {
        $this->forge->dropTable('year');
    }
}
