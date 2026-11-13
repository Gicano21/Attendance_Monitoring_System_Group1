<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Pages extends BaseController
{
    public function admin()
    {
        return view('pages/admin');
    }

    public function admin_dashboard()
    {
        return view('pages/admin_dashboard');
    }

    public function view_enrolled_students()
    {
        return view('pages/admin_view_enrolled_students');
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

        return view('pages/admin_view_faculty', ['instructors' => $instructors]);
    }

    public function view_instructor_attendance()
    {
        return view('pages/admin_view_instructor_attendance');
    }

    public function change_authentication()
    {
        return view('pages/admin_change_authentication');
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
            return view('pages/admin', ['error' => 'Invalid username or password']);
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
            ->join('courses_assignment', 'courses_assignment.assigned_course_id = faculty.assigned_course_id')
            ->join('courses', 'courses.course_id = courses_assignment.course_id')
            ->where('faculty.instructor_id', $instructor_id)
            ->get()
            ->getResultArray();

        return view('pages/instructor_info', [
            'instructor' => $instructor,
            'assigned_courses' => $assigned_courses,
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
                ->join('courses_assignment', 'courses_assignment.assigned_course_id = faculty.assigned_course_id')
                ->where('faculty.instructor_id', $instructor_id)
                ->get()
                ->getResultArray(),
            'course_id'
        );

        $instructor_levels = $db->table('instructor_levels')->get()->getResultArray();
        $assigned_courses = $db->table('courses')
            ->select('course_id, course')
            ->orderBy('course')
            ->get()
            ->getResultArray();

        return view('pages/edit_instructor', [
            'instructor' => $instructor,
            'instructor_levels' => $instructor_levels,
            'assigned_courses' => $assigned_courses,
            'current_course_ids' => $current_course_ids,
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
            ->select('faculty_id, assigned_course_id')
            ->where('instructor_id', $instructor_id)
            ->get()
            ->getResultArray();
        $oldAssignmentIds = array_values(array_unique(array_map(
            'intval',
            array_column($existingFaculty, 'assigned_course_id')
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
            ];
        }
        $db->table('courses_assignment')->insertBatch($assignmentRows);

        if ($existingFaculty === []) {
            $db->table('faculty')->insert([
                'instructor_id' => $instructor_id,
                'instructor_level_id' => (int) $levelId,
                'assigned_course_id' => $newAssignmentId,
            ]);
        } else {
            $db->table('faculty')
                ->where('instructor_id', $instructor_id)
                ->update([
                    'instructor_level_id' => (int) $levelId,
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
            $obsoleteAssignmentIds = array_values(array_diff($oldAssignmentIds, array_map('intval', $sharedAssignmentIds)));

            if ($obsoleteAssignmentIds !== []) {
                $db->table('courses_assignment')
                    ->whereIn('assigned_course_id', $obsoleteAssignmentIds)
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
        return view('pages/add_instructor');
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
        return view("/pages/admin_view_courses", ['courses' => $courses]);
    }

    public function edit_courses()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('courses');
        $courses = $builder->get()->getResultArray();
        return view("/pages/admin_edit_courses", ['courses' => $courses]);
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
        return view("/pages/admin_view_programs", ['programs' => $programs]);
    }

    public function edit_programs()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('programs');
        $programs = $builder->get()->getResultArray();
        return view("/pages/admin_edit_programs", ['programs' => $programs]);
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
