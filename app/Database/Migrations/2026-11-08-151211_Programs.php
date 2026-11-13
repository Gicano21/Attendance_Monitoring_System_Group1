<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Programs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'program_id' => ['type' => 'INT', 'constraint' => 5, 'auto_increment' => true],
            'program_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
            'program' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('program_id', true);
        $this->forge->createTable('programs');
    }

    public function down()
    {
        $this->forge->dropTable('programs');
    }
}
