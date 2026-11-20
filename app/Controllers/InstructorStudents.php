<?php

namespace App\Controllers;

class InstructorStudents extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('instructor', 'students');
    }

    public function edit(int $id)
    {
        return $this->editResource('students', $id);
    }

    public function save()
    {
        return $this->saveResource('students');
    }

    public function delete(int $id)
    {
        return $this->deleteResource('students', $id);
    }
}
