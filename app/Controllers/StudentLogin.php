<?php

namespace App\Controllers;

class StudentLogin extends PortalController
{
    public function index()
    {
        return $this->loginForm('student');
    }

    public function authenticate()
    {
        return $this->authenticateAccount('student');
    }
}
