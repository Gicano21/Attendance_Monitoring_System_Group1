<?php

namespace App\Controllers;

class InstructorSchedules extends PortalController
{
    public function index()
    {
        return $this->schedules();
    }

    public function save()
    {
        return $this->saveSchedule();
    }
}
