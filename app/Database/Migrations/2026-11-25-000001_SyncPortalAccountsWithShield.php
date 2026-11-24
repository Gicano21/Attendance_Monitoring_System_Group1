<?php

namespace App\Database\Migrations;

use App\Services\PortalIdentityService;
use CodeIgniter\Database\Migration;
use RuntimeException;

class SyncPortalAccountsWithShield extends Migration
{
    public function up()
    {
        $identities = new PortalIdentityService();
        $identities->assertShieldTablesExist();

        $this->db->transBegin();
        $identities->syncExistingAccounts();
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('The existing portal accounts could not be synchronized with Shield.');
        }
    }

    public function down()
    {
        throw new RuntimeException('Portal account synchronization cannot be safely reversed.');
    }
}
