<?php

namespace App\Models;

use CodeIgniter\Model;

class InstructorAssignmentModel extends Model
{
    protected $table = 'faculty';
    protected $primaryKey = 'faculty_id';
    protected $allowedFields = [
        'instructor_id', 'instructor_level_id', 'assigned_program_id', 'assigned_course_id',
        'year_id', 'semester_id', 'section_id',
    ];

    protected $validationRules = [
        'instructor_id' => 'required|is_natural_no_zero',
        'instructor_level_id' => 'required|is_natural_no_zero',
        'program_id' => 'required|is_natural_no_zero',
        'course_id' => 'required|is_natural_no_zero',
        'year_id' => 'required|is_natural_no_zero',
        'semester_id' => 'required|is_natural_no_zero',
        'section_id' => 'required',
    ];
}
