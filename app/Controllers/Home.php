<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $user = auth()->user();
        if ($user !== null) {
            $role = null;
            foreach (['admin', 'instructor', 'student'] as $candidate) {
                if ($user->inGroup($candidate)) {
                    $role = $candidate;
                    break;
                }
            }

            $destination = match ($role) {
                'admin' => 'myadmin/attendance',
                'instructor' => 'instructor/attendance',
                'student' => 'student/attendance',
                default => null,
            };
            if ($destination !== null) {
                return redirect()->to($destination)
                    ->with('error', 'You are already signed in. Log out first to open a login option.');
            }
        }

        return view('pages/home');
    }

    public function test(){
        echo "Hello World";
    }
}
