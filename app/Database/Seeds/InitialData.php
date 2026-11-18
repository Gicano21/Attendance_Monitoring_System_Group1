<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialData extends Seeder
{
    public function run()
    {
        $this->SeedData('admin_accounts', [
            'username' => 'Gilberto Tanio Sr.',
            'password' => 'Binitawan8080',
            'email'    => 'gilberto8080@gmail.com',
        ]);

        $this->SeedData('instructors', [
            [
                'last_name' => 'Nolasco',
                'middle_name' => 'O.',
                'first_name' => 'Gian Carlo',
                'extension_name' => '',
                'gender' => 'Male',
                'birth_date' => '2000-01-01',
                'password' => 'qwerty',
                'in_campus' => false,
            ],
            [
                'last_name' => 'Dela Cruz',
                'middle_name' => ' ',
                'first_name' => 'Juan',
                'extension_name' => '',
                'gender' => 'Male',
                'birth_date' => '1995-05-15',
                'password' => 'password123',
                'in_campus' => false,
            ],
        ]);

        $this->SeedData('courses', [
            [
                'course_acronym' => 'IT',
                'course' => 'Information Technology',
            ],
            [
                'course_acronym' => 'OOP',
                'course' => 'Object Oriented Programing',
            ],
        ]);

        $this->SeedData('programs', [
            [
                'program_acronym' => 'BSIT',
                'program' => 'Bachelor of Science in Information Technology',
            ],
            [
                'program_acronym' => 'BSCS',
                'program' => 'Bachelor of Science in Computer Science',
            ],
        ]);

        $this->SeedData('courses_assignment', [
            [
                'assigned_course_id' => 1,
                'course_id' => 1,
                'role' => 'instructor',
            ],
            [
                'assigned_course_id' => 2,
                'course_id' => 2,
                'role' => 'instructor',
            ],
            [
                'assigned_course_id' => 2,
                'course_id' => 1,
                'role' => 'instructor',
            ],
            [
                'assigned_course_id' => 3,
                'course_id' => 1,
                'role' => 'student',
            ],
            [
                'assigned_course_id' => 4,
                'course_id' => 2,
                'role' => 'student',
            ],
            [
                'assigned_course_id' => 4,
                'course_id' => 1,
                'role' => 'student',
            ],
        ]);

        $this->SeedData('programs_assignment', [
            [
                'assigned_program_id' => 1,
                'program_id' => 1,
            ],
            [
                'assigned_program_id' => 2,
                'program_id' => 2,
            ],
            [
                'assigned_program_id' => 2,
                'program_id' => 1,
            ],
        ]);

        $this->SeedData('instructor_levels', [
            ['instructor_level_id' => 1, 'instructor_level' => 'Instructor I'],
            ['instructor_level_id' => 2, 'instructor_level' => 'Instructor II'],
            ['instructor_level_id' => 3, 'instructor_level' => 'Instructor III'],
        ]);

        $this->SeedData('faculty', [
            [
                'instructor_id' => 1,
                'instructor_level_id' => 3,
                'assigned_program_id' => 1,
                'assigned_course_id' => 1,
            ],
            [
                'instructor_id' => 2,
                'instructor_level_id' => 1,
                'assigned_program_id' => 1,
                'assigned_course_id' => 2,
            ],
        ]);

        $this->SeedData('year', [
            ['year_id' => 1, 'year' => '1st Year'],
            ['year_id' => 2, 'year' => '2nd Year'],
            ['year_id' => 3, 'year' => '3rd Year'],
            ['year_id' => 4, 'year' => '4th Year'],
        ]);

        $this->SeedData('school_year', [
            ['year' => '2023-2024'],
            ['year' => '2024-2025'],
            ['year' => '2025-2026'],
        ]);

        $this->SeedData('semesters', [
            ['semester_id' => 1, 'semester' => '1st Semester'],
            ['semester_id' => 2, 'semester' => '2nd Semester'],
        ]);

        $this->SeedData('sections', [
            ['sections_id' => 'A', 'section' => 'Section A'],
            ['sections_id' => 'B', 'section' => 'Section B'],
            ['sections_id' => 'C', 'section' => 'Section C'],
        ]);

        $this->SeedData('students', [
            [
                'last_name' => 'Smith',
                'middle_name' => 'A.',
                'first_name' => 'John',
                'extension_name' => '',
                'gender' => 'Male',
                'birth_date' => '2000-01-01',
                'password' => 'password123',
                'in_campus' => true,
            ],
            [
                'last_name' => 'Johnson',
                'middle_name' => 'B.',
                'first_name' => 'Emily',
                'extension_name' => '',
                'gender' => 'Female',
                'birth_date' => '2001-02-15',
                'password' => 'password456',
                'in_campus' => false,
            ],
        ]);

        $this->SeedData('enrolments', [
            [
                'student_id' => 1,
                'program_id' => 1,
                'year_id' => 1,
                'assigned_course_id' => 3,
                'school_year_id' => 1,
                'semester_id' => 1,
                'section_id' => 'A',
            ],
            [
                'student_id' => 2,
                'program_id' => 2,
                'year_id' => 2,
                'assigned_course_id' => 4,
                'school_year_id' => 2,
                'semester_id' => 1,
                'section_id' => 'B',
            ],
        ]);
    }

    public function SeedData($table, $data)
    {
        echo "Seeding data for table: $table" . PHP_EOL;
        return $this->db->table($table)->insertBatch($data);
    }
}
