<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SchoolYear extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'school_year_id' => ['type' => 'VARCHAR', 'constraint' => 10],
            'year' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('school_year_id', true);
        $this->forge->createTable('school_year');
    }

    public function down()
    {
        $this->forge->dropTable('school_year');
    }
}
