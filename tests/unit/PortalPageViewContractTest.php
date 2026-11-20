<?php

use App\Controllers\PortalController;
use App\Controllers\StudentAttendance;
use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'PortalController.php';

/**
 * @internal
 */
final class PortalPageViewContractTest extends CIUnitTestCase
{
    public function testEachDestinationViewRendersWithTheControllerDataContract(): void
    {
        $controller = (new ReflectionClass(StudentAttendance::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(PortalController::class, 'dashboardViewData');
        $method->setAccessible(true);

        $cases = [
            ['admin', 'attendance', 'pages/admin/attendance'],
            ['admin', 'students', 'pages/admin/students'],
            ['admin', 'instructors', 'pages/admin/instructors'],
            ['admin', 'programs', 'pages/admin/programs'],
            ['admin', 'courses', 'pages/admin/courses'],
            ['instructor', 'attendance', 'pages/instructor/attendance'],
            ['instructor', 'students', 'pages/instructor/students'],
            ['instructor', 'schedules', 'pages/instructor/schedules'],
            ['student', 'attendance', 'pages/student/attendance'],
            ['admin', 'authentication', 'pages/admin/authentication'],
            ['instructor', 'authentication', 'pages/instructor/authentication'],
            ['student', 'authentication', 'pages/student/authentication'],
            ['admin', 'edit', 'pages/admin/students'],
            ['instructor', 'edit', 'pages/instructor/students'],
        ];

        foreach ($cases as [$role, $page, $view]) {
            $data = $method->invoke($controller, $role, $page, ucfirst($page));
            session()->set(['role' => $role, 'user_id' => 1, 'display_name' => 'Test User']);

            if ($page === 'students' || $page === 'programs' || $page === 'courses' || $page === 'instructors') {
                $resource = $page;
                $data['resource'] = $resource;
                $data['columns'] = $resource === 'students' || $resource === 'instructors'
                    ? ['first_name', 'last_name']
                    : [$resource === 'programs' ? 'program' : 'course'];
                $data['labels'] = array_combine($data['columns'], $data['columns']);
                $data['lookup'] = [
                    'programs' => [],
                    'courses' => [],
                    'years' => [],
                    'school_years' => [],
                    'semesters' => [],
                    'sections' => [],
                    'instructors' => [],
                    'levels' => [],
                ];
            }

            if ($page === 'edit') {
                $data['resource'] = 'students';
                $data['columns'] = ['first_name', 'last_name'];
                $data['labels'] = ['first_name' => 'First name', 'last_name' => 'Last name'];
                $data['record'] = ['student_id' => 1, 'first_name' => 'Test', 'last_name' => 'User'];
            }

            if ($role === 'instructor' && $page === 'schedules') {
                $data['assignments'] = [[
                    'faculty_id' => 1,
                    'program_id' => 1,
                    'course_id' => 1,
                    'program_acronym' => 'TEST',
                    'course_acronym' => 'TST101',
                ]];
            }

            $html = view($view, $data);
            $this->assertIsString($html);
            $this->assertStringContainsString(ucfirst($page), $html);
            if ($role === 'instructor' && $page === 'schedules') {
                $this->assertStringContainsString('Class Day', $html);
                foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                    $this->assertStringContainsString('>' . $day . '</option>', $html);
                }
                $this->assertStringNotContainsString('Class date', $html);
            }
        }
    }
}
