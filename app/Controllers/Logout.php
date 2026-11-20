<?php

namespace App\Controllers;

class Logout extends PortalController
{
    public function index()
    {
        return $this->logout();
    }
}
