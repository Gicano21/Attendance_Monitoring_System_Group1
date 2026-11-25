<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class InstructorScheduleTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $portalDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->portalDb = \Config\Database::connect('tests');
        $this->dropTables();

        $this->createTable('faculty', [
            'faculty_id' => ['type' => 'INT', 'constraint' => 10],
            'instructor_id' => ['type' => 'INT', 'constraint' => 10],
            'assigned_program_id' => ['type' => 'INT', 'constraint' => 10],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'year_id' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'semester_id' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'section_id' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
        ], 'faculty_id');
        $this->createTable('programs_assignment', [
            'assigned_program_id' => ['type' => 'INT', 'constraint' => 10],
            'program_id' => ['type' => 'INT', 'constraint' => 5],
        ], 'assigned_program_id');
        $this->createTable('programs', [
            'program_id' => ['type' => 'INT', 'constraint' => 5],
            'program_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
        ], 'program_id');
        $this->createTable('courses_assignment', [
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'role' => ['type' => 'VARCHAR', 'constraint' => 12],
        ], 'assigned_course_id');
        $this->createTable('courses', [
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'course_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
        ], 'course_id');
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

        $this->portalDb->table('faculty')->insert([
            'faculty_id' => 1,
            'instructor_id' => 1,
            'assigned_program_id' => 1,
            'assigned_course_id' => 1,
        ]);
        $this->portalDb->table('programs_assignment')->insert(['assigned_program_id' => 1, 'program_id' => 1]);
        $this->portalDb->table('programs')->insert(['program_id' => 1, 'program_acronym' => 'TEST']);
        $this->portalDb->table('courses_assignment')->insert([
            'assigned_course_id' => 1,
            'course_id' => 1,
            'role' => 'instructor',
        ]);
        $this->portalDb->table('courses')->insert(['course_id' => 1, 'course_acronym' => 'TST101']);
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function testInstructorCanSaveRecurringWeekdayAndOverlapsOnlyConflictOnSameDay(): void
    {
        $this->postSchedule(1, '09:00', '10:00');
        $firstSchedule = $this->portalDb->table('attendance_schedules')->get()->getRowArray();

        $this->assertSame(1, (int) $firstSchedule['day_of_week']);
        $daysUntilMonday = (1 - (int) date('N') + 7) % 7;
        $this->assertSame(date('Y-m-d', strtotime('+' . $daysUntilMonday . ' days')), $firstSchedule['schedule_date']);

        $this->postSchedule(1, '09:30', '10:30');
        $this->assertSame(1, $this->portalDb->table('attendance_schedules')->countAllResults());

        $this->postSchedule(2, '09:30', '10:30');
        $this->assertSame(2, $this->portalDb->table('attendance_schedules')->countAllResults());

        $this->postSchedule(8, '11:00', '12:00');
        $this->assertSame(2, $this->portalDb->table('attendance_schedules')->countAllResults());
    }

    private function postSchedule(int $day, string $start, string $end): void
    {
        $payload = [
            'faculty_id' => 1,
            'program_id' => 1,
            'course_id' => 1,
            'day_of_week' => $day,
            'starts_at' => $start,
            'ends_at' => $end,
        ];
        $payload[csrf_token()] = csrf_hash();

        $this->withSession(['role' => 'instructor', 'user_id' => 1])
            ->post('/instructor/schedules/save', $payload);
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
        foreach (['attendance_schedules', 'courses', 'courses_assignment', 'programs', 'programs_assignment', 'faculty'] as $table) {
            $forge->dropTable($table, true);
        }
    }
}
