<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('/myadmin', 'Pages::admin');
$routes->post('/myadmin/login', 'Pages::login');
$routes->get('/myadmin/dashboard', 'Pages::admin_dashboard');
$routes->get('/myadmin/faculty', 'Pages::view_faculty');
$routes->get('/myadmin/enrolled-students', 'Pages::view_enrolled_students');
$routes->get('/myadmin/change-authentication', 'Pages::change_authentication');
$routes->post('/myadmin/update-authentication', 'Pages::update_authentication');
$routes->get('/myadmin/instructor-attendance', 'Pages::view_instructor_attendance');
$routes->get('/myadmin/view_instructor_info/(:num)', 'Pages::view_instructor_info/$1');
$routes->get('/myadmin/delete_instructor/(:num)', 'Pages::delete_instructor/$1');
$routes->get('/myadmin/edit_instructor/(:num)', 'Pages::edit_instructor/$1');
$routes->post('/myadmin/update_instructor/(:num)', 'Pages::update_instructor/$1');
$routes->get('/myadmin/add_instructor', 'Pages::add_instructor');
$routes->post('/myadmin/save_instructor', 'Pages::save_instructor');

$routes->get('/myadmin/view-courses', 'Pages::view_courses');
$routes->get('/myadmin/edit-courses', 'Pages::edit_courses');
$routes->post('/myadmin/update-courses', 'Pages::update_courses');

$routes->get('/myadmin/view-programs', 'Pages::view_programs');
$routes->get('/myadmin/edit-programs', 'Pages::edit_programs');
$routes->post('/myadmin/update-programs', 'Pages::update_programs');
