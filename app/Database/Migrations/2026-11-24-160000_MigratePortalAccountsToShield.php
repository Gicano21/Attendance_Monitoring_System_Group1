<?php

namespace App\Database\Migrations;

use App\Services\PortalIdentityService;
use CodeIgniter\Database\Migration;
use CodeIgniter\Shield\Models\UserModel;
use RuntimeException;

class MigratePortalAccountsToShield extends Migration
{
    public function up()
    {
        $identities = new PortalIdentityService();
        $identities->assertShieldTablesExist();

        foreach (PortalIdentityService::ACCOUNT_TABLES as $account) {
            if ($this->db->tableExists($account['table']) && $this->db->fieldExists('password', $account['table'])) {
                $this->forge->modifyColumn($account['table'], [
                    'password' => [
                        'name' => 'password',
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => false,
                    ],
                ]);
            }
        }

        $this->db->transBegin();
        $identities->syncExistingAccounts();
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new RuntimeException('The portal accounts could not be migrated to Shield.');
        }
    }

    public function down()
    {
        $identities = new PortalIdentityService();
        $userModel = model(UserModel::class);

        foreach (PortalIdentityService::ACCOUNT_TABLES as $account) {
            if (!$this->db->tableExists($account['table'])) {
                continue;
            }

            foreach ($this->db->table($account['table'])->get()->getResultArray() as $row) {
                $user = $identities->findAccount($account['role'], (int) $row[$account['id']]);
                if ($user === null) {
                    continue;
                }

                $passwordHash = $user->getPasswordHash();
                if ($passwordHash !== null && $passwordHash !== '') {
                    $this->db->table($account['table'])
                        ->where($account['id'], $row[$account['id']])
                        ->update(['password' => $passwordHash]);
                }
                $userModel->delete($user->id, true);
            }
        }
    }
}
