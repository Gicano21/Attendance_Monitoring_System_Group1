<?php

namespace App\Controllers;

class AdminAuthentication extends PortalController
{
    public function index()
    {
        return $this->changeAuthentication('admin');
    }

    public function update()
    {
        return $this->updateAuthentication('admin');
    }
}
