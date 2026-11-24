<?php

namespace App\Models;

use CodeIgniter\Model;

class InstructorModel extends Model
{
    protected $table = 'instructors';
    protected $primaryKey = 'instructor_id';
    protected $allowedFields = [
        'last_name', 'middle_name', 'first_name', 'extension_name', 'gender', 'birth_date',
        'password', 'in_campus',
    ];

    protected $validationRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'gender' => 'required|in_list[Male,Female]',
        'birth_date' => 'required|valid_date[Y-m-d]',
    ];
}
