<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PortalDashboardTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected BaseConnection $portalDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->portalDb = \Config\Database::connect('tests');
        $this->dropTables();
        $this->createTable('students', [
            'student_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'middle_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'extension_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'gender' => ['type' => 'VARCHAR', 'constraint' => 6],
            'birth_date' => ['type' => 'DATE'],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'in_campus' => ['type' => 'BOOLEAN'],
        ], 'student_id');
        $this->createTable('programs', [
            'program_id' => ['type' => 'INT', 'constraint' => 5, 'auto_increment' => true],
            'program_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
            'program' => ['type' => 'VARCHAR', 'constraint' => 100],
        ], 'program_id');
        $this->createTable('courses', [
            'course_id' => ['type' => 'INT', 'constraint' => 5, 'auto_increment' => true],
            'course_acronym' => ['type' => 'VARCHAR', 'constraint' => 8],
            'course' => ['type' => 'VARCHAR', 'constraint' => 100],
        ], 'course_id');
        $this->createTable('courses_assignment', [
            'course_assignment_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'assigned_course_id' => ['type' => 'INT', 'constraint' => 10],
            'course_id' => ['type' => 'INT', 'constraint' => 5],
            'role' => ['type' => 'VARCHAR', 'constraint' => 12],
        ], 'course_assignment_id');
        $this->createTable('year', [
            'year_id' => ['type' => 'INT', 'constraint' => 1, 'auto_increment' => true],
            'year' => ['type' => 'VARCHAR', 'constraint' => 30],
        ], 'year_id');
        $this->createTable('school_year', [
            'school_year_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'year' => ['type' => 'VARCHAR', 'constraint' => 100],
        ], 'school_year_id');
        $this->createTable('semesters', [
            'semester_id' => ['type' => 'INT', 'constraint' => 1, 'auto_increment' => true],
            'semester' => ['type' => 'VARCHAR', 'constraint' => 30],
        ], 'semester_id');
        $this->createTable('sections', [
            'sections_id' => ['type' => 'VARCHAR', 'constraint' => 8],
            'section' => ['type' => 'VARCHAR', 'constraint' => 30],
        ], 'sections_id');
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function testAdminEnrolledStudentsPageListsCoursesWithoutExistingStudentAssignments(): void
    {
        $this->portalDb->table('courses')->insert([
            'course_id' => 3,
            'course_acronym' => 'CS101',
            'course' => 'Programming',
        ]);

        $result = $this->withSession([
            'role' => 'admin',
            'user_id' => 1,
            'display_name' => 'Administrator',
        ])->get('/myadmin/students');

        $result->assertOK();
        $result->assertSee('Current Student records');
        $result->assertSee('Last name');
        $result->assertSee('No records yet.');
        $result->assertSee('type="checkbox" name="course_ids[]"', false);
        $result->assertSee('CS101');
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
        foreach (['sections', 'semesters', 'school_year', 'year', 'courses_assignment', 'courses', 'programs', 'students'] as $table) {
            $forge->dropTable($table, true);
        }
    }
}
