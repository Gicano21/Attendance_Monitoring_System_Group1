<?php

namespace App\Controllers;

class AdminCourses extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('admin', 'courses');
    }

    public function edit(int $id)
    {
        return $this->editResource('courses', $id);
    }

    public function save()
    {
        return $this->saveResource('courses');
    }

    public function delete(int $id)
    {
        return $this->deleteResource('courses', $id);
    }
}
