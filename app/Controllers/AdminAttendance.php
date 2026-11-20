<?php

namespace App\Controllers;

class AdminAttendance extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('admin', 'attendance');
    }
}
