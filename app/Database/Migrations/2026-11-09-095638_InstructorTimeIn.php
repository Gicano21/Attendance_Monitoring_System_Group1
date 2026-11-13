<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InstructorTimeIn extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'instructor_time_in_id' => ['type' => 'INT', 'constraint' => 8, 'auto_increment' => true],
            'faculty_id' => ['type' => 'INT', 'constraint' => 10, 'NULL' => false],
            'date' => ['type' => 'DATE', 'NULL' => false],
            'time' => ['type' => 'TIME', 'NULL' => false],
        ]);
        $this->forge->addKey('instructor_time_in_id', true);

        $this->forge->addForeignKey('faculty_id', 'faculty', 'faculty_id', 'CASCADE', 'CASCADE');
    
        $this->forge->createTable('instructor_time_in');
    }

    public function down()
    {
        $this->forge->dropTable('instructor_time_in');
    }
}
