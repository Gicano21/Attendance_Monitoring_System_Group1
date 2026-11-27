<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeletesToResources extends Migration
{
    public function up()
    {
        foreach (['students', 'instructors', 'programs', 'courses'] as $table) {
            if ($this->db->tableExists($table) && !$this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, [
                    'deleted_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        foreach (['students', 'instructors', 'programs', 'courses'] as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('deleted_at', $table)) {
                $this->forge->dropColumn($table, 'deleted_at');
            }
        }
    }
}
