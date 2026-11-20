<?php

namespace App\Controllers;

class InstructorLogin extends PortalController
{
    public function index()
    {
        return $this->loginForm('instructor');
    }

    public function authenticate()
    {
        return $this->authenticateAccount('instructor');
    }
}
