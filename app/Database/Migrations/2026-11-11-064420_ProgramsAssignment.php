<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProgramsAssignment extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'program_assignment_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'assigned_program_id' => ['type' => 'INT', 'constraint' => 10, 'NULL' => false],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
        ]);
        $this->forge->addKey('program_assignment_id', true);
        $this->forge->addKey('assigned_program_id');

        $this->forge->addForeignKey('program_id', 'programs', 'program_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('programs_assignment');
    }

    public function down()
    {
        $this->forge->dropTable('programs_assignment');
    }
}
