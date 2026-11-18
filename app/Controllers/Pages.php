<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Pages extends BaseController
{
    public function admin()
    {
        return view('pages/admin/admin');
    }

    public function admin_dashboard()
    {
        return view('pages/admin/admin_dashboard');
    }

    public function view_enrolled_students()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('students')
            ->select([
                'students.student_id',
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.extension_name',
                'students.gender',
                'students.birth_date',
                'students.in_campus',
                'enrolments.enrolment_id',
                'courses.course_id',
                'courses.course_acronym',
                'courses.course',
                'programs.program_acronym',
                'programs.program',
                'academic_year.year AS academic_year',
                'school_year.year AS school_year',
                'semesters.semester',
                'sections.section AS section',
            ])
            ->join('enrolments', 'enrolments.student_id = students.student_id', 'left')
            ->join('programs', 'programs.program_id = enrolments.program_id', 'left')
            ->join('year AS academic_year', 'academic_year.year_id = enrolments.year_id', 'left')
            ->join('courses_assignment', "courses_assignment.assigned_course_id = enrolments.assigned_course_id AND courses_assignment.role = 'student'", 'left')
            ->join('courses', 'courses.course_id = courses_assignment.course_id', 'left')
            ->join('school_year', 'school_year.school_year_id = enrolments.school_year_id', 'left')
            ->join('semesters', 'semesters.semester_id = enrolments.semester_id', 'left')
            ->join('sections', 'sections.sections_id = enrolments.section_id', 'left')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->orderBy('enrolments.enrolment_id')
            ->get()
            ->getResultArray();

        $students = [];
        foreach ($rows as $row) {
            $studentId = (int) $row['student_id'];
            if (!isset($students[$studentId])) {
                $students[$studentId] = [
                    'student_id' => $studentId,
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'last_name' => $row['last_name'],
                    'extension_name' => $row['extension_name'],
                    'gender' => $row['gender'],
                    'birth_date' => $row['birth_date'],
                    'in_campus' => $row['in_campus'],
                    'enrolments' => [],
                ];
            }

            if ($row['enrolment_id'] !== null) {
                $key = (int) $row['enrolment_id'];
                if (!isset($students[$studentId]['enrolments'][$key])) {
                    $students[$studentId]['enrolments'][$key] = [
                        'courses' => [],
                        'program_acronym' => $row['program_acronym'],
                        'program' => $row['program'],
                        'academic_year' => $row['academic_year'],
                        'school_year' => $row['school_year'],
                        'semester' => $row['semester'],
                        'section' => $row['section'],
                    ];
                }
                if ($row['course_id'] !== null) {
                    $students[$studentId]['enrolments'][$key]['courses'][(int) $row['course_id']] =
                        $row['course_acronym'] . ' - ' . $row['course'];
                }
            }
        }
        foreach ($students as &$student) {
            foreach ($student['enrolments'] as &$enrollment) {
                $enrollment['courses'] = array_values($enrollment['courses']);
            }
            unset($enrollment);
            $student['enrolments'] = array_values($student['enrolments']);
        }
        unset($student);

        return view('pages/admin/admin_view_enrolled_students', [
            'students' => array_values($students),
        ]);
    }

    public function add_student()
    {
        $db = \Config\Database::connect();
        return view('pages/admin/add_student', $this->studentFormOptions($db));
    }

    public function save_student()
    {
        $db = \Config\Database::connect();
        $studentData = $this->postedStudentDetails(true);
        if ($studentData === null) {
            return redirect()->back()->withInput()->with('error', 'Enter valid student details.');
        }

        $enrollmentData = $this->validatedEnrollmentData($db, [
            'program_id' => $this->request->getPost('program_id'),
            'year_id' => $this->request->getPost('year_id'),
            'school_year_id' => $this->request->getPost('school_year_id'),
            'semester_id' => $this->request->getPost('semester_id'),
            'section_id' => $this->request->getPost('section_id'),
            'assigned_course_id' => $this->request->getPost('assigned_course_id'),
        ]);
        if ($enrollmentData === null) {
            return redirect()->back()->withInput()->with('error', 'Select valid program, year, course(s), school year, semester, and section.');
        }

        $db->transStart();
        $db->table('students')->insert($studentData);
        $studentId = (int) $db->insertID();
        $db->table('enrolments')->insert([
            'student_id' => $studentId,
            'program_id' => $enrollmentData['program_id'],
            'year_id' => $enrollmentData['year_id'],
            'assigned_course_id' => $enrollmentData['assigned_course_id'],
            'school_year_id' => $enrollmentData['school_year_id'],
            'semester_id' => $enrollmentData['semester_id'],
            'section_id' => $enrollmentData['section_id'],
        ]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Unable to save the student and enrollment.');
        }

        return redirect()->to(base_url('/myadmin/enrolled-students'))->with('success', 'Student added successfully.');
    }

    public function view_student_info($student_id)
    {
        $db = \Config\Database::connect();
        $student = $db->table('students')
            ->where('student_id', $student_id)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->to(base_url('/myadmin/enrolled-students'))->with('error', 'Student not found.');
        }

        $enrolments = $this->studentEnrollments($db, $student_id);
        return view('pages/admin/student_info', [
            'student' => $student,
            'enrolments' => $enrolments,
        ]);
    }

    public function edit_student($student_id)
    {
        $db = \Config\Database::connect();
        $student = $db->table('students')
            ->where('student_id', $student_id)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->to(base_url('/myadmin/enrolled-students'))->with('error', 'Student not found.');
        }

        return view('pages/admin/edit_student', array_merge(
            $this->studentFormOptions($db),
            [
                'student' => $student,
                'enrolments' => $this->studentEnrollments($db, $student_id),
            ]
        ));
    }

    public function update_student($student_id)
    {
        $db = \Config\Database::connect();
        $student = $db->table('students')
            ->where('student_id', $student_id)
            ->get()
            ->getRowArray();
        if (!$student) {
            return redirect()->to(base_url('/myadmin/enrolled-students'))->with('error', 'Student not found.');
        }

        $studentData = $this->postedStudentDetails(false);
        if ($studentData === null) {
            return redirect()->back()->withInput()->with('error', 'Enter valid student details.');
        }

        $existingEnrollments = $this->studentEnrollments($db, $student_id);
        $enrollmentsById = [];
        foreach ($existingEnrollments as $enrollment) {
            $enrollmentsById[(string) $enrollment['enrolment_id']] = $enrollment;
        }

        $postedEnrollments = $this->request->getPost('enrolments') ?? [];
        if (!is_array($postedEnrollments) || count($postedEnrollments) !== count($enrollmentsById)) {
            return redirect()->back()->withInput()->with('error', 'Enrollment details are incomplete.');
        }

        $removeIds = $this->request->getPost('remove_enrolment_ids') ?? [];
        if (!is_array($removeIds)) {
            return redirect()->back()->withInput()->with('error', 'Invalid enrollment removal selection.');
        }
        foreach ($removeIds as $removeId) {
            if (!is_string($removeId) || filter_var($removeId, FILTER_VALIDATE_INT) === false) {
                return redirect()->back()->withInput()->with('error', 'Invalid enrollment removal selection.');
            }
        }
        $removeIds = array_values(array_unique($removeIds));
        foreach ($removeIds as $removeId) {
            if (!isset($enrollmentsById[$removeId])) {
                return redirect()->back()->withInput()->with('error', 'Invalid enrollment removal selection.');
            }
        }

        $enrollmentUpdates = [];
        foreach ($enrollmentsById as $enrollmentId => $currentEnrollment) {
            if (!isset($postedEnrollments[$enrollmentId]) || !is_array($postedEnrollments[$enrollmentId])) {
                return redirect()->back()->withInput()->with('error', 'Enrollment details are incomplete.');
            }
            if (in_array((string) $enrollmentId, $removeIds, true)) {
                continue;
            }

            $data = $postedEnrollments[$enrollmentId];
            $validated = $this->validatedEnrollmentData($db, [
                'program_id' => $data['program_id'] ?? null,
                'year_id' => $data['year_id'] ?? null,
                'school_year_id' => $data['school_year_id'] ?? null,
                'semester_id' => $data['semester_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
                'assigned_course_id' => $data['assigned_course_id'] ?? null,
            ]);
            if ($validated === null) {
                return redirect()->back()->withInput()->with('error', 'Select valid values for every enrollment.');
            }
            $enrollmentUpdates[$enrollmentId] = [
                'program_id' => $validated['program_id'],
                'year_id' => $validated['year_id'],
                'assigned_course_id' => $validated['assigned_course_id'],
                'school_year_id' => $validated['school_year_id'],
                'semester_id' => $validated['semester_id'],
                'section_id' => $validated['section_id'],
            ];
        }

        $newAssignedCourseId = $this->request->getPost('new_assigned_course_id') ?? '';
        $newContext = [
            'program_id' => $this->request->getPost('new_program_id') ?? '',
            'year_id' => $this->request->getPost('new_year_id') ?? '',
            'school_year_id' => $this->request->getPost('new_school_year_id') ?? '',
            'semester_id' => $this->request->getPost('new_semester_id') ?? '',
            'section_id' => $this->request->getPost('new_section_id') ?? '',
            'assigned_course_id' => $newAssignedCourseId,
        ];
        $hasNewEnrollment = $newAssignedCourseId !== '' || in_array(true, array_map(
            static fn ($value) => $value !== '',
            array_slice($newContext, 0, 5)
        ), true);
        $newEnrollmentData = null;
        if ($hasNewEnrollment) {
            $newEnrollmentData = $this->validatedEnrollmentData($db, $newContext);
            if ($newEnrollmentData === null) {
                return redirect()->back()->withInput()->with('error', 'Complete the new enrollment details or clear the new enrollment fields.');
            }
        }

        $db->transStart();
        $db->table('students')->where('student_id', $student_id)->update($studentData);
        foreach ($enrollmentUpdates as $enrollmentId => $data) {
            $db->table('enrolments')->where('enrolment_id', $enrollmentId)->update($data);
        }
        if ($removeIds !== []) {
            $db->table('enrolments')->whereIn('enrolment_id', $removeIds)->delete();
        }
        if ($newEnrollmentData !== null) {
            $db->table('enrolments')->insert([
                'student_id' => $student_id,
                'program_id' => $newEnrollmentData['program_id'],
                'year_id' => $newEnrollmentData['year_id'],
                'assigned_course_id' => $newEnrollmentData['assigned_course_id'],
                'school_year_id' => $newEnrollmentData['school_year_id'],
                'semester_id' => $newEnrollmentData['semester_id'],
                'section_id' => $newEnrollmentData['section_id'],
            ]);
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Unable to update the student and enrollment.');
        }

        return redirect()->to(base_url('/myadmin/enrolled-students'))->with('success', 'Student updated successfully.');
    }

    public function delete_student($student_id)
    {
        $db = \Config\Database::connect();
        $student = $db->table('students')->where('student_id', $student_id)->get()->getRowArray();
        if (!$student) {
            return redirect()->to(base_url('/myadmin/enrolled-students'))->with('error', 'Student not found.');
        }

        $db->table('students')->where('student_id', $student_id)->delete();
        return redirect()->to(base_url('/myadmin/enrolled-students'))->with('success', 'Student deleted successfully.');
    }

    private function studentFormOptions($db): array
    {
        $assignmentRows = $db->table('courses_assignment')
            ->select([
                'courses_assignment.assigned_course_id',
                'courses.course_id',
                'courses.course_acronym',
                'courses.course',
            ])
            ->join('courses', 'courses.course_id = courses_assignment.course_id')
            ->where('courses_assignment.role', 'student')
            ->orderBy('courses_assignment.assigned_course_id')
            ->orderBy('courses.course')
            ->get()
            ->getResultArray();

        $courseAssignments = [];
        foreach ($assignmentRows as $row) {
            $assignmentId = (int) $row['assigned_course_id'];
            if (!isset($courseAssignments[$assignmentId])) {
                $courseAssignments[$assignmentId] = [
                    'assigned_course_id' => $assignmentId,
                    'label' => 'Assignment ' . $assignmentId,
                    'courses' => [],
                ];
            }
            $courseAssignments[$assignmentId]['courses'][] = $row['course_acronym'] . ' - ' . $row['course'];
        }
        foreach ($courseAssignments as &$assignment) {
            $assignment['label'] = implode(', ', $assignment['courses']);
        }
        unset($assignment);

        return [
            'programs' => $db->table('programs')->orderBy('program')->get()->getResultArray(),
            'years' => $db->table('year')->orderBy('year_id')->get()->getResultArray(),
            'course_assignments' => array_values($courseAssignments),
            'school_years' => $db->table('school_year')->orderBy('school_year_id', 'DESC')->get()->getResultArray(),
            'semesters' => $db->table('semesters')->orderBy('semester_id')->get()->getResultArray(),
            'sections' => $db->table('sections')->orderBy('section')->get()->getResultArray(),
        ];
    }

    private function studentEnrollments($db, $studentId): array
    {
        $rows = $db->table('enrolments')
            ->select([
                'enrolments.enrolment_id',
                'enrolments.program_id',
                'enrolments.year_id',
                'enrolments.assigned_course_id',
                'enrolments.school_year_id',
                'enrolments.semester_id',
                'enrolments.section_id',
                'programs.program_acronym',
                'programs.program',
                'academic_year.year AS academic_year',
                'courses.course_id',
                'courses.course_acronym',
                'courses.course',
                'school_year.year AS school_year',
                'semesters.semester',
                'sections.section AS section',
            ])
            ->join('programs', 'programs.program_id = enrolments.program_id', 'left')
            ->join('year AS academic_year', 'academic_year.year_id = enrolments.year_id', 'left')
            ->join('courses_assignment', "courses_assignment.assigned_course_id = enrolments.assigned_course_id AND courses_assignment.role = 'student'", 'left')
            ->join('courses', 'courses.course_id = courses_assignment.course_id', 'left')
            ->join('school_year', 'school_year.school_year_id = enrolments.school_year_id', 'left')
            ->join('semesters', 'semesters.semester_id = enrolments.semester_id', 'left')
            ->join('sections', 'sections.sections_id = enrolments.section_id', 'left')
            ->where('enrolments.student_id', $studentId)
            ->orderBy('enrolments.enrolment_id')
            ->get()
            ->getResultArray();

        $enrolments = [];
        foreach ($rows as $row) {
            $enrolmentId = (int) $row['enrolment_id'];
            if (!isset($enrolments[$enrolmentId])) {
                $enrolments[$enrolmentId] = [
                    'enrolment_id' => $enrolmentId,
                    'program_id' => $row['program_id'],
                    'year_id' => $row['year_id'],
                    'assigned_course_id' => $row['assigned_course_id'],
                    'school_year_id' => $row['school_year_id'],
                    'semester_id' => $row['semester_id'],
                    'section_id' => $row['section_id'],
                    'program_acronym' => $row['program_acronym'],
                    'program' => $row['program'],
                    'academic_year' => $row['academic_year'],
                    'school_year' => $row['school_year'],
                    'semester' => $row['semester'],
                    'section' => $row['section'],
                    'courses' => [],
                ];
            }
            if ($row['course_id'] !== null) {
                $enrolments[$enrolmentId]['courses'][(int) $row['course_id']] =
                    $row['course_acronym'] . ' - ' . $row['course'];
            }
        }

        foreach ($enrolments as &$enrolment) {
            $enrolment['courses'] = array_values($enrolment['courses']);
        }
        unset($enrolment);

        return array_values($enrolments);
    }

    private function postedStudentDetails(bool $passwordRequired): ?array
    {
        $data = [
            'first_name' => $this->request->getPost('first_name'),
            'middle_name' => $this->request->getPost('middle_name') ?? '',
            'last_name' => $this->request->getPost('last_name'),
            'extension_name' => $this->request->getPost('extension_name') ?? '',
            'gender' => $this->request->getPost('gender'),
            'birth_date' => $this->request->getPost('birth_date'),
        ];
        $password = $this->request->getPost('password');
        foreach ($data as $value) {
            if (!is_string($value)) {
                return null;
            }
        }
        if ($password !== null && !is_string($password)) {
            return null;
        }

        $data = array_map('trim', $data);
        $birthDate = \DateTime::createFromFormat('!Y-m-d', $data['birth_date']);
        $dateErrors = \DateTime::getLastErrors();
        if (
            $data['first_name'] === '' || strlen($data['first_name']) > 50 ||
            strlen($data['middle_name']) > 50 ||
            $data['last_name'] === '' || strlen($data['last_name']) > 50 ||
            strlen($data['extension_name']) > 50 ||
            !in_array($data['gender'], ['Male', 'Female'], true) ||
            !$birthDate || $birthDate->format('Y-m-d') !== $data['birth_date'] ||
            ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            return null;
        }

        $password = $password === null ? '' : $password;
        if (($passwordRequired && $password === '') || strlen($password) > 50) {
            return null;
        }
        if ($password !== '') {
            $data['password'] = $password;
        }
        if ($passwordRequired) {
            $data['in_campus'] = false;
        }
        return $data;
    }

    private function validatedEnrollmentData($db, array $data): ?array
    {
        $programId = $this->validIntegerReference($db, 'programs', 'program_id', $data['program_id'] ?? null);
        $yearId = $this->validIntegerReference($db, 'year', 'year_id', $data['year_id'] ?? null);
        $semesterId = $this->validIntegerReference($db, 'semesters', 'semester_id', $data['semester_id'] ?? null);
        $schoolYearId = $this->validIntegerReference($db, 'school_year', 'school_year_id', $data['school_year_id'] ?? null);
        $sectionId = $this->validStringReference($db, 'sections', 'sections_id', $data['section_id'] ?? null, 8);
        $assignedCourseId = $this->validStudentCourseAssignment(
            $db,
            $data['assigned_course_id'] ?? null
        );
        if (
            $programId === null || $yearId === null || $semesterId === null ||
            $schoolYearId === null || $sectionId === null || $assignedCourseId === null
        ) {
            return null;
        }

        return [
            'program_id' => $programId,
            'year_id' => $yearId,
            'school_year_id' => $schoolYearId,
            'semester_id' => $semesterId,
            'section_id' => $sectionId,
            'assigned_course_id' => $assignedCourseId,
        ];
    }

    private function validStudentCourseAssignment($db, $value): ?int
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }
        $id = (int) $value;
        if ($id < 1) {
            return null;
        }
        $count = $db->table('courses_assignment')
            ->where('assigned_course_id', $id)
            ->where('role', 'student')
            ->countAllResults();
        return $count > 0 ? $id : null;
    }

    private function validIntegerReference($db, string $table, string $field, $value): ?int
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }
        $id = (int) $value;
        if ($id < 1 || $db->table($table)->where($field, $id)->countAllResults() !== 1) {
            return null;
        }
        return $id;
    }

    private function validStringReference($db, string $table, string $field, $value, int $maxLength): ?string
    {
        if (!is_string($value) || trim($value) === '' || strlen($value) > $maxLength) {
            return null;
        }
        $value = trim($value);
        if ($db->table($table)->where($field, $value)->countAllResults() !== 1) {
            return null;
        }
        return $value;
    }

    public function view_faculty()
    {
        $db = \Config\Database::connect();
        $instructors = $db->table('instructors')
            ->select([
                'instructors.instructor_id',
                'instructors.first_name',
                'instructors.middle_name',
                'instructors.last_name',
                'instructors.extension_name',
                'instructors.gender',
                'instructors.birth_date',
                'instructors.in_campus',
                'MAX(instructor_levels.instructor_level) AS instructor_level',
            ])
            ->join('faculty', 'faculty.instructor_id = instructors.instructor_id', 'left')
            ->join('instructor_levels', 'instructor_levels.instructor_level_id = faculty.instructor_level_id', 'left')
            ->groupBy('instructors.instructor_id')
            ->get()
            ->getResultArray();

        return view('pages/admin/admin_view_faculty', ['instructors' => $instructors]);
    }

    public function view_instructor_attendance()
    {
        return view('pages/admin/admin_view_instructor_attendance');
    }

    public function change_authentication()
    {
        return view('pages/admin/admin_change_authentication');
    }

    public function update_authentication()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('admin_accounts');

        $currentUsername = trim((string) $this->request->getPost('current_username'));
        $currentPassword = (string) $this->request->getPost('current_password');
        $newUsername = trim((string) $this->request->getPost('new_username'));
        $newPassword = (string) $this->request->getPost('new_password');

        if ($currentUsername === '' || $currentPassword === '' || $newUsername === '' || $newPassword === '') {
            return redirect()->back()->withInput()->with('error', 'Please fill in all fields.');
        }

        $admin = $builder->where('username', $currentUsername)->get()->getRowArray();
        if (!$admin || $admin['password'] !== $currentPassword) {
            return redirect()->back()->withInput()->with('error', 'Current username or password is incorrect.');
        }

        if (strlen($newUsername) > 100 || strlen($newPassword) > 50) {
            return redirect()->back()->withInput()->with('error', 'New username or password exceeds the allowed length.');
        }

        $existingUsername = $builder->where('username', $newUsername)->where('admin_id !=', $admin['admin_id'])->get()->getRowArray();
        if ($existingUsername) {
            return redirect()->back()->withInput()->with('error', 'That username is already in use.');
        }

        $builder->where('admin_id', $admin['admin_id'])->update([
            'username' => $newUsername,
            'password' => $newPassword,
        ]);

        return redirect()->to(base_url('/myadmin/dashboard'))->with('success', 'Authentication updated successfully.');
    }

    public function login()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('admin_accounts');

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Find admin account from the database
        $admin = $builder->where('username', $username)->get()
            ->getRowArray();

        // Verify the password
        if ($admin && $admin['password'] === $password) {
            // Successful login, redirect to the admin dashboard or any other page
            return redirect()->to(base_url('/myadmin/dashboard'));
        } else {
            // Invalid credentials, show an error message
            return view('pages/admin/admin', ['error' => 'Invalid username or password']);
        }
    }

    public function view_instructor_info($instructor_id)
    {
        $db = \Config\Database::connect();
        $instructor = $db->table('instructors')
            ->select([
                'instructors.instructor_id',
                'instructors.first_name',
                'instructors.middle_name',
                'instructors.last_name',
                'instructors.extension_name',
                'instructors.gender',
                'instructors.birth_date',
                'instructors.in_campus',
                'instructor_levels.instructor_level',
            ])
            ->join('faculty', 'faculty.instructor_id = instructors.instructor_id', 'left')
            ->join('instructor_levels', 'instructor_levels.instructor_level_id = faculty.instructor_level_id', 'left')
            ->where('instructors.instructor_id', $instructor_id)
            ->get()
            ->getRowArray();

        if (!$instructor) {
            // Handle case when instructor is not found
            return redirect()->to(base_url('/myadmin/faculty'))->with('error', 'Instructor not found.');
        }

        $assigned_courses = $db->table('faculty')
            ->select('courses.course')
            ->join('courses_assignment', "courses_assignment.assigned_course_id = faculty.assigned_course_id AND courses_assignment.role = 'instructor'")
            ->join('courses', 'courses.course_id = courses_assignment.course_id')
            ->where('faculty.instructor_id', $instructor_id)
            ->get()
            ->getResultArray();

        $assigned_programs = $db->table('faculty')
            ->select('programs.program')
            ->join('programs_assignment', 'programs_assignment.assigned_program_id = faculty.assigned_program_id')
            ->join('programs', 'programs.program_id = programs_assignment.program_id')
            ->where('faculty.instructor_id', $instructor_id)
            ->get()
            ->getResultArray();

        return view('pages/admin/instructor_info', [
            'instructor' => $instructor,
            'assigned_courses' => $assigned_courses,
            'assigned_programs' => $assigned_programs,
        ]);
    }

    public function delete_instructor($instructor_id)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('instructors');

        // Delete the instructor from the database
        $builder->where('instructor_id', $instructor_id)->delete();

        // Redirect back to the faculty list with a success message
        return redirect()->to(base_url('/myadmin/faculty'))->with('success', 'Instructor deleted successfully.');
    }

    public function edit_instructor($instructor_id)
    {
        $db = \Config\Database::connect();
        $instructor = $db->table('instructors')
            ->where('instructor_id', $instructor_id)
            ->get()
            ->getRowArray();

        if (!$instructor) {
            // Handle case when instructor is not found
            return redirect()->to(base_url('/myadmin/faculty'))->with('error', 'Instructor not found.');
        }

        $faculty = $db->table('faculty')
            ->select('instructor_level_id')
            ->where('instructor_id', $instructor_id)
            ->orderBy('faculty_id')
            ->get()
            ->getRowArray();

        $instructor['instructor_level_id'] = $faculty['instructor_level_id'] ?? null;
        $current_course_ids = array_column(
            $db->table('faculty')
                ->select('courses_assignment.course_id')
                ->join('courses_assignment', "courses_assignment.assigned_course_id = faculty.assigned_course_id AND courses_assignment.role = 'instructor'")
                ->where('faculty.instructor_id', $instructor_id)
                ->get()
                ->getResultArray(),
            'course_id'
        );
        $current_program_ids = array_column(
            $db->table('faculty')
                ->select('programs_assignment.program_id')
                ->join('programs_assignment', 'programs_assignment.assigned_program_id = faculty.assigned_program_id')
                ->where('faculty.instructor_id', $instructor_id)
                ->get()
                ->getResultArray(),
            'program_id'
        );

        $instructor_levels = $db->table('instructor_levels')->get()->getResultArray();
        $assigned_courses = $db->table('courses')
            ->select('course_id, course')
            ->orderBy('course')
            ->get()
            ->getResultArray();
        $assigned_programs = $db->table('programs')
            ->select('program_id, program')
            ->orderBy('program')
            ->get()
            ->getResultArray();

        return view('pages/admin/edit_instructor', [
            'instructor' => $instructor,
            'instructor_levels' => $instructor_levels,
            'assigned_courses' => $assigned_courses,
            'current_course_ids' => $current_course_ids,
            'assigned_programs' => $assigned_programs,
            'current_program_ids' => $current_program_ids,
        ]);
    }

    public function update_instructor($instructor_id)
    {
        $db = \Config\Database::connect();
        $instructor = $db->table('instructors')
            ->where('instructor_id', $instructor_id)
            ->get()
            ->getRowArray();

        if (!$instructor) {
            return redirect()->to(base_url('/myadmin/faculty'))->with('error', 'Instructor not found.');
        }

        $courseIds = $this->request->getPost('course_ids');
        if (!is_array($courseIds) || $courseIds === []) {
            return redirect()->back()->withInput()->with('error', 'Select at least one assigned course.');
        }

        foreach ($courseIds as $courseId) {
            if (!is_string($courseId) || trim($courseId) === '' || strlen($courseId) > 8) {
                return redirect()->back()->withInput()->with('error', 'Invalid assigned course selection.');
            }
        }

        $courseIds = array_values(array_unique($courseIds));
        $courseCount = $db->table('courses')
            ->whereIn('course_id', $courseIds)
            ->countAllResults();
        if ($courseCount !== count($courseIds)) {
            return redirect()->back()->withInput()->with('error', 'One or more selected courses are invalid.');
        }

        $programIds = $this->request->getPost('program_ids');
        if (!is_array($programIds) || $programIds === []) {
            return redirect()->back()->withInput()->with('error', 'Select at least one assigned program.');
        }

        foreach ($programIds as $programId) {
            if (!is_string($programId) || trim($programId) === '' || strlen($programId) > 8) {
                return redirect()->back()->withInput()->with('error', 'Invalid assigned program selection.');
            }
        }

        $programIds = array_values(array_unique($programIds));
        $programCount = $db->table('programs')
            ->whereIn('program_id', $programIds)
            ->countAllResults();
        if ($programCount !== count($programIds)) {
            return redirect()->back()->withInput()->with('error', 'One or more selected programs are invalid.');
        }

        $levelId = $this->request->getPost('instructor_level_id');
        if (!is_scalar($levelId) || filter_var($levelId, FILTER_VALIDATE_INT) === false) {
            return redirect()->back()->withInput()->with('error', 'Select a valid instructor level.');
        }

        $levelExists = $db->table('instructor_levels')
            ->where('instructor_level_id', (int) $levelId)
            ->countAllResults();
        if ($levelExists !== 1) {
            return redirect()->back()->withInput()->with('error', 'Select a valid instructor level.');
        }

        $postedInstructorData = [
            'first_name' => $this->request->getPost('first_name'),
            'middle_name' => $this->request->getPost('middle_name') ?? '',
            'last_name' => $this->request->getPost('last_name'),
            'extension_name' => $this->request->getPost('extension_name') ?? '',
            'gender' => $this->request->getPost('gender'),
            'birth_date' => $this->request->getPost('birth_date'),
        ];
        foreach ($postedInstructorData as $value) {
            if (!is_string($value)) {
                return redirect()->back()->withInput()->with('error', 'Enter valid instructor details.');
            }
        }

        $birthDate = \DateTime::createFromFormat('!Y-m-d', $postedInstructorData['birth_date']);
        $dateErrors = \DateTime::getLastErrors();
        if (
            trim($postedInstructorData['first_name']) === '' || trim($postedInstructorData['last_name']) === '' ||
            !in_array($postedInstructorData['gender'], ['Male', 'Female'], true) ||
            !$birthDate || $birthDate->format('Y-m-d') !== $postedInstructorData['birth_date'] ||
            ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            return redirect()->back()->withInput()->with('error', 'Enter valid instructor details.');
        }

        $instructorData = array_map('trim', $postedInstructorData);
        $db->transStart();
        $db->table('instructors')
            ->where('instructor_id', $instructor_id)
            ->update($instructorData);

        $existingFaculty = $db->table('faculty')
            ->select('faculty_id, assigned_course_id, assigned_program_id')
            ->where('instructor_id', $instructor_id)
            ->get()
            ->getResultArray();
        $oldAssignmentIds = array_values(array_unique(array_map(
            'intval',
            array_column($existingFaculty, 'assigned_course_id')
        )));
        $oldProgramAssignmentIds = array_values(array_unique(array_map(
            'intval',
            array_column($existingFaculty, 'assigned_program_id')
        )));
        $newAssignmentId = (int) $db->table('courses_assignment')
            ->selectMax('assigned_course_id')
            ->get()
            ->getRow()
            ->assigned_course_id + 1;

        $assignmentRows = [];
        foreach ($courseIds as $courseId) {
            $assignmentRows[] = [
                'assigned_course_id' => $newAssignmentId,
                'course_id' => $courseId,
                'role' => 'instructor',
            ];
        }
        $db->table('courses_assignment')->insertBatch($assignmentRows);

        $newProgramAssignmentId = (int) $db->table('programs_assignment')
            ->selectMax('assigned_program_id')
            ->get()
            ->getRow()
            ->assigned_program_id + 1;
        $programAssignmentRows = [];
        foreach ($programIds as $programId) {
            $programAssignmentRows[] = [
                'assigned_program_id' => $newProgramAssignmentId,
                'program_id' => $programId,
            ];
        }
        $db->table('programs_assignment')->insertBatch($programAssignmentRows);

        if ($existingFaculty === []) {
            $db->table('faculty')->insert([
                'instructor_id' => $instructor_id,
                'instructor_level_id' => (int) $levelId,
                'assigned_program_id' => $newProgramAssignmentId,
                'assigned_course_id' => $newAssignmentId,
            ]);
        } else {
            $db->table('faculty')
                ->where('instructor_id', $instructor_id)
                ->update([
                    'instructor_level_id' => (int) $levelId,
                    'assigned_program_id' => $newProgramAssignmentId,
                    'assigned_course_id' => $newAssignmentId,
                ]);
        }

        if ($oldAssignmentIds !== []) {
            $sharedAssignmentIds = array_column(
                $db->table('faculty')
                    ->select('assigned_course_id')
                    ->whereIn('assigned_course_id', $oldAssignmentIds)
                    ->where('instructor_id !=', $instructor_id)
                    ->groupBy('assigned_course_id')
                    ->get()
                    ->getResultArray(),
                'assigned_course_id'
            );
            $studentAssignmentIds = array_column(
                $db->table('enrolments')
                    ->select('assigned_course_id')
                    ->whereIn('assigned_course_id', $oldAssignmentIds)
                    ->groupBy('assigned_course_id')
                    ->get()
                    ->getResultArray(),
                'assigned_course_id'
            );
            $protectedAssignmentIds = array_unique(array_merge(
                array_map('intval', $sharedAssignmentIds),
                array_map('intval', $studentAssignmentIds)
            ));
            $obsoleteAssignmentIds = array_values(array_diff($oldAssignmentIds, $protectedAssignmentIds));

            if ($obsoleteAssignmentIds !== []) {
                $db->table('courses_assignment')
                    ->whereIn('assigned_course_id', $obsoleteAssignmentIds)
                    ->where('role', 'instructor')
                    ->delete();
            }
        }

        if ($oldProgramAssignmentIds !== []) {
            $sharedProgramAssignmentIds = array_column(
                $db->table('faculty')
                    ->select('assigned_program_id')
                    ->whereIn('assigned_program_id', $oldProgramAssignmentIds)
                    ->where('instructor_id !=', $instructor_id)
                    ->groupBy('assigned_program_id')
                    ->get()
                    ->getResultArray(),
                'assigned_program_id'
            );
            $obsoleteProgramAssignmentIds = array_values(array_diff(
                $oldProgramAssignmentIds,
                array_map('intval', $sharedProgramAssignmentIds)
            ));

            if ($obsoleteProgramAssignmentIds !== []) {
                $db->table('programs_assignment')
                    ->whereIn('assigned_program_id', $obsoleteProgramAssignmentIds)
                    ->delete();
            }
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Unable to update instructor.');
        }

        return redirect()->to(base_url('/myadmin/faculty'))->with('success', 'Instructor updated successfully.');
    }

    public function add_instructor()
    {
        return view('pages/admin/add_instructor');
    }

    public function save_instructor()
    {
        $db = \Config\Database::connect();
        $firstName = $this->request->getPost('first_name');
        $lastName = $this->request->getPost('last_name');
        $middleName = $this->request->getPost('middle_name');
        $extensionName = $this->request->getPost('extension_name');
        $birthDate = $this->request->getPost('birth_date');
        $gender = $this->request->getPost('gender');
        $inCampus = false;
        // Validate the input data
        if (empty($firstName) || empty($lastName) || empty($birthDate) || empty($gender)) {
            return redirect()->back()->withInput()->with('error', 'Please fill in all required fields.');
        }
        // Insert the new instructor into the database
        $db->table('instructors')->insert([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'extension_name' => $extensionName,
            'birth_date' => $birthDate,
            'gender' => $gender,
            'in_campus' => $inCampus,
        ]);
        return redirect()->to(base_url('/myadmin/faculty'))->with('success', 'Instructor added successfully.');
    }

    public function view_courses()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('courses');
        $courses = $builder->get()->getResultArray();
        return view("/pages/admin/admin_view_courses", ['courses' => $courses]);
    }

    public function edit_courses()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('courses');
        $courses = $builder->get()->getResultArray();
        return view("/pages/admin/admin_edit_courses", ['courses' => $courses]);
    }

    public function update_courses()
    {
        $db = \Config\Database::connect();
        $courseIds = $this->request->getPost('course_id');

        if (!is_array($courseIds) || $courseIds === []) {
            return redirect()->back()->withInput()->with('error', 'No course records were submitted.');
        }

        $courseAcronyms = $this->request->getPost('course_acronym');
        $courseNames = $this->request->getPost('course');

        if (!is_array($courseAcronyms) || !is_array($courseNames) || count($courseAcronyms) !== count($courseIds) || count($courseNames) !== count($courseIds)) {
            return redirect()->back()->withInput()->with('error', 'Course details are incomplete.');
        }

        foreach ($courseIds as $index => $courseId) {
            $courseId = filter_var($courseId, FILTER_VALIDATE_INT);
            if ($courseId === false || $courseId <= 0) {
                return redirect()->back()->withInput()->with('error', 'One or more course IDs are invalid.');
            }

            $acronym = trim((string) ($courseAcronyms[$index] ?? ''));
            $name = trim((string) ($courseNames[$index] ?? ''));

            if ($acronym === '' || $name === '') {
                return redirect()->back()->withInput()->with('error', 'Course acronym and name are required for all entries.');
            }

            if (strlen($acronym) > 8 || strlen($name) > 100) {
                return redirect()->back()->withInput()->with('error', 'Course acronym and name exceed the allowed length.');
            }

            $db->table('courses')
                ->where('course_id', $courseId)
                ->update([
                    'course_acronym' => $acronym,
                    'course' => $name,
                ]);
        }

        return redirect()->to(base_url('/myadmin/view-courses'))->with('success', 'Course table updated successfully.');
    }

    public function delete_course()
    {
        //
    }

    public function view_programs()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('programs');
        $programs = $builder->get()->getResultArray();
        return view("/pages/admin/admin_view_programs", ['programs' => $programs]);
    }

    public function edit_programs()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('programs');
        $programs = $builder->get()->getResultArray();
        return view("/pages/admin/admin_edit_programs", ['programs' => $programs]);
    }

    public function update_programs()
    {
        $db = \Config\Database::connect();
        $programIds = $this->request->getPost('program_id');

        if (!is_array($programIds) || $programIds === []) {
            return redirect()->back()->withInput()->with('error', 'No program records were submitted.');
        }

        $programAcronyms = $this->request->getPost('program_acronym');
        $programNames = $this->request->getPost('program');

        if (!is_array($programAcronyms) || !is_array($programNames) || count($programAcronyms) !== count($programIds) || count($programNames) !== count($programIds)) {
            return redirect()->back()->withInput()->with('error', 'Program details are incomplete.');
        }

        foreach ($programIds as $index => $programId) {
            $programId = filter_var($programId, FILTER_VALIDATE_INT);
            if ($programId === false || $programId <= 0) {
                return redirect()->back()->withInput()->with('error', 'One or more program IDs are invalid.');
            }

            $acronym = trim((string) ($programAcronyms[$index] ?? ''));
            $name = trim((string) ($programNames[$index] ?? ''));

            if ($acronym === '' || $name === '') {
                return redirect()->back()->withInput()->with('error', 'Program acronym and name are required for all entries.');
            }

            if (strlen($acronym) > 8 || strlen($name) > 100) {
                return redirect()->back()->withInput()->with('error', 'Program acronym and name exceed the allowed length.');
            }

            $db->table('programs')
                ->where('program_id', $programId)
                ->update([
                    'program_acronym' => $acronym,
                    'program' => $name,
                ]);
        }

        return redirect()->to(base_url('/myadmin/view-programs'))->with('success', 'Program table updated successfully.');
    }

    public function delete_program()
    {
        //
    }
}
