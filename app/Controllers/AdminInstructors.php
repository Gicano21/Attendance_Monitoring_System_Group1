<?php

namespace App\Controllers;

class AdminInstructors extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('admin', 'instructors');
    }

    public function edit(int $id)
    {
        return $this->editResource('instructors', $id);
    }

    public function save()
    {
        return $this->saveResource('instructors');
    }

    public function delete(int $id)
    {
        return $this->deleteResource('instructors', $id);
    }

    public function saveAssignment()
    {
        return $this->saveFacultyAssignment();
    }

    public function deleteAssignment(int $facultyId)
    {
        return $this->deleteFacultyAssignment($facultyId);
    }
}
