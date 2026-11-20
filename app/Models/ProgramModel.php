<?php

namespace App\Models;

use CodeIgniter\Model;

class ProgramModel extends Model
{
    protected $table = 'programs';
    protected $primaryKey = 'program_id';
    protected $allowedFields = ['program_acronym', 'program'];

    protected $validationRules = [
        'program_acronym' => 'required',
        'program' => 'required',
    ];
}
