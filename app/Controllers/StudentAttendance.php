<?php

namespace App\Controllers;

class StudentAttendance extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('student', 'attendance');
    }
}
