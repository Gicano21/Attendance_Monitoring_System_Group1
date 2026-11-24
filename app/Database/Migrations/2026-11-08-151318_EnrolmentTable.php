<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnrolmentTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'enrolment_id' => ['type' => 'INT', 'constraint' => 8, 'auto_increment' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 8],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'year_id' => ['type' => 'INT', 'constraint' => 1],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'school_year_id' => ['type' => 'INT', 'constraint' => 10],
            'semester_id' => ['type' => 'INT', 'constraint' => 1],
            'section_id' => ['type' => 'VARCHAR', 'constraint' => 8],
        ]);
        $this->forge->addKey('enrolment_id', true);

        $this->forge->addForeignKey('student_id', 'students', 'student_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('program_id', 'programs', 'program_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('year_id', 'year', 'year_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_course_id', 'courses_assignment', 'assigned_course_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('school_year_id', 'school_year', 'school_year_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('semester_id', 'semesters', 'semester_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('section_id', 'sections', 'sections_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('enrolments');
    }

    public function down()
    {
        $this->forge->dropTable('enrolments');
    }
    
}
