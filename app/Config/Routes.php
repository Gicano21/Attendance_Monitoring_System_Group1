<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('student', 'Portal::loginForm/student');
$routes->post('student/login', 'Portal::authenticate/student');
$routes->get('instructor', 'Portal::loginForm/instructor');
$routes->post('instructor/login', 'Portal::authenticate/instructor');
$routes->get('myadmin', 'Portal::loginForm/admin');
$routes->get('myadmin/', 'Portal::loginForm/admin');
$routes->post('myadmin/login', 'Portal::authenticate/admin');
$routes->get('myadmin/change-authentication', 'Portal::changeAuthentication/admin');
$routes->post('myadmin/change-authentication', 'Portal::updateAuthentication/admin');
$routes->get('logout', 'Portal::logout');

$routes->get('myadmin/(:segment)', 'Portal::dashboard/$1');
$routes->get('myadmin/(:segment)/edit/(:num)', 'Portal::editResource/$1/$2');
$routes->post('myadmin/assignments/save', 'Portal::saveFacultyAssignment');
$routes->post('myadmin/assignments/delete/(:num)', 'Portal::deleteFacultyAssignment/$1');
$routes->post('myadmin/(:segment)/save', 'Portal::saveResource/$1');
$routes->post('myadmin/(:segment)/delete/(:num)', 'Portal::deleteResource/$1/$2');

$routes->get('instructor/attendance', 'Portal::dashboard/attendance');
$routes->get('instructor/students', 'Portal::dashboard/students');
$routes->get('instructor/students/edit/(:num)', 'Portal::editResource/students/$1');
$routes->post('instructor/students/save', 'Portal::saveResource/students');
$routes->post('instructor/students/delete/(:num)', 'Portal::deleteResource/students/$1');
$routes->get('instructor/schedules', 'Portal::schedules');
$routes->post('instructor/schedules/save', 'Portal::saveSchedule');
$routes->get('student/attendance', 'Portal::dashboard/attendance');

$routes->get('student/change-authentication', 'Portal::changeAuthentication/student');
$routes->post('student/change-authentication', 'Portal::updateAuthentication/student');
$routes->get('instructor/change-authentication', 'Portal::changeAuthentication/instructor');
$routes->post('instructor/change-authentication', 'Portal::updateAuthentication/instructor');

$routes->post('api/Scan_QR_Code', 'Portal::scanQrCode');
