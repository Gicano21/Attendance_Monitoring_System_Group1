<?php

namespace App\Controllers;

class AdminStudents extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('admin', 'students');
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

    public function restore(int $id)
    {
        return $this->restoreResource('students', $id);
    }
}
