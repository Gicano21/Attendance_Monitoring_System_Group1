<?php

namespace App\Controllers;

class StudentAuthentication extends PortalController
{
    public function index()
    {
        return $this->changeAuthentication('student');
    }

    public function update()
    {
        return $this->updateAuthentication('student');
    }
}
