<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AttendanceWorkflow extends Migration
{
    public function up()
    {
        foreach (['admin_accounts', 'students', 'instructors'] as $table) {
            $primaryKey = $table === 'admin_accounts' ? 'admin_id' : ($table === 'students' ? 'student_id' : 'instructor_id');
            $this->forge->modifyColumn($table, [
                'password' => ['name' => 'password', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            ]);

            foreach ($this->db->table($table)->get()->getResultArray() as $account) {
                $password = (string) ($account['password'] ?? '');
                if ($password !== '' && password_get_info($password)['algo'] === null) {
                    $this->db->table($table)
                        ->where($primaryKey, $account[$primaryKey])
                        ->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);
                }
            }
        }

        $this->forge->addField([
            'schedule_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'faculty_id' => ['type' => 'INT', 'constraint' => 10, 'null' => false],
            'program_id' => ['type' => 'INT', 'constraint' => 5, 'null' => false],
            'course_id' => ['type' => 'INT', 'constraint' => 5, 'null' => false],
            'schedule_date' => ['type' => 'DATE', 'null' => false],
            'starts_at' => ['type' => 'TIME', 'null' => false],
            'ends_at' => ['type' => 'TIME', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('schedule_id', true);
        $this->forge->addKey(['faculty_id', 'schedule_date']);
        $this->forge->addForeignKey('faculty_id', 'faculty', 'faculty_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('program_id', 'programs', 'program_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'course_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('attendance_schedules');

        $this->forge->addField([
            'attendance_record_id' => ['type' => 'INT', 'constraint' => 10, 'auto_increment' => true],
            'participant_role' => ['type' => 'VARCHAR', 'constraint' => 12],
            'participant_id' => ['type' => 'INT', 'constraint' => 10],
            'schedule_id' => ['type' => 'INT', 'constraint' => 10],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 3],
            'scanned_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('attendance_record_id', true);
        $this->forge->addKey(['participant_role', 'participant_id', 'scanned_at']);
        $this->forge->addForeignKey('schedule_id', 'attendance_schedules', 'schedule_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('attendance_records');
    }

    public function down()
    {
        $this->forge->dropTable('attendance_records');
        $this->forge->dropTable('attendance_schedules');
    }
}
