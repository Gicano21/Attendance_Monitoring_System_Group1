<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StudentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'student_id' => ['type' => 'INT', 'constraint' => 8, 'auto_increment' => true],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'middle_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'extension_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'gender' => ['type' => 'VARCHAR', 'constraint' => 6],
            'birth_date' => ['type' => 'DATE'],
            'password' => ['type' => 'VARCHAR', 'constraint' => 50],
            'in_campus' => ['type' => 'BOOLEAN', 'NULL' => false],
        ]);
        $this->forge->addKey('student_id', true);
        $this->forge->createTable('students');
    }

    public function down()
    {
        $this->forge->dropTable('students');
    }
}
