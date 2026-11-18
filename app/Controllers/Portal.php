<?php

namespace App\Controllers;

class Portal extends BaseController
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

        if (session()->get('role') === $role) {
            return redirect()->to($this->landingPath($role));
        }

        return view('pages/login', [
            'role' => $role,
            'action' => $role === 'admin' ? site_url('myadmin/login') : site_url($role . '/login'),
        ]);
    }

    public function authenticate(string $role)
    {
        if (!in_array($role, ['admin', 'student', 'instructor'], true)) {
            return redirect()->to('/');
        }

        $table = $role === 'admin' ? 'admin_accounts' : ($role === 'student' ? 'students' : 'instructors');
        $key = $role === 'admin' ? 'username' : ($role === 'student' ? 'student_id' : 'instructor_id');
        $identifier = trim((string) $this->request->getPost('identifier'));
        $password = (string) $this->request->getPost('password');
        $account = db_connect()->table($table)->where($key, $identifier)->get()->getRowArray();

        if ($account === null || !$this->passwordMatches((string) $account['password'], $password)) {
            return redirect()->back()->withInput()->with('error', 'The account ID or password is incorrect.');
        }

        if (password_get_info((string) $account['password'])['algo'] === null) {
            db_connect()->table($table)
                ->where($key, $account[$key])
                ->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $id = (int) $account[$role === 'admin' ? 'admin_id' : ($role === 'student' ? 'student_id' : 'instructor_id')];
        $name = $role === 'admin'
            ? $account['username']
            : trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''));
        session()->regenerate();
        session()->set(['role' => $role, 'user_id' => $id, 'display_name' => $name]);

        return redirect()->to($this->landingPath($role));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }

    public function dashboard(string $page = 'attendance')
    {
        $role = (string) session()->get('role');
        if (!in_array($role, ['admin', 'student', 'instructor'], true)) {
            return redirect()->to('/');
        }
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
                ->orderBy('i.last_name')->get()->getResultArray();
            $data['records'] = $this->attendanceRecords();
        } elseif ($page === 'attendance') {
            $data['records'] = $this->attendanceRecords($role, (int) session()->get('user_id'));
            if ($role === 'instructor') {
                $data['rows'] = db_connect()->table('attendance_schedules s')
                    ->select('s.*, p.program_acronym, c.course_acronym')
                    ->join('faculty f', 'f.faculty_id = s.faculty_id')
                    ->join('programs p', 'p.program_id = s.program_id')
                    ->join('courses c', 'c.course_id = s.course_id')
                    ->where('f.instructor_id', session()->get('user_id'))
                    ->orderBy('s.day_of_week', 'ASC')
                    ->orderBy('s.starts_at', 'ASC')->get()->getResultArray();
            }
        } elseif ($role === 'instructor' && $page === 'students') {
            $data['rows'] = $this->instructorRoster((int) session()->get('user_id'));
            $data['assignments'] = $this->instructorAssignments((int) session()->get('user_id'));
            $data['lookup'] = $this->enrolmentOptions();
        } else {
            $config = self::RESOURCE_FIELDS[$page];
            $data['resource'] = $page;
            $data['columns'] = $config['fields'];
            $data['labels'] = $config['labels'];
            $data['rows'] = db_connect()->table($config['table'])
                ->orderBy($config['primary'], 'DESC')->get()->getResultArray();
            if ($page === 'students') {
                $data['lookup'] = $this->enrolmentOptions();
            } elseif ($page === 'instructors') {
                $data['assignments'] = $this->facultyAssignments();
                $data['lookup'] = $this->facultyAssignmentOptions();
            }
        }

        return view('pages/' . $role . '/dashboard', $data);
    }

    public function editResource(string $resource, int $id)
    {
        $role = (string) session()->get('role');
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }

        if ($role === 'instructor' && !$this->instructorCanManageStudent($id, (int) session()->get('user_id'))) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }

        $record = db_connect()->table($config['table'])
            ->where($config['primary'], $id)->get()->getRowArray();
        if ($record === null) {
            return redirect()->to($this->landingPath($role))->with('error', 'The selected record no longer exists.');
        }

        $data = $this->dashboardViewData($role, 'edit', 'Edit ' . rtrim(ucfirst($resource), 's'));
        $data['resource'] = $resource;
        $data['columns'] = $config['fields'];
        $data['labels'] = $config['labels'];
        $data['record'] = $record;

        return view('pages/' . $role . '/dashboard', $data);
    }

    public function saveResource(string $resource)
    {
        $role = (string) session()->get('role');
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }

        $id = (int) $this->request->getPost('id');
        if ($role === 'instructor' && $id > 0 && !$this->instructorCanManageStudent($id, (int) session()->get('user_id'))) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }

        $values = [];
        foreach ($config['fields'] as $field) {
            $values[$field] = trim((string) $this->request->getPost($field));
        }
        $required = match ($resource) {
            'programs' => ['program_acronym', 'program'],
            'courses' => ['course_acronym', 'course'],
            default => ['first_name', 'last_name', 'gender', 'birth_date'],
        };
        foreach ($required as $field) {
            if ($values[$field] === '') {
                return redirect()->back()->withInput()->with('error', 'Complete all required fields before saving.');
            }
        }
        if (isset($values['birth_date']) && !$this->validDate($values['birth_date'])) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid birth date.');
        }
        if (isset($values['gender']) && strlen($values['gender']) > 6) {
            return redirect()->back()->withInput()->with('error', 'Gender must be 6 characters or fewer in the current database schema.');
        }

        $isAccount = in_array($resource, ['students', 'instructors'], true);
        if ($isAccount && $id === 0) {
            $password = (string) $this->request->getPost('password');
            if (strlen($password) < 8) {
                return redirect()->back()->withInput()->with('error', 'Initial passwords must contain at least 8 characters.');
            }
            $values['password'] = password_hash($password, PASSWORD_DEFAULT);
            $values['in_campus'] = false;
        } elseif ($isAccount) {
            $password = (string) $this->request->getPost('password');
            if (strlen($password) > 0) {
                if (strlen($password) < 8) {
                    return redirect()->back()->withInput()->with('error', 'Passwords must contain at least 8 characters.');
                }
                $values['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        $db = db_connect();
        if ($id > 0) {
            $builder = $db->table($config['table']);
            $builder->where($config['primary'], $id)->update($values);
        } else {
            $db->transStart();
            $db->table($config['table'])->insert($values);
            $studentId = (int) $db->insertID();
            if ($resource === 'students') {
                $courseId = (int) $this->request->getPost('course_id');
                $programId = (int) $this->request->getPost('program_id');
                $assignment = $this->validStudentAssignment($role, $courseId, $programId);
                $enrolment = $this->enrolmentValues($assignment);
                if ($assignment === null || $enrolment === null) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Choose a valid assigned Program/Course and complete the enrolment fields.');
                }
                $enrolment['student_id'] = $studentId;
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
        $role = (string) session()->get('role');
        if ($role !== 'admin' && !($role === 'instructor' && $resource === 'students')) {
            return redirect()->to('/');
        }

        $config = self::RESOURCE_FIELDS[$resource] ?? null;
        if ($config === null) {
            return redirect()->to($this->landingPath($role));
        }
        if ($role === 'instructor' && !$this->instructorCanManageStudent($id, (int) session()->get('user_id'))) {
            return redirect()->to('instructor/students')->with('error', 'That student is not in your assigned roster.');
        }

        if ($role === 'instructor' && $resource === 'students') {
            $assignments = $this->instructorAssignments((int) session()->get('user_id'));
            $conditions = [];
            foreach ($assignments as $assignment) {
                $conditions[] = '(e.program_id = ' . (int) $assignment['program_id']
                    . ' AND ca.course_id = ' . (int) $assignment['course_id'] . ')';
            }
            if ($conditions !== []) {
                $enrolments = db_connect()->query(
                    'SELECT e.enrolment_id FROM enrolments e JOIN courses_assignment ca ON ca.assigned_course_id = e.assigned_course_id '
                    . 'WHERE e.student_id = ? AND ca.role = ? AND (' . implode(' OR ', $conditions) . ')',
                    [$id, 'student']
                )->getResultArray();
                $enrolmentIds = array_column($enrolments, 'enrolment_id');
                if ($enrolmentIds !== []) {
                    db_connect()->table('enrolments')->whereIn('enrolment_id', $enrolmentIds)->delete();
                }
            }
            return redirect()->to('instructor/students')->with('success', 'Student removed from your assigned roster.');
        }

        if ($this->hasDependents($resource, $id)) {
            return redirect()->to($this->landingPath($role, $resource))
                ->with('error', 'This record is assigned to active students, instructors, or attendance windows and cannot be deleted.');
        }
        db_connect()->table($config['table'])->where($config['primary'], $id)->delete();
        if (db_connect()->error()['code'] !== 0) {
            return redirect()->to($this->landingPath($role, $resource))
                ->with('error', 'This record is still in use and cannot be deleted.');
        }
        return redirect()->to($this->landingPath($role, $resource))->with('success', 'Deleted successfully.');
    }

    public function schedules()
    {
        if (session()->get('role') !== 'instructor') {
            return redirect()->to('/');
        }

        $assignments = $this->instructorAssignments((int) session()->get('user_id'));
        $rows = db_connect()->table('attendance_schedules s')
            ->select('s.*, p.program_acronym, c.course_acronym')
            ->join('faculty f', 'f.faculty_id = s.faculty_id')
            ->join('programs p', 'p.program_id = s.program_id')
            ->join('courses c', 'c.course_id = s.course_id')
            ->where('f.instructor_id', session()->get('user_id'))
            ->orderBy('s.day_of_week', 'ASC')
            ->orderBy('s.starts_at', 'ASC')->get()->getResultArray();

        $data = $this->dashboardViewData('instructor', 'schedules', 'Set Attendance');
        $data['assignments'] = $assignments;
        $data['rows'] = $rows;

        return view('pages/instructor/dashboard', $data);
    }

    public function saveFacultyAssignment()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $instructorId = (int) $this->request->getPost('instructor_id');
        $programId = (int) $this->request->getPost('program_id');
        $courseId = (int) $this->request->getPost('course_id');
        $levelId = (int) $this->request->getPost('instructor_level_id');
        $db = db_connect();

        if ($db->table('instructors')->where('instructor_id', $instructorId)->countAllResults() === 0
            || $db->table('programs')->where('program_id', $programId)->countAllResults() === 0
            || $db->table('courses')->where('course_id', $courseId)->countAllResults() === 0
            || $db->table('instructor_levels')->where('instructor_level_id', $levelId)->countAllResults() === 0) {
            return redirect()->to('myadmin/instructors')->with('error', 'Choose an existing instructor, level, program, and course.');
        }

        $programAssignmentId = $this->ensureProgramAssignment($programId);
        $instructorCourseAssignmentId = $this->ensureCourseAssignment($courseId, 'instructor');
        $this->ensureCourseAssignment($courseId, 'student');

        $exists = $db->table('faculty')
            ->where('instructor_id', $instructorId)
            ->where('assigned_program_id', $programAssignmentId)
            ->where('assigned_course_id', $instructorCourseAssignmentId)
            ->countAllResults();
        if ($exists > 0) {
            return redirect()->to('myadmin/instructors')->with('error', 'This instructor assignment already exists.');
        }

        $db->table('faculty')->insert([
            'instructor_id' => $instructorId,
            'instructor_level_id' => $levelId,
            'assigned_program_id' => $programAssignmentId,
            'assigned_course_id' => $instructorCourseAssignmentId,
        ]);
        if ($db->error()['code'] !== 0) {
            return redirect()->to('myadmin/instructors')->with('error', 'The instructor assignment could not be saved.');
        }
        return redirect()->to('myadmin/instructors')->with('success', 'Instructor assigned to the Program/Course.');
    }

    public function deleteFacultyAssignment(int $facultyId)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        if (db_connect()->table('attendance_schedules')->where('faculty_id', $facultyId)->countAllResults() > 0) {
            return redirect()->to('myadmin/instructors')->with('error', 'This assignment has attendance schedules and cannot be removed.');
        }
        db_connect()->table('faculty')->where('faculty_id', $facultyId)->delete();
        return redirect()->to('myadmin/instructors')->with('success', 'Instructor assignment removed.');
    }

    public function saveSchedule()
    {
        if (session()->get('role') !== 'instructor') {
            return redirect()->to('/');
        }

        $facultyId = (int) $this->request->getPost('faculty_id');
        $programId = (int) $this->request->getPost('program_id');
        $courseId = (int) $this->request->getPost('course_id');
        $day = filter_var($this->request->getPost('day_of_week'), FILTER_VALIDATE_INT);
        $start = (string) $this->request->getPost('starts_at');
        $end = (string) $this->request->getPost('ends_at');
        $assignment = null;
        foreach ($this->instructorAssignments((int) session()->get('user_id')) as $candidate) {
            if ((int) $candidate['faculty_id'] === $facultyId
                && (int) $candidate['program_id'] === $programId
                && (int) $candidate['course_id'] === $courseId) {
                $assignment = $candidate;
                break;
            }
        }

        if ($assignment === null || $day === false || $day < 1 || $day > 7
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
        if (session()->get('role') !== $role) {
            return redirect()->to('/');
        }
        return view('pages/' . $role . '/dashboard', $this->dashboardViewData($role, 'authentication', 'Change Authentication'));
    }

    public function updateAuthentication(string $role)
    {
        if (session()->get('role') !== $role) {
            return redirect()->to('/');
        }

        [$table, $key] = $this->accountTableAndKey($role);
        $account = db_connect()->table($table)
            ->where($key, session()->get('user_id'))->get()->getRowArray();
        $current = (string) $this->request->getPost('current_password');
        $new = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');

        if ($account === null || !$this->passwordMatches((string) $account['password'], $current)) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }
        if (strlen($new) < 8 || $new !== $confirm) {
            return redirect()->back()->with('error', 'New passwords must match and contain at least 8 characters.');
        }

        db_connect()->table($table)
            ->where($key, session()->get('user_id'))
            ->update(['password' => password_hash($new, PASSWORD_DEFAULT)]);
        return redirect()->to($this->landingPath($role))->with('success', 'Password updated.');
    }

    public function scanQrCode()
    {
        $deviceKey = env('ATTENDANCE_DEVICE_API_KEY');
        if (!is_string($deviceKey) || strlen($deviceKey) < 32) {
            return $this->response->setStatusCode(503)->setJSON([
                'ok' => false,
                'message' => 'Attendance device endpoint is not configured.',
            ]);
        }

        $providedKey = $this->request->getHeaderLine('X-Device-Key');
        if ($providedKey === '' || !hash_equals($deviceKey, $providedKey)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => false,
                'message' => 'Attendance device authentication failed.',
            ]);
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'JSON must include id and role.']);
        }

        $role = strtolower((string) ($payload['role'] ?? $payload['Role'] ?? ''));
        $role = rtrim($role, 's');
        $idValue = $payload['id'] ?? $payload['ID'] ?? null;
        $id = filter_var($idValue, FILTER_VALIDATE_INT);
        if (!in_array($role, ['student', 'instructor'], true) || $id === false || $id < 1) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Invalid participant ID or role.']);
        }

        $db = db_connect();
        $table = $role === 'student' ? 'students' : 'instructors';
        $key = $role === 'student' ? 'student_id' : 'instructor_id';
        $person = $db->table($table)->where($key, $id)->get()->getRowArray();
        if ($person === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Participant not found.']);
        }

        $now = date('Y-m-d H:i:s');
        $schedule = $this->activeSchedule($role, (int) $id, $now);
        if ($schedule === null) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'No matching attendance window is active.']);
        }

        $current = (bool) $person['in_campus'];
        $direction = $current ? 'OUT' : 'IN';
        if ($role === 'instructor' && !$current) {
            $alreadyCheckedIn = $db->table('attendance_records')
                ->where('participant_role', 'instructor')
                ->where('participant_id', $id)
                ->where('direction', 'IN')
                ->where('scanned_at >=', date('Y-m-d 00:00:00'))
                ->where('scanned_at <=', date('Y-m-d 23:59:59'))
                ->countAllResults() > 0;
            if ($alreadyCheckedIn) {
                return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Instructor attendance is limited to one check-in per day.']);
            }
        }

        $db->transStart();
        $db->table($table)->where($key, $id)->where('in_campus', $current)->update(['in_campus' => !$current]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Attendance was just updated. Please scan again.']);
        }
        $db->table('attendance_records')->insert([
            'participant_role' => $role,
            'participant_id' => $id,
            'schedule_id' => $schedule['schedule_id'],
            'direction' => $direction,
            'scanned_at' => $now,
        ]);
        if ($role === 'student' && $direction === 'IN') {
            $db->table('student_time_ins')->insert([
                'enrolment_id' => $schedule['enrolment_id'],
                'program_id' => $schedule['program_id'],
                'course_id' => $schedule['course_id'],
                'school_year_id' => $schedule['school_year_id'],
                'semester_id' => $schedule['semester_id'],
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
            ]);
        } elseif ($role === 'instructor' && $direction === 'IN') {
            $db->table('instructor_time_in')->insert([
                'faculty_id' => $schedule['faculty_id'],
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
            ]);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'message' => 'Attendance could not be recorded.']);
        }

        return $this->response->setJSON([
            'ok' => true,
            'role' => $role,
            'id' => (int) $id,
            'status' => $current ? 'out' : 'in',
            'scanned_at' => $now,
        ]);
    }

    private function activeSchedule(string $role, int $id, string $now): ?array
    {
        $db = db_connect();
        $today = substr($now, 0, 10);
        $clock = substr($now, 11, 8);
        $weekday = (int) date('N', strtotime($today));

        if ($role === 'instructor') {
            return $db->table('attendance_schedules s')
                ->select('s.*')
                ->join('faculty f', 'f.faculty_id = s.faculty_id')
                ->where('f.instructor_id', $id)
                ->groupStart()
                    ->where('s.day_of_week', $weekday)
                    ->orGroupStart()
                        ->where('s.day_of_week', null)
                        ->where('s.schedule_date', $today)
                    ->groupEnd()
                ->groupEnd()
                ->where('s.starts_at <=', $clock)
                ->where('s.ends_at >=', $clock)
                ->orderBy('s.starts_at')
                ->get()->getRowArray();
        }

        return $db->table('attendance_schedules s')
            ->select('s.*, e.enrolment_id, e.school_year_id, e.semester_id')
            ->join('enrolments e', 'e.program_id = s.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
            ->where('e.student_id', $id)
            ->where('ca.course_id = s.course_id', null, false)
            ->where('ca.role', 'student')
            ->groupStart()
                ->where('s.day_of_week', $weekday)
                ->orGroupStart()
                    ->where('s.day_of_week', null)
                    ->where('s.schedule_date', $today)
                ->groupEnd()
            ->groupEnd()
            ->where('s.starts_at <=', $clock)
            ->where('s.ends_at >=', $clock)
            ->orderBy('s.starts_at')
            ->get()->getRowArray();
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
            ->select('f.faculty_id, p.program_id, p.program_acronym, c.course_id, c.course_acronym')
            ->join('programs_assignment pa', 'pa.assigned_program_id = f.assigned_program_id')
            ->join('programs p', 'p.program_id = pa.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = f.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->where('f.instructor_id', $instructorId)
            ->where('ca.role', 'instructor')
            ->get()->getResultArray();
    }

    private function facultyAssignments(): array
    {
        return db_connect()->table('faculty f')
            ->select('f.faculty_id, i.first_name, i.last_name, il.instructor_level, p.program_acronym, c.course_acronym')
            ->join('instructors i', 'i.instructor_id = f.instructor_id')
            ->join('instructor_levels il', 'il.instructor_level_id = f.instructor_level_id')
            ->join('programs_assignment pa', 'pa.assigned_program_id = f.assigned_program_id')
            ->join('programs p', 'p.program_id = pa.program_id')
            ->join('courses_assignment ca', 'ca.assigned_course_id = f.assigned_course_id')
            ->join('courses c', 'c.course_id = ca.course_id')
            ->orderBy('i.last_name')->get()->getResultArray();
    }

    private function facultyAssignmentOptions(): array
    {
        $db = db_connect();
        return [
            'instructors' => $db->table('instructors')->orderBy('last_name')->get()->getResultArray(),
            'levels' => $db->table('instructor_levels')->get()->getResultArray(),
            'programs' => $db->table('programs')->orderBy('program_acronym')->get()->getResultArray(),
            'courses' => $db->table('courses')->orderBy('course_acronym')->get()->getResultArray(),
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

        $conditions = [];
        foreach ($assignments as $assignment) {
            $conditions[] = '(e.program_id = ' . (int) $assignment['program_id']
                . ' AND ca.course_id = ' . (int) $assignment['course_id'] . ')';
        }

        return db_connect()->query(
            'SELECT DISTINCT st.student_id AS id, st.first_name, st.last_name, st.gender, st.birth_date '
            . 'FROM students st JOIN enrolments e ON e.student_id = st.student_id '
            . 'JOIN courses_assignment ca ON ca.assigned_course_id = e.assigned_course_id '
            . 'WHERE ca.role = ? AND (' . implode(' OR ', $conditions) . ') ORDER BY st.last_name, st.first_name',
            ['student']
        )->getResultArray();
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
            'programs' => $db->table('programs')->orderBy('program_acronym')->get()->getResultArray(),
            'courses' => $db->table('courses c')
                ->select('c.course_id, c.course_acronym, c.course')
                ->distinct()
                ->join('courses_assignment ca', 'ca.course_id = c.course_id')
                ->where('ca.role', 'student')
                ->orderBy('c.course_acronym')->get()->getResultArray(),
            'years' => $db->table('year')->get()->getResultArray(),
            'school_years' => $db->table('school_year')->get()->getResultArray(),
            'semesters' => $db->table('semesters')->get()->getResultArray(),
            'sections' => $db->table('sections')->get()->getResultArray(),
        ];
    }

    private function validStudentAssignment(string $role, int $courseId, int $programId): ?array
    {
        if ($courseId < 1 || $programId < 1) {
            return null;
        }

        if ($role === 'instructor') {
            foreach ($this->instructorAssignments((int) session()->get('user_id')) as $assignment) {
                if ((int) $assignment['course_id'] === $courseId && (int) $assignment['program_id'] === $programId) {
                    return $assignment;
                }
            }
            return null;
        }

        $programExists = db_connect()->table('programs')->where('program_id', $programId)->countAllResults() > 0;
        $courseExists = db_connect()->table('courses_assignment')
            ->where('course_id', $courseId)->where('role', 'student')->countAllResults() > 0;
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
        if ($courseAssignment === null
            || $values['year_id'] < 1
            || $values['school_year_id'] < 1
            || $values['semester_id'] < 1
            || $values['section_id'] === '') {
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

    private function passwordMatches(string $stored, string $provided): bool
    {
        if ($stored === '' || $provided === '') {
            return false;
        }
        if (password_get_info($stored)['algo'] !== null) {
            return password_verify($provided, $stored);
        }
        return hash_equals($stored, $provided);
    }

    /**
     * Keeps the dashboard view's list and record variables defined on every page.
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

    private function validDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
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
