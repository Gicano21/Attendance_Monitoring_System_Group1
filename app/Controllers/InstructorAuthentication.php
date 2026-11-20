<?php

namespace App\Controllers;

class InstructorAuthentication extends PortalController
{
    public function index()
    {
        return $this->changeAuthentication('instructor');
    }

    public function update()
    {
        return $this->updateAuthentication('instructor');
    }
}
