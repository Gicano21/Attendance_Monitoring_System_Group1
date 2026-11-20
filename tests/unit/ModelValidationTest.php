<?php

use App\Models\AttendanceScheduleModel;
use App\Models\ProgramModel;
use App\Models\StudentModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ModelValidationTest extends CIUnitTestCase
{
    public function testProgramRequiresItsCodeAndName(): void
    {
        $this->assertFalse($this->validateModelRules(ProgramModel::class, ['program_acronym' => '', 'program' => '']));
        $this->assertTrue($this->validateModelRules(ProgramModel::class, [
            'program_acronym' => 'BSCS',
            'program' => 'Computer Science',
        ]));
    }

    public function testStudentRejectsInvalidBirthDatesAndOversizedGenderValues(): void
    {
        $validProfile = [
            'first_name' => 'Avery',
            'last_name' => 'Student',
            'gender' => 'Female',
            'birth_date' => '2005-04-15',
        ];

        $this->assertTrue($this->validateModelRules(StudentModel::class, $validProfile));
        $this->assertFalse($this->validateModelRules(StudentModel::class, array_merge($validProfile, ['birth_date' => '2005-02-31'])));
        $this->assertFalse($this->validateModelRules(StudentModel::class, array_merge($validProfile, ['gender' => 'TooLongValue'])));
    }

    public function testAttendanceScheduleRequiresAValidWeekdayAndTimes(): void
    {
        $validSchedule = [
            'faculty_id' => 1,
            'program_id' => 1,
            'course_id' => 1,
            'day_of_week' => 5,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
        ];

        $this->assertTrue($this->validateModelRules(AttendanceScheduleModel::class, $validSchedule));
        $this->assertFalse($this->validateModelRules(AttendanceScheduleModel::class, array_merge($validSchedule, ['day_of_week' => 8])));
        $this->assertFalse($this->validateModelRules(AttendanceScheduleModel::class, array_merge($validSchedule, ['starts_at' => ''])));
    }

    private function validateModelRules(string $modelClass, array $data): bool
    {
        $model = (new ReflectionClass($modelClass))->newInstanceWithoutConstructor();
        $rules = new ReflectionProperty($model, 'validationRules');
        $rules->setAccessible(true);

        return service('validation')
            ->reset()
            ->setRules($rules->getValue($model))
            ->run($data);
    }
}
