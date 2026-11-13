<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdminTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'admin_id' => ['type' => 'INT', 'constraint' => 5, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'password' => ['type' => 'VARCHAR', 'constraint' => 50],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('admin_id', true);
        $this->forge->createTable('admin_accounts');
    }

    public function down()
    {
        $this->forge->dropTable('admin_accounts');
    }
}
