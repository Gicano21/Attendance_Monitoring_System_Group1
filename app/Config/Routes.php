<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index', ['as' => 'login']);
$routes->get('/test', 'Home::test');

$routes->get('student', 'StudentLogin::index', ['filter' => 'auth-rates']);
$routes->post('student/login', 'StudentLogin::authenticate', ['filter' => 'auth-rates']);
$routes->get('instructor', 'InstructorLogin::index', ['filter' => 'auth-rates']);
$routes->post('instructor/login', 'InstructorLogin::authenticate', ['filter' => 'auth-rates']);
$routes->get('myadmin', 'AdminLogin::index', ['filter' => 'auth-rates']);
$routes->get('myadmin/', 'AdminLogin::index', ['filter' => 'auth-rates']);
$routes->post('myadmin/login', 'AdminLogin::authenticate', ['filter' => 'auth-rates']);
$routes->get('logout', 'Logout::index');

$routes->group('myadmin', ['filter' => 'group:admin'], static function (RouteCollection $routes): void {
    $routes->get('attendance', 'AdminAttendance::index');
    $routes->get('instructors', 'AdminInstructors::index');
    $routes->get('instructors/edit/(:num)', 'AdminInstructors::edit/$1');
    $routes->post('instructors/save', 'AdminInstructors::save');
    $routes->post('instructors/delete/(:num)', 'AdminInstructors::delete/$1');
    $routes->post('instructors/restore/(:num)', 'AdminInstructors::restore/$1');
    $routes->post('assignments/save', 'AdminInstructors::saveAssignment');
    $routes->post('assignments/delete/(:num)', 'AdminInstructors::deleteAssignment/$1');
    $routes->get('students', 'AdminStudents::index');
    $routes->get('students/edit/(:num)', 'AdminStudents::edit/$1');
    $routes->post('students/save', 'AdminStudents::save');
    $routes->post('students/delete/(:num)', 'AdminStudents::delete/$1');
    $routes->post('students/restore/(:num)', 'AdminStudents::restore/$1');
    $routes->get('programs', 'AdminPrograms::index');
    $routes->get('programs/edit/(:num)', 'AdminPrograms::edit/$1');
    $routes->post('programs/save', 'AdminPrograms::save');
    $routes->post('programs/delete/(:num)', 'AdminPrograms::delete/$1');
    $routes->post('programs/restore/(:num)', 'AdminPrograms::restore/$1');
    $routes->get('courses', 'AdminCourses::index');
    $routes->get('courses/edit/(:num)', 'AdminCourses::edit/$1');
    $routes->post('courses/save', 'AdminCourses::save');
    $routes->post('courses/delete/(:num)', 'AdminCourses::delete/$1');
    $routes->post('courses/restore/(:num)', 'AdminCourses::restore/$1');
    $routes->get('change-authentication', 'AdminAuthentication::index');
    $routes->post('change-authentication', 'AdminAuthentication::update');
});

$routes->group('instructor', ['filter' => 'group:instructor'], static function (RouteCollection $routes): void {
    $routes->get('attendance', 'InstructorAttendance::index');
    $routes->get('students', 'InstructorStudents::index');
    $routes->get('students/edit/(:num)', 'InstructorStudents::edit/$1');
    $routes->post('students/save', 'InstructorStudents::save');
    $routes->post('students/delete/(:num)', 'InstructorStudents::delete/$1');
    $routes->get('schedules', 'InstructorSchedules::index');
    $routes->post('schedules/save', 'InstructorSchedules::save');
    $routes->get('change-authentication', 'InstructorAuthentication::index');
    $routes->post('change-authentication', 'InstructorAuthentication::update');
});

$routes->group('student', ['filter' => 'group:student'], static function (RouteCollection $routes): void {
    $routes->get('attendance', 'StudentAttendance::index');
    $routes->get('change-authentication', 'StudentAuthentication::index');
    $routes->post('change-authentication', 'StudentAuthentication::update');
});
