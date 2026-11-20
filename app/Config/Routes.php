<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('student', 'StudentLogin::index');
$routes->post('student/login', 'StudentLogin::authenticate');
$routes->get('instructor', 'InstructorLogin::index');
$routes->post('instructor/login', 'InstructorLogin::authenticate');
$routes->get('myadmin', 'AdminLogin::index');
$routes->get('myadmin/', 'AdminLogin::index');
$routes->post('myadmin/login', 'AdminLogin::authenticate');
$routes->get('logout', 'Logout::index');

$routes->get('myadmin/attendance', 'AdminAttendance::index');
$routes->get('myadmin/instructors', 'AdminInstructors::index');
$routes->get('myadmin/instructors/edit/(:num)', 'AdminInstructors::edit/$1');
$routes->post('myadmin/instructors/save', 'AdminInstructors::save');
$routes->post('myadmin/instructors/delete/(:num)', 'AdminInstructors::delete/$1');
$routes->post('myadmin/assignments/save', 'AdminInstructors::saveAssignment');
$routes->post('myadmin/assignments/delete/(:num)', 'AdminInstructors::deleteAssignment/$1');
$routes->get('myadmin/students', 'AdminStudents::index');
$routes->get('myadmin/students/edit/(:num)', 'AdminStudents::edit/$1');
$routes->post('myadmin/students/save', 'AdminStudents::save');
$routes->post('myadmin/students/delete/(:num)', 'AdminStudents::delete/$1');
$routes->get('myadmin/programs', 'AdminPrograms::index');
$routes->get('myadmin/programs/edit/(:num)', 'AdminPrograms::edit/$1');
$routes->post('myadmin/programs/save', 'AdminPrograms::save');
$routes->post('myadmin/programs/delete/(:num)', 'AdminPrograms::delete/$1');
$routes->get('myadmin/courses', 'AdminCourses::index');
$routes->get('myadmin/courses/edit/(:num)', 'AdminCourses::edit/$1');
$routes->post('myadmin/courses/save', 'AdminCourses::save');
$routes->post('myadmin/courses/delete/(:num)', 'AdminCourses::delete/$1');
$routes->get('myadmin/change-authentication', 'AdminAuthentication::index');
$routes->post('myadmin/change-authentication', 'AdminAuthentication::update');

$routes->get('instructor/attendance', 'InstructorAttendance::index');
$routes->get('instructor/students', 'InstructorStudents::index');
$routes->get('instructor/students/edit/(:num)', 'InstructorStudents::edit/$1');
$routes->post('instructor/students/save', 'InstructorStudents::save');
$routes->post('instructor/students/delete/(:num)', 'InstructorStudents::delete/$1');
$routes->get('instructor/schedules', 'InstructorSchedules::index');
$routes->post('instructor/schedules/save', 'InstructorSchedules::save');
$routes->get('instructor/change-authentication', 'InstructorAuthentication::index');
$routes->post('instructor/change-authentication', 'InstructorAuthentication::update');

$routes->get('student/attendance', 'StudentAttendance::index');
$routes->get('student/change-authentication', 'StudentAuthentication::index');
$routes->post('student/change-authentication', 'StudentAuthentication::update');
