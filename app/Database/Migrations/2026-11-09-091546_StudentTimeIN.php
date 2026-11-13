<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StudentTimeIN extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'student_time_in_id' => ['type' => 'INT', 'constraint' => 8, 'auto_increment' => true],
            'enrolment_id' => ['type' => 'INT', 'constraint' => 8, 'NULL' => false],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'school_year_id' => ['type' => 'VARCHAR', 'constraint' => 10],
            'semester_id' => ['type' => 'INT', 'constraint' => 1],
            'date' => ['type' => 'DATE', 'NULL' => false],
            'time' => ['type' => 'TIME', 'NULL' => false],
        ]);
        $this->forge->addKey('student_time_in_id', true);

        $this->forge->addForeignKey('enrolment_id', 'enrolments', 'enrolment_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('program_id', 'programs', 'program_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'course_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('school_year_id', 'school_year', 'school_year_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('semester_id', 'semesters', 'semester_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('student_time_ins');
    }

    public function down()
    {
        $this->forge->dropTable('student_time_ins');
    }
}
