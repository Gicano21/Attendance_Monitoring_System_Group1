<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAttendanceScheduleDayOfWeek extends Migration
{
    public function up()
    {
        $this->forge->addColumn('attendance_schedules', [
            'day_of_week' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'comment' => 'ISO-8601 weekday: Monday=1 through Sunday=7; NULL for legacy one-time schedules.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('attendance_schedules', 'day_of_week');
    }
}
