<?php

namespace App\Models;

use CodeIgniter\Model;

class EnrolmentModel extends Model
{
    protected $table = 'enrolments';
    protected $primaryKey = 'enrolment_id';
    protected $allowedFields = [
        'student_id', 'program_id', 'assigned_course_id', 'year_id',
        'school_year_id', 'semester_id', 'section_id',
    ];

    protected $validationRules = [
        'program_id' => 'required|is_natural_no_zero',
        'assigned_course_id' => 'required|is_natural_no_zero',
        'year_id' => 'required|is_natural_no_zero',
        'school_year_id' => 'required|is_natural_no_zero',
        'semester_id' => 'required|is_natural_no_zero',
        'section_id' => 'required',
    ];
}
