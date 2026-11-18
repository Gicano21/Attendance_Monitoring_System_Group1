<?php

use App\Database\Migrations\AttendanceWorkflow;
use App\Database\Migrations\AddAttendanceScheduleDayOfWeek;
use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . '2026-11-19-000001_AttendanceWorkflow.php';
require_once APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . '2026-11-19-000002_AddAttendanceScheduleDayOfWeek.php';

/**
 * @internal
 */
final class AttendanceWorkflowMigrationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect('tests');
        $this->dropTables();
        $this->createAccountTable('admin_accounts', 'admin_id', [
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->createAccountTable('students', 'student_id', [
            'in_campus' => ['type' => 'BOOLEAN'],
        ]);
        $this->createAccountTable('instructors', 'instructor_id', [
            'in_campus' => ['type' => 'BOOLEAN'],
        ]);
        $this->createPrimaryTable('faculty', 'faculty_id');
        $this->createPrimaryTable('programs', 'program_id');
        $this->createPrimaryTable('courses', 'course_id');

        foreach ([
            ['admin_accounts', 'admin_id', ['username' => 'admin', 'email' => 'admin@example.test']],
            ['students', 'student_id', ['in_campus' => false]],
            ['instructors', 'instructor_id', ['in_campus' => false]],
        ] as [$table, $key, $fields]) {
            $fields['password'] = 'legacy-password';
            $this->db->table($table)->insert($fields);
        }
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function testMigrationHashesLegacyPasswordsAndCreatesAttendanceTables(): void
    {
        (new AttendanceWorkflow())->up();

        foreach ([
            ['admin_accounts', 'admin_id'],
            ['students', 'student_id'],
            ['instructors', 'instructor_id'],
        ] as [$table, $key]) {
            $account = $this->db->table($table)->get()->getRowArray();
            $this->assertNotNull(password_get_info($account['password'])['algo']);
            $this->assertTrue(password_verify('legacy-password', $account['password']));
        }

        $this->assertTrue($this->db->tableExists('attendance_schedules'));
        $this->assertTrue($this->db->tableExists('attendance_records'));

        $this->db->table('faculty')->insert(['faculty_id' => 1]);
        $this->db->table('programs')->insert(['program_id' => 1]);
        $this->db->table('courses')->insert(['course_id' => 1]);
        $this->db->table('attendance_schedules')->insert([
            'faculty_id' => 1,
            'program_id' => 1,
            'course_id' => 1,
            'schedule_date' => '2026-11-19',
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
            'created_at' => '2026-11-19 08:00:00',
        ]);

        $migration = new AddAttendanceScheduleDayOfWeek();
        $migration->up();
        $legacySchedule = $this->db->table('attendance_schedules')->get()->getRowArray();
        $this->assertArrayHasKey('day_of_week', $legacySchedule);
        $this->assertNull($legacySchedule['day_of_week']);
        $migration->down();
        $this->assertNotContains('day_of_week', $this->db->getFieldNames('attendance_schedules'));
    }

    private function createAccountTable(string $table, string $primaryKey, array $fields): void
    {
        $forge = $this->forge();
        $forge->addField([
            $primaryKey => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'password' => ['type' => 'VARCHAR', 'constraint' => 50],
            ...$fields,
        ]);
        $forge->addKey($primaryKey, true);
        $forge->createTable($table);
    }

    private function createPrimaryTable(string $table, string $primaryKey): void
    {
        $forge = $this->forge();
        $forge->addField([
            $primaryKey => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
        ]);
        $forge->addKey($primaryKey, true);
        $forge->createTable($table);
    }

    private function dropTables(): void
    {
        $forge = $this->forge();
        foreach (['attendance_records', 'attendance_schedules', 'faculty', 'programs', 'courses', 'students', 'instructors', 'admin_accounts'] as $table) {
            $forge->dropTable($table, true);
        }
    }

    private function forge(): \CodeIgniter\Database\Forge
    {
        return \Config\Database::forge('tests');
    }
}
