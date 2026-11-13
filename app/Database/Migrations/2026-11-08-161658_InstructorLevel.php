<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InstructorLevel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'instructor_level_id' => ['type' => 'INT', 'constraint' => 1],
            'instructor_level' => ['type' => 'VARCHAR', 'constraint' => 15],
        ]);
        $this->forge->addKey('instructor_level_id', true);
        $this->forge->createTable('instructor_levels');
    }

    public function down()
    {
        $this->forge->dropTable('instructor_levels');
    }
}
