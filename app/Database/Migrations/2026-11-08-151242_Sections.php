<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Sections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'sections_id' => ['type' => 'VARCHAR', 'constraint' => 8],
            'sections' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('sections_id', true);
        $this->forge->createTable('sections');
    }

    public function down()
    {
        $this->forge->dropTable('sections');
    }
}
