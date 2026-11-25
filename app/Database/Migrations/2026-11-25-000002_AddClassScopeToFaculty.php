<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClassScopeToFaculty extends Migration
{
    public function up()
    {
        $this->forge->addColumn('faculty', [
            'year_id' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'semester_id' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'section_id' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('faculty', ['year_id', 'semester_id', 'section_id']);
    }
}
