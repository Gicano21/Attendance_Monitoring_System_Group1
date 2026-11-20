<?php

namespace App\Controllers;

class InstructorAttendance extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('instructor', 'attendance');
    }
}
