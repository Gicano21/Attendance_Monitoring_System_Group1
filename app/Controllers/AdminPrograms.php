<?php

namespace App\Controllers;

class AdminPrograms extends PortalController
{
    public function index()
    {
        return $this->dashboardForRole('admin', 'programs');
    }

    public function edit(int $id)
    {
        return $this->editResource('programs', $id);
    }

    public function save()
    {
        return $this->saveResource('programs');
    }

    public function delete(int $id)
    {
        return $this->deleteResource('programs', $id);
    }

    public function restore(int $id)
    {
        return $this->restoreResource('programs', $id);
    }
}
