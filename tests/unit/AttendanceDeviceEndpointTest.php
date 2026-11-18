<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AttendanceDeviceEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const DEVICE_KEY_ENV = 'ATTENDANCE_DEVICE_API_KEY';

    private array $originalEnvironment = [];

    private BaseConnection $portalDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvironment = [
            'env' => $_ENV[self::DEVICE_KEY_ENV] ?? null,
            'env_exists' => array_key_exists(self::DEVICE_KEY_ENV, $_ENV),
            'server' => $_SERVER[self::DEVICE_KEY_ENV] ?? null,
            'server_exists' => array_key_exists(self::DEVICE_KEY_ENV, $_SERVER),
            'process' => getenv(self::DEVICE_KEY_ENV),
        ];

        unset($_ENV[self::DEVICE_KEY_ENV], $_SERVER[self::DEVICE_KEY_ENV]);
        putenv(self::DEVICE_KEY_ENV);

        $this->portalDb = \Config\Database::connect('tests');
        $this->dropTables();
        $this->createTable('students', [
            'student_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'in_campus' => ['type' => 'BOOLEAN'],
        ], 'student_id');
        $this->createTable('attendance_schedules', [
            'schedule_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'faculty_id' => ['type' => 'INT', 'constraint' => 10],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'schedule_date' => ['type' => 'DATE'],
            'day_of_week' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'starts_at' => ['type' => 'TIME'],
            'ends_at' => ['type' => 'TIME'],
            'created_at' => ['type' => 'DATETIME'],
        ], 'schedule_id');
        $this->createTable('enrolments', [
            'enrolment_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 10],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'school_year_id' => ['type' => 'INT', 'constraint' => 10],
            'semester_id' => ['type' => 'INT', 'constraint' => 10],
        ], 'enrolment_id');
        $this->createTable('courses_assignment', [
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'role' => ['type' => 'VARCHAR', 'constraint' => 12],
        ], 'assigned_course_id');
        $this->createTable('attendance_records', [
            'attendance_record_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'participant_role' => ['type' => 'VARCHAR', 'constraint' => 12],
            'participant_id' => ['type' => 'INT', 'constraint' => 10],
            'schedule_id' => ['type' => 'INT', 'constraint' => 10],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 3],
            'scanned_at' => ['type' => 'DATETIME'],
        ], 'attendance_record_id');
        $this->createTable('student_time_ins', [
            'student_time_in_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'enrolment_id' => ['type' => 'INT', 'constraint' => 10],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'school_year_id' => ['type' => 'INT', 'constraint' => 10],
            'semester_id' => ['type' => 'INT', 'constraint' => 10],
            'date' => ['type' => 'DATE'],
            'time' => ['type' => 'TIME'],
        ], 'student_time_in_id');
    }

    protected function tearDown(): void
    {
        $this->dropTables();

        if ($this->originalEnvironment['env_exists']) {
            $_ENV[self::DEVICE_KEY_ENV] = $this->originalEnvironment['env'];
        } else {
            unset($_ENV[self::DEVICE_KEY_ENV]);
        }

        if ($this->originalEnvironment['server_exists']) {
            $_SERVER[self::DEVICE_KEY_ENV] = $this->originalEnvironment['server'];
        } else {
            unset($_SERVER[self::DEVICE_KEY_ENV]);
        }

        if ($this->originalEnvironment['process'] === false) {
            putenv(self::DEVICE_KEY_ENV);
        } else {
            putenv(self::DEVICE_KEY_ENV . '=' . $this->originalEnvironment['process']);
        }

        parent::tearDown();
    }

    public function testEndpointRejectsRequestsWhenDeviceKeyIsNotConfigured(): void
    {
        $response = $this->withBodyFormat('json')
            ->post('/api/Scan_QR_Code', ['ID' => 1, 'Role' => 'Student']);

        $response->assertStatus(503);
        $response->assertJSONFragment(['ok' => false]);
    }

    public function testEndpointRejectsAnInvalidDeviceKey(): void
    {
        $_ENV[self::DEVICE_KEY_ENV] = str_repeat('a', 64);

        $response = $this->withHeaders(['X-Device-Key' => 'incorrect'])
            ->withBodyFormat('json')
            ->post('/api/Scan_QR_Code', ['ID' => 1, 'Role' => 'Student']);

        $response->assertStatus(401);
        $response->assertJSONFragment(['ok' => false]);
    }

    public function testAuthenticatedDeviceRequestReachesPayloadValidation(): void
    {
        $_ENV[self::DEVICE_KEY_ENV] = str_repeat('a', 64);

        $response = $this->withHeaders(['X-Device-Key' => str_repeat('a', 64)])
            ->withBodyFormat('json')
            ->post('/api/Scan_QR_Code', []);

        $response->assertStatus(422);
        $response->assertJSONFragment(['ok' => false]);
    }

    public function testAuthenticatedDeviceCanCheckInWithoutParticipantSession(): void
    {
        $key = str_repeat('b', 64);
        $_ENV[self::DEVICE_KEY_ENV] = $key;
        $this->portalDb->table('students')->insert(['in_campus' => false]);
        $this->portalDb->table('courses_assignment')->insert([
            'assigned_course_id' => 1,
            'course_id' => 1,
            'role' => 'student',
        ]);
        $this->portalDb->table('enrolments')->insert([
            'student_id' => 1,
            'program_id' => 1,
            'assigned_course_id' => 1,
            'school_year_id' => 1,
            'semester_id' => 1,
        ]);
        $this->portalDb->table('attendance_schedules')->insert([
            'faculty_id' => 1,
            'program_id' => 1,
            'course_id' => 1,
            'schedule_date' => date('Y-m-d', strtotime('-7 days')),
            'day_of_week' => (int) date('N'),
            'starts_at' => '00:00:00',
            'ends_at' => '23:59:59',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withHeaders(['X-Device-Key' => $key])
            ->withBodyFormat('json')
            ->post('/api/Scan_QR_Code', ['ID' => 1, 'Role' => 'Student']);

        $response->assertOK();
        $response->assertJSONFragment(['ok' => true, 'role' => 'student', 'id' => 1, 'status' => 'in']);
        $this->assertSame(1, (int) $this->portalDb->table('students')->where('student_id', 1)->get()->getRow()->in_campus);
        $this->assertSame(1, $this->portalDb->table('attendance_records')->where('direction', 'IN')->countAllResults());
        $this->assertSame(1, $this->portalDb->table('student_time_ins')->countAllResults());
    }

    private function createTable(string $name, array $fields, string $primaryKey): void
    {
        $forge = \Config\Database::forge('tests');
        $forge->addField($fields);
        $forge->addKey($primaryKey, true);
        $forge->createTable($name);
    }

    private function dropTables(): void
    {
        $forge = \Config\Database::forge('tests');
        foreach (['student_time_ins', 'attendance_records', 'attendance_schedules', 'enrolments', 'courses_assignment', 'students'] as $table) {
            $forge->dropTable($table, true);
        }
    }
}
