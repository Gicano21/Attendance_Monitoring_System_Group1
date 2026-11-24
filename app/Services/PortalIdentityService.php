<?php

namespace App\Services;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use InvalidArgumentException;
use RuntimeException;

class PortalIdentityService
{
    private const ROLES = ['admin', 'instructor', 'student'];

    public const ACCOUNT_TABLES = [
        ['role' => 'admin', 'table' => 'admin_accounts', 'id' => 'admin_id'],
        ['role' => 'instructor', 'table' => 'instructors', 'id' => 'instructor_id'],
        ['role' => 'student', 'table' => 'students', 'id' => 'student_id'],
    ];

    public function usernameFor(string $role, int $accountId): string
    {
        if (!in_array($role, self::ROLES, true) || $accountId < 1) {
            throw new InvalidArgumentException('A valid portal role and account ID are required.');
        }

        return $role . '_' . $accountId;
    }

    public function assertShieldTablesExist(): void
    {
        $db = db_connect();
        $missingTables = [];

        foreach (config('Auth')->tables as $table) {
            if (!$db->tableExists($table)) {
                $missingTables[] = $table;
            }
        }

        if ($missingTables !== []) {
            throw new RuntimeException(
                'Shield tables are missing (' . implode(', ', $missingTables) . '). Run `php spark migrate --all` before migrating portal accounts or seeding.',
            );
        }
    }

    public function syncExistingAccounts(): void
    {
        $db = db_connect();

        foreach (self::ACCOUNT_TABLES as $account) {
            if (!$db->tableExists($account['table'])) {
                continue;
            }

            foreach ($db->table($account['table'])->get()->getResultArray() as $row) {
                $storedPassword = (string) ($row['password'] ?? '');
                $isHash = (password_get_info($storedPassword)['algo'] ?? null) !== null;
                $looksLikeTruncatedHash = preg_match('/^\$(?:2[aby]\$\d\d\$|argon2(?:i|id)\$)/', $storedPassword) === 1;

                if ($storedPassword === '' || (!$isHash && $looksLikeTruncatedHash)) {
                    log_message(
                        'error',
                        'Shield account import skipped for {role} account {id}: its legacy password is empty or invalid.',
                        ['role' => $account['role'], 'id' => $row[$account['id']]],
                    );
                    continue;
                }

                $user = $this->syncLegacyAccount(
                    $account['role'],
                    (int) $row[$account['id']],
                    $storedPassword,
                );

                if (!$isHash) {
                    $passwordHash = $user->getPasswordHash();
                    if ($passwordHash === null || $passwordHash === '') {
                        throw new RuntimeException('Shield did not create a password hash for a migrated account.');
                    }
                    $db->table($account['table'])
                        ->where($account['id'], $row[$account['id']])
                        ->update(['password' => $passwordHash]);
                }
            }
        }
    }

    public function findAccount(string $role, int $accountId): ?User
    {
        return model(UserModel::class)
            ->where('username', $this->usernameFor($role, $accountId))
            ->first();
    }

    public function createAccount(string $role, int $accountId, string $password): User
    {
        if ($password === '') {
            throw new InvalidArgumentException('A non-empty password is required.');
        }

        return $this->ensureAccount($role, $accountId, $password, false);
    }

    public function syncLegacyAccount(string $role, int $accountId, string $storedPassword): User
    {
        if ($storedPassword === '') {
            throw new InvalidArgumentException('A non-empty legacy password is required.');
        }

        $isHash = (password_get_info($storedPassword)['algo'] ?? null) !== null;
        if (!$isHash && preg_match('/^\$(?:2[aby]\$\d\d\$|argon2(?:i|id)\$)/', $storedPassword) === 1) {
            throw new RuntimeException('The legacy password hash is invalid or truncated.');
        }

        return $this->ensureAccount($role, $accountId, $storedPassword, $isHash);
    }

    public function updatePassword(string $role, int $accountId, string $password): User
    {
        if ($password === '') {
            throw new InvalidArgumentException('A non-empty password is required.');
        }

        $user = $this->findAccount($role, $accountId);
        if ($user === null) {
            return $this->createAccount($role, $accountId, $password);
        }

        if ($user->getEmailIdentity() === null) {
            return $this->ensureAccount($role, $accountId, $password, false);
        }

        if (!$user->inGroup($role)) {
            if (($user->getGroups() ?? []) !== []) {
                throw new RuntimeException('The Shield account is already assigned to a different role.');
            }
            $user->addGroup($role);
        }

        $user->setPassword($password);
        model(UserModel::class)->save($user);

        return $user;
    }

    public function deleteAccount(string $role, int $accountId): void
    {
        $user = $this->findAccount($role, $accountId);
        if ($user !== null) {
            if (!$user->inGroup($role)) {
                throw new RuntimeException('The Shield account is assigned to a different role.');
            }
            model(UserModel::class)->delete($user->id);
        }
    }

    private function ensureAccount(string $role, int $accountId, string $password, bool $passwordIsHash): User
    {
        $username = $this->usernameFor($role, $accountId);
        $userModel = model(UserModel::class);
        $user = $userModel->where('username', $username)->first();

        if ($user === null) {
            $user = $userModel->createNewUser([
                'username' => $username,
                'active' => true,
            ]);
            $user->setEmail($username . '@attendance.invalid');
            if ($passwordIsHash) {
                $user->setPasswordHash($password);
            } else {
                $user->setPassword($password);
            }
            $userModel->save($user);

            $user = $userModel->where('username', $username)->first();
            if ($user === null) {
                throw new RuntimeException('Shield could not reload the saved portal account.');
            }
        } elseif ($user->getEmailIdentity() === null || $user->getPasswordHash() === null || $user->getPasswordHash() === '') {
            $user->setEmail($username . '@attendance.invalid');
            if ($passwordIsHash) {
                $user->setPasswordHash($password);
            } else {
                $user->setPassword($password);
            }
            $userModel->save($user);
            $user = $userModel->where('username', $username)->first();
            if ($user === null) {
                throw new RuntimeException('Shield could not reload the repaired portal account.');
            }
        }

        if (!$user->inGroup($role)) {
            if (($user->getGroups() ?? []) !== []) {
                throw new RuntimeException('The Shield account is already assigned to a different role.');
            }
            $user->addGroup($role);
        }

        return $user;
    }
}
