<?php

use App\Controllers\Portal;
use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Portal.php';

/**
 * @internal
 */
final class PortalDashboardViewContractTest extends CIUnitTestCase
{
    public function testDashboardViewRendersEveryPageWithTheControllerDataContract(): void
    {
        $controller = (new ReflectionClass(Portal::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(Portal::class, 'dashboardViewData');
        $method->setAccessible(true);

        $cases = [
            ['admin', 'attendance'],
            ['admin', 'students'],
            ['admin', 'instructors'],
            ['admin', 'programs'],
            ['admin', 'courses'],
            ['instructor', 'attendance'],
            ['instructor', 'students'],
            ['instructor', 'schedules'],
            ['student', 'attendance'],
            ['admin', 'authentication'],
            ['instructor', 'authentication'],
            ['student', 'authentication'],
            ['admin', 'edit'],
            ['instructor', 'edit'],
        ];

        foreach ($cases as [$role, $page]) {
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

            $html = view('pages/' . $role . '/dashboard', $data);
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
