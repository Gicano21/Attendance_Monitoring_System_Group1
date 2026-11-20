<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceScheduleModel extends Model
{
    protected $table = 'attendance_schedules';
    protected $primaryKey = 'schedule_id';
    protected $allowedFields = [
        'faculty_id', 'program_id', 'course_id', 'schedule_date', 'day_of_week',
        'starts_at', 'ends_at', 'created_at',
    ];

    protected $validationRules = [
        'faculty_id' => 'required|is_natural_no_zero',
        'program_id' => 'required|is_natural_no_zero',
        'course_id' => 'required|is_natural_no_zero',
        'day_of_week' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[7]',
        'starts_at' => 'required',
        'ends_at' => 'required',
    ];
}
