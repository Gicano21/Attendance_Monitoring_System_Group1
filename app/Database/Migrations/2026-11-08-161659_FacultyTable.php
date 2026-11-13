<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FacultyTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'faculty_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'instructor_id' => ['type' => 'INT', 'constraint' => 8],
            'instructor_level_id' => ['type' => 'INT', 'constraint' => 1],
            'assigned_program_id' => ['type' => 'INT', 'constraint' => 10, 'NULL' => false],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10, 'NULL' => false],
        ]);
        $this->forge->addKey('faculty_id', true);

        $this->forge->addForeignKey('instructor_id', 'instructors', 'instructor_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_course_id', 'courses_assignment', 'assigned_course_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('instructor_level_id', 'instructor_levels', 'instructor_level_id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('faculty');
    }

    public function down()
    {
        $this->forge->dropTable('faculty');
    }
}
