<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CoursesAssignment extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'course_assignment_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10, 'NULL' => false],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'role' => ['type' => 'ENUM', 'constraint' => ["instructor", "student"], 'default' => "instructor"],
        ]);
        $this->forge->addKey('course_assignment_id', true);
        $this->forge->addKey('assigned_course_id');

        $this->forge->addForeignKey('course_id', 'courses', 'course_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('courses_assignment');
    }

    public function down()
    {
        $this->forge->dropTable('courses_assignment');
    }
}
