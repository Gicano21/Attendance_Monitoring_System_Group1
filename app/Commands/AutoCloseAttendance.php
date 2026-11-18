<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;

class AutoCloseAttendance extends BaseCommand
{
    protected $group = 'Attendance';
    protected $name = 'attendance:auto-close';
    protected $description = 'Close attendance sessions that have passed their scheduled end time.';

    public function run(array $params)
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $time = date('H:i:s');
        $weekday = (int) date('N');
        $schedules = $db->table('attendance_schedules')
            ->groupStart()
                ->groupStart()
                    ->where('day_of_week', $weekday)
                    ->where('ends_at <=', $time)
                ->groupEnd()
                ->orGroupStart()
                    ->where('day_of_week', null)
                    ->where('schedule_date <=', $today)
                    ->groupStart()
                        ->where('schedule_date <', $today)
                        ->orGroupStart()
                            ->where('schedule_date', $today)
                            ->where('ends_at <=', $time)
                        ->groupEnd()
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->get()
            ->getResultArray();

        $closed = 0;
        foreach ($schedules as $schedule) {
            $faculty = $db->table('faculty')
                ->where('faculty_id', $schedule['faculty_id'])
                ->get()
                ->getRowArray();

            if ($faculty === null) {
                continue;
            }

            $closed += $this->closeParticipant(
                $db,
                'instructor',
                (int) $faculty['instructor_id'],
                (int) $schedule['schedule_id']
            );

            $students = $db->table('enrolments e')
                ->select('e.student_id')
                ->join('courses_assignment ca', 'ca.assigned_course_id = e.assigned_course_id')
                ->where('e.program_id', $schedule['program_id'])
                ->where('ca.course_id', $schedule['course_id'])
                ->where('ca.role', 'student')
                ->get()
                ->getResultArray();

            foreach ($students as $student) {
                $closed += $this->closeParticipant(
                    $db,
                    'student',
                    (int) $student['student_id'],
                    (int) $schedule['schedule_id']
                );
            }
        }

        CLI::write("Closed {$closed} attendance session(s).");
    }

    private function closeParticipant(BaseConnection $db, string $role, int $id, int $scheduleId): int
    {
        $table = $role === 'student' ? 'students' : 'instructors';
        $primaryKey = $role === 'student' ? 'student_id' : 'instructor_id';
        $participant = $db->table($table)
            ->select('in_campus')
            ->where($primaryKey, $id)
            ->get()
            ->getRowArray();

        if ($participant === null || (int) $participant['in_campus'] !== 1) {
            return 0;
        }

        $latestRecord = $db->table('attendance_records')
            ->select('schedule_id, direction')
            ->where('participant_role', $role)
            ->where('participant_id', $id)
            ->orderBy('scanned_at', 'DESC')
            ->orderBy('attendance_record_id', 'DESC')
            ->get(1)->getRowArray();

        if ($latestRecord === null
            || (int) $latestRecord['schedule_id'] !== $scheduleId
            || $latestRecord['direction'] !== 'IN') {
            return 0;
        }

        $db->transStart();
        $db->table($table)->where($primaryKey, $id)->update(['in_campus' => false]);
        $db->table('attendance_records')->insert([
            'participant_role' => $role,
            'participant_id' => $id,
            'schedule_id' => $scheduleId,
            'direction' => 'OUT',
            'scanned_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to close attendance for ' . $role . ' #' . $id);
        }

        return 1;
    }
}
