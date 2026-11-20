<?php

namespace App\Controllers;

class AdminLogin extends PortalController
{
    public function index()
    {
        return $this->loginForm('admin');
    }

    public function authenticate()
    {
        return $this->authenticateAccount('admin');
    }
}
