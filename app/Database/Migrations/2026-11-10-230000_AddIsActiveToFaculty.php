<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToFaculty extends Migration
{
    public function up()
    {
        $this->forge->addColumn('faculty', [
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'null' => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('faculty', 'is_active');
    }
}
