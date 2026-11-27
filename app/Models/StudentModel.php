<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'student_id';
    protected $allowedFields = [
        'last_name', 'middle_name', 'first_name', 'extension_name', 'gender', 'birth_date',
        'password', 'in_campus', 'deleted_at',
    ];
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'gender' => 'required|in_list[Male,Female]',
        'birth_date' => 'required|valid_date[Y-m-d]',
    ];
}
