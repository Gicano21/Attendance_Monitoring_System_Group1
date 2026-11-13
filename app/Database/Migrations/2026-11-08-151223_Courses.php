<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Courses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'course_id' => ['type' => 'int', 'constraint' => 5, 'auto_increment' => true],
            'course_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
            'course' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('course_id', true);
        $this->forge->createTable('courses');
    }

    public function down()
    {
        $this->forge->dropTable('courses');
    }
}
