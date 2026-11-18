<?php

use App\Commands\AutoCloseAttendance;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Commands' . DIRECTORY_SEPARATOR . 'AutoCloseAttendance.php';

/**
 * @internal
 */
final class AutoCloseAttendanceCommandTest extends CIUnitTestCase
{
    private BaseConnection $portalDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->portalDb = \Config\Database::connect('tests');
        $this->dropTables();

        $this->createTable('students', [
            'student_id' => ['type' => 'INT', 'constraint' => 10],
            'in_campus' => ['type' => 'BOOLEAN'],
        ], 'student_id');
        $this->createTable('instructors', [
            'instructor_id' => ['type' => 'INT', 'constraint' => 10],
            'in_campus' => ['type' => 'BOOLEAN'],
        ], 'instructor_id');
        $this->createTable('faculty', [
            'faculty_id' => ['type' => 'INT', 'constraint' => 10],
            'instructor_id' => ['type' => 'INT', 'constraint' => 10],
        ], 'faculty_id');
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
            'enrolment_id' => ['type' => 'INT', 'constraint' => 10],
            'student_id' => ['type' => 'INT', 'constraint' => 10],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
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
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function testCommandClosesAttendanceForARecurringWeekdayAfterItsEndTime(): void
    {
        $this->portalDb->table('students')->insert(['student_id' => 1, 'in_campus' => true]);
        $this->portalDb->table('instructors')->insert(['instructor_id' => 1, 'in_campus' => false]);
        $this->portalDb->table('faculty')->insert(['faculty_id' => 1, 'instructor_id' => 1]);
        $this->portalDb->table('attendance_schedules')->insert([
            'schedule_id' => 1,
            'faculty_id' => 1,
            'program_id' => 1,
            'course_id' => 1,
            'schedule_date' => date('Y-m-d', strtotime('-7 days')),
            'day_of_week' => (int) date('N'),
            'starts_at' => '00:00:00',
            'ends_at' => '00:00:00',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->portalDb->table('enrolments')->insert([
            'enrolment_id' => 1,
            'student_id' => 1,
            'program_id' => 1,
            'assigned_course_id' => 1,
        ]);
        $this->portalDb->table('courses_assignment')->insert([
            'assigned_course_id' => 1,
            'course_id' => 1,
            'role' => 'student',
        ]);
        $this->portalDb->table('attendance_records')->insert([
            'participant_role' => 'student',
            'participant_id' => 1,
            'schedule_id' => 1,
            'direction' => 'IN',
            'scanned_at' => date('Y-m-d H:i:s'),
        ]);

        (new ReflectionClass(AutoCloseAttendance::class))
            ->newInstanceWithoutConstructor()
            ->run([]);

        $student = $this->portalDb->table('students')->where('student_id', 1)->get()->getRowArray();
        $this->assertSame(0, (int) $student['in_campus']);
        $this->assertSame(1, $this->portalDb->table('attendance_records')->where('direction', 'OUT')->countAllResults());
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
        foreach (['attendance_records', 'courses_assignment', 'enrolments', 'attendance_schedules', 'faculty', 'instructors', 'students'] as $table) {
            $forge->dropTable($table, true);
        }
    }
}
