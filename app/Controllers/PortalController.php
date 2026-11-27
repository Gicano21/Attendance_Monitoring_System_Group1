<?php

namespace App\Controllers;

use App\Services\PortalIdentityService;
use App\Models\AttendanceScheduleModel;
use App\Models\CourseModel;
use App\Models\EnrolmentModel;
use App\Models\InstructorAssignmentModel;
use App\Models\InstructorModel;
use App\Models\ProgramModel;
use App\Models\StudentModel;

abstract class PortalController extends BaseController
{
    private const RESOURCE_FIELDS = [
        'programs' => [
            'table' => 'programs',
            'primary' => 'program_id',
            'fields' => ['program_acronym', 'program'],
            'labels' => ['program_acronym' => 'Program code', 'program' => 'Program name'],
        ],
        'courses' => [
            'table' => 'courses',
            'primary' => 'course_id',
            'fields' => ['course_acronym', 'course'],
            'labels' => ['course_acronym' => 'Course code', 'course' => 'Course name'],
        ],
        'students' => [
            'table' => 'students',
            'primary' => 'student_id',
            'fields' => ['last_name', 'middle_name', 'first_name', 'extension_name', 'gender', 'birth_date'],
            'labels' => [
                'last_name' => 'Last name',
                'middle_name' => 'Middle name',
                'first_name' => 'First name',
                'extension_name' => 'Name extension',
                'gender' => 'Gender',
                'birth_date' => 'Birth date',
            ],
        ],
        'instructors' => [
            'table' => 'instructors',
            'primary' => 'instructor_id',
            'fields' => ['last_name', 'middle_name', 'first_name', 'extension_name', 'gender', 'birth_date'],
            'labels' => [
                'last_name' => 'Last name',
                'middle_name' => 'Middle name',
                'first_name' => 'First name',
                'extension_name' => 'Name extension',
                'gender' => 'Gender',
                'birth_date' => 'Birth date',
            ],
        ],
    ];

    public function loginForm(string $role)
    {
        if (!in_array($role, ['admin', 'student', 'instructor'], true)) {
            return redirect()->to('/');
        }

        $authenticatedRole = $this->authenticatedRole();
        if ($authenticatedRole !== null) {
            return redirect()->to($this->landingPath($authenticatedRole))
                ->with('error', 'You are already signed in. Log out first to open a login option.');
        }

        return view('pages/login/' . $role, [
            'role' => $role,
            'action' => $role === 'admin' ? site_url('myadmin/login') : site_url($role . '/login'),
        ]);
    }

    public function authenticateAccount(string $role)
    {
        if (!in_array($role, ['admin', 'student', 'instructor'], true)) {
            return redirect()->to('/');
        }

        $identifier = trim((string) $this->request->getPost('identifier'));
        $password = (string) $this->request->getPost('password');
        if ($identifier === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'The account ID or password is incorrect.');
        }

        $authenticatedRole = $this->authenticatedRole();
        if ($authenticatedRole !== null) {
            return redirect()->to($this->landingPath($authenticatedRole))
                ->with('error', 'You are already signed in. Log out first to open a login option.');
        }

        session()->remove(['role', 'user_id', 'display_name']);

        $table = $role === 'admin' ? 'admin_accounts' : ($role === 'student' ? 'students' : 'instructors');
        $key = $role === 'admin' ? 'username' : ($role === 'student' ? 'student_id' : 'instructor_id');
        $accountBuilder = db_connect()->table($table)->where($key, $identifier);
        if ($role !== 'admin') {
            $accountBuilder->where($table . '.deleted_at', null);
        }
        $account = $accountBuilder->get()->getRowArray();

        if ($account === null) {
            return redirect()->back()->withInput()->with('error', 'The account ID or password is incorrect.');
        }

        $id = (int) $account[$role === 'admin' ? 'admin_id' : ($role === 'student' ? 'student_id' : 'instructor_id')];
        $username = (new PortalIdentityService())->usernameFor($role, $id);
        $result = auth()->attempt(['username' => $username, 'password' => $password]);
        $user = auth()->user();
        if (!$result->isOK() || $user === null || !$user->inGroup($role)) {
            if ($user !== null) {
                auth()->logout();
            }
            return redirect()->back()->withInput()->with('error', 'The account ID or password is incorrect.');
        }

