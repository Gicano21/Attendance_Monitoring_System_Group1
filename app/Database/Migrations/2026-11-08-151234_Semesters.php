<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Semesters extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'semester_id' => ['type' => 'INT', 'constraint' => 1, 'auto_increment' => true],
            'semester' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('semester_id', true);
        $this->forge->createTable('semesters');
    }

    public function down()
    {
        $this->forge->dropTable('semesters');
    }
}