        $name = $role === 'admin'
            ? $account['username']
            : trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''));
        session()->set(['role' => $role, 'user_id' => $id, 'display_name' => $name]);

        return redirect()->to($this->landingPath($role));
    }

    public function logout()
    {
        auth()->logout();
        session()->remove(['role', 'user_id', 'display_name']);
        return redirect()->to('/');
    }

    public function dashboard(string $page = 'attendance')
    {
        $role = $this->authenticatedRole();
        if ($role === null) {
            return redirect()->to('/');
        }
        $userId = $this->authenticatedAccountId();
        if ($role === 'student' && $page !== 'attendance') {
            return redirect()->to('student/attendance');
        }
        if ($role === 'instructor' && !in_array($page, ['attendance', 'students'], true)) {
            return redirect()->to('instructor/attendance');
        }
        if ($role === 'admin' && !in_array($page, ['attendance', 'instructors', 'students', 'programs', 'courses'], true)) {
            return redirect()->to('myadmin/attendance');
        }

        $data = $this->dashboardViewData($role, $page, $this->pageTitle($role, $page));
        if ($role === 'admin' && $page === 'attendance') {
            $data['rows'] = db_connect()->table('instructors i')
                ->select('i.instructor_id AS id, i.first_name, i.last_name, i.in_campus')
                ->where('i.deleted_at', null)
                ->orderBy('i.last_name')->get()->getResultArray();
            $data['records'] = $this->attendanceRecords();
        } elseif ($page === 'attendance') {
            $data['records'] = $this->attendanceRecords($role, $userId);
            if ($role === 'instructor') {
                $data['rows'] = db_connect()->table('attendance_schedules s')
                    ->select('s.*, p.program_acronym, c.course_acronym')
                    ->join('faculty f', 'f.faculty_id = s.faculty_id')
                    ->join('programs p', 'p.program_id = s.program_id')
                    ->join('courses c', 'c.course_id = s.course_id')
                    ->where('f.instructor_id', $userId)
                    ->orderBy('s.day_of_week', 'ASC')
                    ->orderBy('s.starts_at', 'ASC')->get()->getResultArray();
            }
        } elseif ($role === 'instructor' && $page === 'students') {
            $data['rows'] = $this->instructorRoster($userId);
        } else {
            $config = self::RESOURCE_FIELDS[$page];
            $data['resource'] = $page;
            $data['columns'] = $config['fields'];
            $data['labels'] = $config['labels'];
            $data['rows'] = db_connect()->table($config['table'])
                ->where($config['table'] . '.deleted_at', null)
                ->orderBy($config['primary'], 'DESC')->get()->getResultArray();
            $data['deletedRows'] = db_connect()->table($config['table'])
                ->where($config['table'] . '.deleted_at IS NOT NULL')
                ->orderBy($config['primary'], 'DESC')->get()->getResultArray();
            if ($page === 'students') {
                $data['lookup'] = $this->enrolmentOptions();
            } elseif ($page === 'instructors') {
                $data['assignments'] = $this->facultyAssignments();
                $data['lookup'] = $this->facultyAssignmentOptions();
            }
        }

        return view('pages/' . $role . '/' . $page, $data);
    }

    protected function dashboardForRole(string $role, string $page)
    {
        if ($this->authenticatedRole() !== $role) {
            return redirect()->to('/');
        }

        return $this->dashboard($page);
    }

    public function editResource(string $resource, int $id)
    {
        $role = $this->authenticatedRole() ?? '';
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }

        if ($role === 'instructor' && !$this->instructorCanManageStudent($id, $this->authenticatedAccountId())) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }

        $record = db_connect()->table($config['table'])
            ->where($config['primary'], $id)
            ->where($config['table'] . '.deleted_at', null)
            ->get()->getRowArray();
        if ($record === null) {
            return redirect()->to($this->landingPath($role))->with('error', 'The selected record no longer exists.');
        }

        $data = $this->dashboardViewData($role, 'edit', 'Edit ' . rtrim(ucfirst($resource), 's'));
        $data['resource'] = $resource;
        $data['columns'] = $config['fields'];
        $data['labels'] = $config['labels'];
        $data['record'] = $record;
        if ($resource === 'students') {
            $data['lookup'] = $this->enrolmentOptions();
            $data['enrolments'] = $this->studentEnrolments($id);
        } elseif ($resource === 'instructors') {
            $data['assignments'] = $this->facultyAssignments($id);
            $data['lookup'] = $this->facultyAssignmentOptions();
        }

        return view('pages/' . $role . '/' . $resource, $data);
    }

    public function saveResource(string $resource)
    {
        $role = $this->authenticatedRole() ?? '';
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }

        $id = (int) $this->request->getPost('id');
        if ($role === 'instructor' && $id > 0 && !$this->instructorCanManageStudent($id, $this->authenticatedAccountId())) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }
        if ($role === 'instructor' && $resource === 'students' && $id === 0) {
            return redirect()->to('instructor/students')
                ->with('error', 'Students must already be enrolled before they can appear in your roster.');
        }

        $values = [];
        foreach ($config['fields'] as $field) {
            $values[$field] = trim((string) $this->request->getPost($field));
        }
        $validationModel = match ($resource) {
            'programs' => model(ProgramModel::class),
            'courses' => model(CourseModel::class),
            'students' => model(StudentModel::class),
            'instructors' => model(InstructorModel::class),
        };
        if (!$validationModel->validate($values)) {
            $errors = $validationModel->errors();
            return redirect()->back()->withInput()->with('error', reset($errors) ?: 'Check the submitted values and try again.');
        }

        $isAccount = in_array($resource, ['students', 'instructors'], true);
        $newPassword = null;
        if ($isAccount && $id === 0) {
            $newPassword = (string) $this->request->getPost('password');
            if (strlen($newPassword) < 8) {
                return redirect()->back()->withInput()->with('error', 'Initial passwords must contain at least 8 characters.');
            }
            $values['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $values['in_campus'] = false;
        } elseif ($isAccount) {
            $submittedPassword = (string) $this->request->getPost('password');
            if ($submittedPassword !== '') {
                if (strlen($submittedPassword) < 8) {
                    return redirect()->back()->withInput()->with('error', 'Passwords must contain at least 8 characters.');
                }
                $newPassword = $submittedPassword;
                $values['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        $db = db_connect();
        $enrolments = [];
        $existingEnrolments = [];
        $transactionStarted = false;
        if ($resource === 'students' && ($id === 0 || $role === 'admin')) {
            $programId = (int) $this->request->getPost('program_id');
            $courseIds = $role === 'admin'
                ? $this->request->getPost('course_ids')
                : [$this->request->getPost('course_id')];
            if (!is_array($courseIds) || $courseIds === []) {
                return redirect()->back()->withInput()->with('error', 'Choose a program and at least one course assignment.');
            }

            $normalizedCourseIds = [];
            foreach ($courseIds as $courseId) {
                $courseId = is_scalar($courseId) ? filter_var($courseId, FILTER_VALIDATE_INT) : false;
                if ($courseId === false || $courseId < 1) {
                    return redirect()->back()->withInput()->with('error', 'Choose valid course assignments.');
                }
                $normalizedCourseIds[] = (int) $courseId;
            }
            if (count(array_unique($normalizedCourseIds)) !== count($normalizedCourseIds)) {
                return redirect()->back()->withInput()->with('error', 'Choose each course assignment only once.');
            }

            $db->transStart();
            $transactionStarted = true;
            if ($id > 0) {
                foreach ($this->studentEnrolments($id) as $existingEnrolment) {
                    $courseId = (int) $existingEnrolment['course_id'];
                    if (isset($existingEnrolments[$courseId])) {
                        $db->transRollback();
                        return redirect()->back()->withInput()->with('error', 'This student has multiple enrolment records for one course; edit those records separately.');
                    }
                    $existingEnrolments[$courseId] = $existingEnrolment;
                }
            }
            foreach ($normalizedCourseIds as $courseId) {
                $assignment = $this->validStudentAssignment($role, $courseId, $programId);
                if ($assignment === null) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Choose an existing program and course.');
                }
                if ($role === 'admin') {
                    $this->ensureCourseAssignment($courseId, 'student');
                    if ($db->error()['code'] !== 0) {
                        $db->transRollback();
                        return redirect()->back()->withInput()->with('error', 'The selected course assignment could not be prepared.');
                    }
                }
                $enrolment = $this->enrolmentValues($assignment);
                if ($enrolment === null) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Choose valid course assignments and complete the enrolment fields.');
                }
                $enrolments[$courseId] = $enrolment;
            }
        }

        if ($id > 0) {
            if ($isAccount && $newPassword !== null && !$transactionStarted) {
                $db->transStart();
                $transactionStarted = true;
            }
            $builder = $db->table($config['table']);
            $builder->where($config['primary'], $id)->update($values);
            if ($transactionStarted) {
                foreach ($existingEnrolments as $courseId => $existingEnrolment) {
                    if (isset($enrolments[$courseId])) {
                        continue;
                    }
                    if ($db->tableExists('student_time_ins')
                        && $db->table('student_time_ins')
                            ->where('enrolment_id', $existingEnrolment['enrolment_id'])
                            ->countAllResults() > 0) {
                        $db->transRollback();
                        return redirect()->back()->withInput()->with('error', 'A course assignment with attendance history cannot be removed.');
                    }
                    $db->table('enrolments')
                        ->where('enrolment_id', $existingEnrolment['enrolment_id'])
                        ->delete();
                }
                foreach ($enrolments as $courseId => $enrolment) {
                    if (isset($existingEnrolments[$courseId])) {
                        $db->table('enrolments')
                            ->where('enrolment_id', $existingEnrolments[$courseId]['enrolment_id'])
                            ->update($enrolment);
                    } else {
                        $enrolment['student_id'] = $id;
                        $db->table('enrolments')->insert($enrolment);
                    }
                }
                if ($isAccount && $newPassword !== null) {
                    $accountRole = $resource === 'students' ? 'student' : 'instructor';
                    (new PortalIdentityService())->updatePassword($accountRole, $id, $newPassword);
                }
                $db->transComplete();
            }
        } else {
            if (!$transactionStarted) {
                $db->transStart();
            }
            $db->table($config['table'])->insert($values);
            $recordId = (int) $db->insertID();
            if ($isAccount && $newPassword !== null) {
                $accountRole = $resource === 'students' ? 'student' : 'instructor';
                (new PortalIdentityService())->createAccount($accountRole, $recordId, $newPassword);
            }
            foreach ($enrolments as $enrolment) {
                $enrolment['student_id'] = $recordId;
                $db->table('enrolments')->insert($enrolment);
            }
            $db->transComplete();
        }

        if ($db->error()['code'] !== 0 || $db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'The record could not be saved. Check required fields and try again.');
        }

        return redirect()->to($this->landingPath($role, $resource))->with('success', 'Saved successfully.');
    }

    public function deleteResource(string $resource, int $id)
    {
        $role = $this->authenticatedRole() ?? '';
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }
        if ($role === 'instructor' && !$this->instructorCanManageStudent($id, $this->authenticatedAccountId())) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }
        if ($role === 'instructor' && $resource === 'students') {
            $assignments = $this->instructorAssignments($this->authenticatedAccountId());
            if ($assignments !== []) {
                $builder = db_connect()->table('enrolments e')
                    ->select('e.enrolment_id')
                    ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
                    ->where('e.student_id', $id)
                    ->where('ca.role', 'student')
                    ->groupStart();
                foreach ($assignments as $assignment) {
                    $builder->orGroupStart()
                        ->where('e.program_id', $assignment['program_id'])
                        ->where('ca.course_id', $assignment['course_id'])
                        ->where('e.year_id', $assignment['year_id'])
                        ->where('e.semester_id', $assignment['semester_id'])
                        ->where('e.section_id', $assignment['section_id'])
                        ->groupEnd();
                }
                $enrolmentIds = array_column($builder->groupEnd()->get()->getResultArray(), 'enrolment_id');
                if ($enrolmentIds !== []) {
                    db_connect()->table('enrolments')->whereIn('enrolment_id', $enrolmentIds)->delete();
                }
            }
            return redirect()->to('instructor/students')->with('success', 'Student removed from your assigned roster.');
        }

        db_connect()->table($config['table'])->where($config['primary'], $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
        if (db_connect()->error()['code'] !== 0) {
            return redirect()->to($this->landingPath($role, $resource))
                ->with('error', 'This record could not be moved to deleted records.');
        }
        if ($resource === 'students' || $resource === 'instructors') {
            $accountRole = $resource === 'students' ? 'student' : 'instructor';
            (new PortalIdentityService())->deleteAccount($accountRole, $id);
        }
        return redirect()->to($this->landingPath($role, $resource))->with('success', 'Deleted successfully. It can be restored from Deleted records.');
    }

    public function restoreResource(string $resource, int $id)
    {
        if ($this->authenticatedRole() !== 'admin') {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to('myadmin');
        }

        $deleted = db_connect()->table($config['table'])
            ->where($config['primary'], $id)
            ->where($config['table'] . '.deleted_at IS NOT NULL')
            ->get()->getRowArray();
        if ($deleted === null) {
            return redirect()->to($this->landingPath('admin', $resource))
                ->with('error', 'The selected record is not in Deleted records.');
        }

        db_connect()->table($config['table'])->where($config['primary'], $id)->update([
            'deleted_at' => null,
        ]);
        if (db_connect()->error()['code'] !== 0) {
            return redirect()->to($this->landingPath('admin', $resource))
                ->with('error', 'The record could not be restored.');
        }
        if ($resource === 'students' || $resource === 'instructors') {
            $accountRole = $resource === 'students' ? 'student' : 'instructor';
            (new PortalIdentityService())->restoreAccount($accountRole, $id);
        }
        return redirect()->to($this->landingPath('admin', $resource))
            ->with('success', 'Record restored successfully.');
    }

    public function schedules()
    {
        if ($this->authenticatedRole() !== 'instructor') {
            return redirect()->to('/');
        }

        $instructorId = $this->authenticatedAccountId();
        $assignments = $this->instructorAssignments($instructorId);
        $rows = db_connect()->table('attendance_schedules s')
            ->select('s.*, p.program_acronym, c.course_acronym')
            ->join('faculty f', 'f.faculty_id = s.faculty_id')
            ->join('programs p', 'p.program_id = s.program_id')
            ->join('courses c', 'c.course_id = s.course_id')
            ->where('f.instructor_id', $instructorId)
            ->orderBy('s.day_of_week', 'ASC')
            ->orderBy('s.starts_at', 'ASC')->get()->getResultArray();

        $data = $this->dashboardViewData('instructor', 'schedules', 'Set Attendance');
        $data['assignments'] = $assignments;
        $data['rows'] = $rows;

        return view('pages/instructor/schedules', $data);
    }

    public function saveFacultyAssignment()
    {
        if ($this->authenticatedRole() !== 'admin') {
            return redirect()->to('/');
        }

        $instructorId = (int) $this->request->getPost('instructor_id');
        $returnPath = $this->request->getPost('return_to') === 'edit' && $instructorId > 0
            ? 'myadmin/instructors/edit/' . $instructorId
            : 'myadmin/instructors';
        $programId = (int) $this->request->getPost('program_id');
        $courseId = (int) $this->request->getPost('course_id');
        $levelId = (int) $this->request->getPost('instructor_level_id');
        $yearId = (int) $this->request->getPost('year_id');
        $semesterId = (int) $this->request->getPost('semester_id');
        $sectionId = trim((string) $this->request->getPost('section_id'));
        $db = db_connect();

        $assignmentModel = model(InstructorAssignmentModel::class);
        if (!$assignmentModel->validate([
            'instructor_id' => $instructorId,
            'instructor_level_id' => $levelId,
            'program_id' => $programId,
            'course_id' => $courseId,
            'year_id' => $yearId,
            'semester_id' => $semesterId,
            'section_id' => $sectionId,
        ])) {
            return redirect()->to($returnPath)->with('error', 'Choose an existing instructor, level, program, course, year, semester, and section.');
        }

        if ($db->table('instructors')->where('instructor_id', $instructorId)->where('deleted_at', null)->countAllResults() === 0
            || $db->table('programs')->where('program_id', $programId)->where('deleted_at', null)->countAllResults() === 0
            || $db->table('courses')->where('course_id', $courseId)->where('deleted_at', null)->countAllResults() === 0
            || $db->table('instructor_levels')->where('instructor_level_id', $levelId)->countAllResults() === 0
            || $db->table('year')->where('year_id', $yearId)->countAllResults() === 0
            || $db->table('semesters')->where('semester_id', $semesterId)->countAllResults() === 0
            || $db->table('sections')->where('sections_id', $sectionId)->countAllResults() === 0) {
            return redirect()->to($returnPath)->with('error', 'Choose an existing instructor, level, program, course, year, semester, and section.');
        }

        $programAssignmentId = $this->ensureProgramAssignment($programId);
        $instructorCourseAssignmentId = $this->ensureCourseAssignment($courseId, 'instructor');
        $this->ensureCourseAssignment($courseId, 'student');

        $exists = $db->table('faculty')
            ->where('instructor_id', $instructorId)
            ->where('assigned_program_id', $programAssignmentId)
            ->where('assigned_course_id', $instructorCourseAssignmentId)
            ->where('year_id', $yearId)
            ->where('semester_id', $semesterId)
            ->where('section_id', $sectionId)
            ->countAllResults();
        if ($exists > 0) {
            return redirect()->to($returnPath)->with('error', 'This instructor assignment already exists.');
        }

        $legacyAssignment = $db->table('faculty')
            ->where('instructor_id', $instructorId)
            ->where('assigned_program_id', $programAssignmentId)
            ->where('assigned_course_id', $instructorCourseAssignmentId)
            ->where('year_id', null)
            ->where('semester_id', null)
            ->where('section_id', null)
            ->get()->getRowArray();
        $assignmentValues = [
            'instructor_level_id' => $levelId,
            'year_id' => $yearId,
            'semester_id' => $semesterId,
            'section_id' => $sectionId,
        ];
        if ($legacyAssignment !== null) {
            $db->table('faculty')->where('faculty_id', $legacyAssignment['faculty_id'])->update($assignmentValues);
            if ($db->error()['code'] !== 0) {
                return redirect()->to($returnPath)->with('error', 'The instructor assignment could not be updated.');
            }
            return redirect()->to($returnPath)->with('success', 'Instructor assignment scope updated.');
        }

        $db->table('faculty')->insert([
            'instructor_id' => $instructorId,
            ...$assignmentValues,
            'assigned_program_id' => $programAssignmentId,
            'assigned_course_id' => $instructorCourseAssignmentId,
        ]);
        if ($db->error()['code'] !== 0) {
            return redirect()->to($returnPath)->with('error', 'The instructor assignment could not be saved.');
        }
        return redirect()->to($returnPath)->with('success', 'Instructor assigned to the Program/Course/Year/Semester/Section.');
    }

    public function deleteFacultyAssignment(int $facultyId)
    {
        if ($this->authenticatedRole() !== 'admin') {
            return redirect()->to('/');
        }

        $instructorId = (int) $this->request->getPost('instructor_id');
        $returnPath = $this->request->getPost('return_to') === 'edit' && $instructorId > 0
            ? 'myadmin/instructors/edit/' . $instructorId
            : 'myadmin/instructors';
        $db = db_connect();
        if ($db->table('attendance_schedules')->where('faculty_id', $facultyId)->countAllResults() > 0) {
            return redirect()->to($returnPath)->with('error', 'This assignment has attendance schedules and cannot be removed.');
        }
        $db->table('faculty')->where('faculty_id', $facultyId)->delete();
        if ($db->error()['code'] !== 0) {
            return redirect()->to($returnPath)->with('error', 'The instructor assignment could not be removed.');
        }
        return redirect()->to($returnPath)->with('success', 'Instructor assignment removed.');
    }

    public function saveSchedule()
    {
        if ($this->authenticatedRole() !== 'instructor') {
            return redirect()->to('/');
        }

        $facultyId = (int) $this->request->getPost('faculty_id');
        $programId = (int) $this->request->getPost('program_id');
        $courseId = (int) $this->request->getPost('course_id');
        $day = filter_var($this->request->getPost('day_of_week'), FILTER_VALIDATE_INT);
        $start = (string) $this->request->getPost('starts_at');
        $end = (string) $this->request->getPost('ends_at');
        $assignment = null;
        foreach ($this->instructorAssignments($this->authenticatedAccountId()) as $candidate) {
            if ((int) $candidate['faculty_id'] === $facultyId
                && (int) $candidate['program_id'] === $programId
                && (int) $candidate['course_id'] === $courseId) {
                $assignment = $candidate;
                break;
            }
        }

        $scheduleModel = model(AttendanceScheduleModel::class);
        $scheduleIsValid = $day !== false && $scheduleModel->validate([
            'faculty_id' => $facultyId,
            'program_id' => $programId,
            'course_id' => $courseId,
            'day_of_week' => $day,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);
        if ($assignment === null || !$scheduleIsValid
            || !$this->validTime($start) || !$this->validTime($end) || $start >= $end) {
            return redirect()->back()->withInput()->with('error', 'Choose one of your assigned Program/Course pairs, a day of the week, and a valid time window.');
        }

        $scheduleTable = db_connect()->table('attendance_schedules');
        $startClock = strlen($start) === 5 ? $start . ':00' : $start;
        $endClock = strlen($end) === 5 ? $end . ':00' : $end;
        $overlap = false;
        foreach ($scheduleTable->select('day_of_week, schedule_date, starts_at, ends_at')
            ->where('faculty_id', $facultyId)->get()->getResultArray() as $existing) {
            $existingDay = $existing['day_of_week'] === null
                ? (int) date('N', strtotime($existing['schedule_date']))
                : (int) $existing['day_of_week'];
            $isRecurring = $existing['day_of_week'] !== null;
            if ($existingDay === $day
                && ($isRecurring || $existing['schedule_date'] >= date('Y-m-d'))
                && $startClock < (string) $existing['ends_at']
                && $endClock > (string) $existing['starts_at']) {
                $overlap = true;
                break;
            }
        }
        if ($overlap) {
            return redirect()->back()->withInput()->with('error', 'This schedule overlaps another attendance window.');
        }

        $daysUntil = ($day - (int) date('N') + 7) % 7;
        $firstOccurrence = date('Y-m-d', strtotime('+' . $daysUntil . ' days'));
        $scheduleTable->insert([
            'faculty_id' => $facultyId,
            'program_id' => $programId,
            'course_id' => $courseId,
            'schedule_date' => $firstOccurrence,
            'day_of_week' => $day,
            'starts_at' => $start,
            'ends_at' => $end,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if (db_connect()->error()['code'] !== 0) {
            return redirect()->back()->withInput()->with('error', 'Could not save this attendance window.');
        }
        return redirect()->to('instructor/schedules')->with('success', 'Attendance window added.');
    }

    public function changeAuthentication(string $role)
    {
        if ($this->authenticatedRole() !== $role) {
            return redirect()->to('/');
        }
        return view('pages/' . $role . '/authentication', $this->dashboardViewData($role, 'authentication', 'Change Authentication'));
    }

    public function updateAuthentication(string $role)
    {
        if ($this->authenticatedRole() !== $role) {
            return redirect()->to('/');
        }

        [$table, $key] = $this->accountTableAndKey($role);
        $accountId = $this->authenticatedAccountId();
        $account = db_connect()->table($table)
            ->where($key, $accountId)->get()->getRowArray();
        $current = (string) $this->request->getPost('current_password');
        $new = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');
        $user = auth()->user();
        $passwordHash = $user === null ? null : $user->getPasswordHash();

        if ($account === null || $passwordHash === null || !service('passwords')->verify($current, $passwordHash)) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }
        if (strlen($new) < 8 || $new !== $confirm) {
            return redirect()->back()->with('error', 'New passwords must match and contain at least 8 characters.');
        }

        $db = db_connect();
        $db->transStart();
        $db->table($table)
            ->where($key, $accountId)
            ->update(['password' => password_hash($new, PASSWORD_DEFAULT)]);
        (new PortalIdentityService())->updatePassword($role, $accountId, $new);
        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'The password could not be updated. Try again.');
        }
        return redirect()->to($this->landingPath($role))->with('success', 'Password updated.');
    }

    private function attendanceRecords(?string $role = null, ?int $id = null): array
    {
        $builder = db_connect()->table('attendance_records r')
            ->select('r.*, s.schedule_date, p.program_acronym, c.course_acronym')
            ->join('attendance_schedules s', 's.schedule_id = r.schedule_id')
            ->join('programs p', 'p.program_id = s.program_id')
            ->join('courses c', 'c.course_id = s.course_id')
            ->orderBy('r.scanned_at', 'DESC')
            ->limit(100);

        if ($role !== null && $id !== null) {
            $builder->where('r.participant_role', $role)->where('r.participant_id', $id);
        }
        return $builder->get()->getResultArray();
    }

    private function instructorAssignments(int $instructorId): array
    {
        return db_connect()->table('faculty f')
            ->select('f.faculty_id, p.program_id, p.program_acronym, c.course_id, c.course_acronym, f.year_id, f.semester_id, f.section_id')
            ->join('programs_assignment pa', 'pa.assigned_program_id = f.assigned_program_id')
            ->join('programs p', 'p.program_id = pa.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = f.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->where('f.instructor_id', $instructorId)
            ->where('ca.role', 'instructor')
            ->get()->getResultArray();
    }

    private function facultyAssignments(?int $instructorId = null): array
    {
        $builder = db_connect()->table('faculty f')
            ->select('f.faculty_id, i.first_name, i.last_name, il.instructor_level, p.program_acronym, c.course_acronym, y.year, sem.semester, sec.section')
            ->join('instructors i', 'i.instructor_id = f.instructor_id')
            ->join('instructor_levels il', 'il.instructor_level_id = f.instructor_level_id')
            ->join('programs_assignment pa', 'pa.assigned_program_id = f.assigned_program_id')
            ->join('programs p', 'p.program_id = pa.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = f.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->join('year y', 'y.year_id = f.year_id', 'left')
            ->join('semesters sem', 'sem.semester_id = f.semester_id', 'left')
            ->join('sections sec', 'sec.sections_id = f.section_id', 'left')
            ->orderBy('i.last_name');
        if ($instructorId !== null) {
            $builder->where('f.instructor_id', $instructorId);
        }
        return $builder->get()->getResultArray();
    }

    private function facultyAssignmentOptions(): array
    {
        $db = db_connect();
        return [
            'instructors' => $db->table('instructors')->where('deleted_at', null)->orderBy('last_name')->get()->getResultArray(),
            'levels' => $db->table('instructor_levels')->get()->getResultArray(),
            'programs' => $db->table('programs')->where('deleted_at', null)->orderBy('program_acronym')->get()->getResultArray(),
            'courses' => $db->table('courses')->where('deleted_at', null)->orderBy('course_acronym')->get()->getResultArray(),
            'years' => $db->table('year')->get()->getResultArray(),
            'semesters' => $db->table('semesters')->get()->getResultArray(),
            'sections' => $db->table('sections')->get()->getResultArray(),
        ];
    }

    private function ensureProgramAssignment(int $programId): int
    {
        $db = db_connect();
        $existing = $db->table('programs_assignment')
            ->where('program_id', $programId)->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['assigned_program_id'];
        }

        $assignedId = (int) $db->table('programs_assignment')->selectMax('assigned_program_id')
            ->get()->getRowArray()['assigned_program_id'] + 1;
        $db->table('programs_assignment')->insert(['assigned_program_id' => $assignedId, 'program_id' => $programId]);
        return $assignedId;
    }

    private function ensureCourseAssignment(int $courseId, string $role): int
    {
        $db = db_connect();
        $existing = $db->table('courses_assignment')
            ->where('course_id', $courseId)->where('role', $role)->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['assigned_course_id'];
        }

        $assignedId = (int) $db->table('courses_assignment')->selectMax('assigned_course_id')
            ->get()->getRowArray()['assigned_course_id'] + 1;
        $db->table('courses_assignment')->insert([
            'assigned_course_id' => $assignedId,
            'course_id' => $courseId,
            'role' => $role,
        ]);
        return $assignedId;
    }

    private function instructorRoster(int $instructorId): array
    {
        $assignments = $this->instructorAssignments($instructorId);
        if ($assignments === []) {
            return [];
        }

        $builder = db_connect()->table('students st')
            ->distinct()
            ->select('st.student_id AS id, st.first_name, st.last_name, st.gender, st.birth_date, p.program_acronym, c.course_acronym, y.year, sem.semester, sec.section')
            ->join('enrolments e', 'e.student_id = st.student_id')
            ->join('programs p', 'p.program_id = e.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->join('year y', 'y.year_id = e.year_id')
            ->join('semesters sem', 'sem.semester_id = e.semester_id')
            ->join('sections sec', 'sec.sections_id = e.section_id')
            ->where('ca.role', 'student')
            ->groupStart();
        foreach ($assignments as $assignment) {
            $builder->orGroupStart()
                ->where('e.program_id', $assignment['program_id'])
                ->where('ca.course_id', $assignment['course_id'])
                ->where('e.year_id', $assignment['year_id'])
                ->where('e.semester_id', $assignment['semester_id'])
                ->where('e.section_id', $assignment['section_id'])
                ->groupEnd();
        }
        return $builder->groupEnd()
            ->orderBy('st.last_name')->orderBy('st.first_name')
            ->get()->getResultArray();

    }

    private function instructorCanManageStudent(int $studentId, int $instructorId): bool
    {
        foreach ($this->instructorRoster($instructorId) as $student) {
            if ((int) $student['id'] === $studentId) {
                return true;
            }
        }
        return false;
    }

    private function enrolmentOptions(): array
    {
        $db = db_connect();
        return [
            'programs' => $db->table('programs')->where('deleted_at', null)->orderBy('program_acronym')->get()->getResultArray(),
            'courses' => $db->table('courses')
                ->select('course_id, course_acronym, course')
                ->where('deleted_at', null)
                ->orderBy('course_acronym')->get()->getResultArray(),
            'years' => $db->table('year')->get()->getResultArray(),
            'school_years' => $db->table('school_year')->get()->getResultArray(),
            'semesters' => $db->table('semesters')->get()->getResultArray(),
            'sections' => $db->table('sections')->get()->getResultArray(),
        ];
    }

    private function studentEnrolments(int $studentId): array
    {
        return db_connect()->table('enrolments e')
            ->select('e.*, ca.course_id, c.course_acronym, c.course')
            ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->where('e.student_id', $studentId)
            ->where('ca.role', 'student')
            ->orderBy('c.course_acronym')
            ->get()->getResultArray();
    }

    private function validStudentAssignment(string $role, int $courseId, int $programId): ?array
    {
        if ($courseId < 1 || $programId < 1) {
            return null;
        }

        if ($role === 'instructor') {
            foreach ($this->instructorAssignments($this->authenticatedAccountId()) as $assignment) {
                if ((int) $assignment['course_id'] === $courseId && (int) $assignment['program_id'] === $programId) {
                    return $assignment;
                }
            }
            return null;
        }

        $db = db_connect();
        $programExists = $db->table('programs')->where('program_id', $programId)->where('deleted_at', null)->countAllResults() > 0;
        $courseExists = $db->table('courses')->where('course_id', $courseId)->where('deleted_at', null)->countAllResults() > 0;
        if (!$programExists || !$courseExists) {
            return null;
        }
        return ['course_id' => $courseId, 'program_id' => $programId];
    }

    private function enrolmentValues(?array $assignment): ?array
    {
        if ($assignment === null) {
            return null;
        }

        $courseAssignment = db_connect()->table('courses_assignment')
            ->where('course_id', $assignment['course_id'])
            ->where('role', 'student')
            ->get()->getRowArray();
        $values = [
            'year_id' => (int) $this->request->getPost('year_id'),
            'school_year_id' => (int) $this->request->getPost('school_year_id'),
            'semester_id' => (int) $this->request->getPost('semester_id'),
            'section_id' => trim((string) $this->request->getPost('section_id')),
        ];
        if ($courseAssignment === null || !model(EnrolmentModel::class)->validate([
            'program_id' => (int) $assignment['program_id'],
            'assigned_course_id' => (int) $courseAssignment['assigned_course_id'],
            'year_id' => $values['year_id'],
            'school_year_id' => $values['school_year_id'],
            'semester_id' => $values['semester_id'],
            'section_id' => $values['section_id'],
        ])) {
            return null;
        }
        $db = db_connect();
        if ($db->table('year')->where('year_id', $values['year_id'])->countAllResults() === 0
            || $db->table('school_year')->where('school_year_id', $values['school_year_id'])->countAllResults() === 0
            || $db->table('semesters')->where('semester_id', $values['semester_id'])->countAllResults() === 0
            || $db->table('sections')->where('sections_id', $values['section_id'])->countAllResults() === 0) {
            return null;
        }

        return [
            'program_id' => (int) $assignment['program_id'],
            'assigned_course_id' => (int) $courseAssignment['assigned_course_id'],
            'year_id' => $values['year_id'],
            'school_year_id' => $values['school_year_id'],
            'semester_id' => $values['semester_id'],
            'section_id' => $values['section_id'],
        ];
    }

    private function authenticatedRole(): ?string
    {
        $user = auth()->user();
        if ($user === null || preg_match('/^(admin|instructor|student)_([1-9]\d*)$/', (string) $user->username, $matches) !== 1) {
            return null;
        }

        return $user->inGroup($matches[1]) ? $matches[1] : null;
    }

    private function authenticatedAccountId(): int
    {
        $user = auth()->user();
        if ($user === null || preg_match('/^(admin|instructor|student)_([1-9]\d*)$/', (string) $user->username, $matches) !== 1) {
            return 0;
        }

        return $this->authenticatedRole() === $matches[1] ? (int) $matches[2] : 0;
    }

    /**
     * Keeps common page data defined for each destination view.
     *
     * @return array<string, mixed>
     */
    private function dashboardViewData(string $role, string $page, string $title): array
    {
        return [
            'role' => $role,
            'page' => $page,
            'title' => $title,
            'resource' => null,
            'record' => [],
            'columns' => [],
            'labels' => [],
            'rows' => [],
            'records' => [],
            'deletedRows' => [],
            'enrolments' => [],
            'assignments' => [],
            'lookup' => [
                'programs' => [],
                'courses' => [],
                'years' => [],
                'school_years' => [],
                'semesters' => [],
                'sections' => [],
                'instructors' => [],
                'levels' => [],
            ],
        ];
    }

    private function accountTableAndKey(string $role): array
    {
        return match ($role) {
            'admin' => ['admin_accounts', 'admin_id'],
            'student' => ['students', 'student_id'],
            'instructor' => ['instructors', 'instructor_id'],
            default => ['', ''],
        };
    }

    private function hasDependents(string $resource, int $id): bool
    {
        $db = db_connect();
        if ($resource === 'programs') {
            return $db->table('enrolments')->where('program_id', $id)->countAllResults() > 0
                || $db->table('attendance_schedules')->where('program_id', $id)->countAllResults() > 0
                || $db->table('faculty f')
                    ->join('programs_assignment pa', 'pa.assigned_program_id = f.assigned_program_id')
                    ->where('pa.program_id', $id)->countAllResults() > 0;
        }
        if ($resource === 'courses') {
            return $db->table('attendance_schedules')->where('course_id', $id)->countAllResults() > 0
                || $db->table('enrolments e')
                    ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
                    ->where('ca.course_id', $id)->countAllResults() > 0
                || $db->table('faculty f')
                    ->join('courses_assignment ca', 'ca.assigned_course_id = f.assigned_course_id')
                    ->where('ca.course_id', $id)->countAllResults() > 0;
        }
        if ($resource === 'instructors') {
            return $db->table('faculty')->where('instructor_id', $id)->countAllResults() > 0;
        }
        return false;
    }

    private function validTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) === 1;
    }

    private function landingPath(string $role, ?string $resource = null): string
    {
        if ($resource !== null) {
            return ($role === 'admin' ? 'myadmin/' : 'instructor/') . $resource;
        }
        return match ($role) {
            'admin' => 'myadmin/attendance',
            'instructor' => 'instructor/attendance',
            default => 'student/attendance',
        };
    }

    private function pageTitle(string $role, string $page): string
    {
        if ($role === 'student') {
            return 'Show Attendance';
        }
        if ($page === 'attendance') {
            return $role === 'admin' ? 'Instructors Attendance' : 'Students Attendance';
        }
        return ucfirst($page);
    }
}
