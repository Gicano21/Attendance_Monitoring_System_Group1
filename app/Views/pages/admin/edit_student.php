<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Edit Student<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>Edit Student</h1>
<?php if (session()->getFlashdata('error')): ?>
    <p role="alert"><?= esc(session()->getFlashdata('error')) ?></p>
<?php endif; ?>
<form action="<?= base_url('myadmin/update_student/' . $student['student_id']) ?>" method="post">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Student Details</legend>
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?= esc($student['first_name']) ?>" maxlength="50" required><br>
        <label for="middle_name">Middle Name:</label>
        <input type="text" id="middle_name" name="middle_name" value="<?= esc($student['middle_name']) ?>" maxlength="50"><br>
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?= esc($student['last_name']) ?>" maxlength="50" required><br>
        <label for="extension_name">Extension Name:</label>
        <input type="text" id="extension_name" name="extension_name" value="<?= esc($student['extension_name']) ?>" maxlength="50"><br>
        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
        </select><br>
        <label for="birth_date">Birth Date:</label>
        <input type="date" id="birth_date" name="birth_date" value="<?= esc($student['birth_date']) ?>" required><br>
        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" id="password" name="password" maxlength="50" autocomplete="new-password"><br>
    </fieldset>
    <fieldset>
        <legend>Current Enrollments</legend>
        <?php if ($enrolments === []): ?>
            <p>This student has no enrollment records.</p>
        <?php endif; ?>
        <?php foreach ($enrolments as $enrollment): ?>
            <?php $enrollmentId = (string) $enrollment['enrolment_id']; ?>
            <fieldset>
                <legend><?= esc(implode(', ', $enrollment['courses'])) ?></legend>
                <label for="program_<?= esc($enrollmentId) ?>">Program:</label>
                <select id="program_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][program_id]" required>
                    <?php foreach ($programs as $program): ?>
                        <option value="<?= esc($program['program_id']) ?>" <?= (int) $enrollment['program_id'] === (int) $program['program_id'] ? 'selected' : '' ?>><?= esc($program['program_acronym'] . ' - ' . $program['program']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="year_<?= esc($enrollmentId) ?>">Academic Year Level:</label>
                <select id="year_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][year_id]" required>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= esc($year['year_id']) ?>" <?= (int) $enrollment['year_id'] === (int) $year['year_id'] ? 'selected' : '' ?>><?= esc($year['year']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="course_<?= esc($enrollmentId) ?>">Course Assignment:</label>
                <select id="course_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][assigned_course_id]" required>
                    <?php foreach ($course_assignments as $assignment): ?>
                        <option value="<?= esc($assignment['assigned_course_id']) ?>" <?= (int) $enrollment['assigned_course_id'] === (int) $assignment['assigned_course_id'] ? 'selected' : '' ?>><?= esc($assignment['label']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="school_year_<?= esc($enrollmentId) ?>">School Year:</label>
                <select id="school_year_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][school_year_id]" required>
                    <?php foreach ($school_years as $schoolYear): ?>
                        <option value="<?= esc($schoolYear['school_year_id']) ?>" <?= (int) $enrollment['school_year_id'] === (int) $schoolYear['school_year_id'] ? 'selected' : '' ?>><?= esc($schoolYear['year']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="semester_<?= esc($enrollmentId) ?>">Semester:</label>
                <select id="semester_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][semester_id]" required>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?= esc($semester['semester_id']) ?>" <?= (int) $enrollment['semester_id'] === (int) $semester['semester_id'] ? 'selected' : '' ?>><?= esc($semester['semester']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="section_<?= esc($enrollmentId) ?>">Section:</label>
                <select id="section_<?= esc($enrollmentId) ?>" name="enrolments[<?= esc($enrollmentId) ?>][section_id]" required>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= esc($section['sections_id']) ?>" <?= $enrollment['section_id'] === $section['sections_id'] ? 'selected' : '' ?>><?= esc($section['section']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label>
                    <input type="checkbox" name="remove_enrolment_ids[]" value="<?= esc($enrollmentId) ?>">
                    Remove this enrollment
                </label>
            </fieldset>
        <?php endforeach; ?>
    </fieldset>
    <fieldset>
        <legend>Add Enrollment(s)</legend>
        <label for="new_program_id">Program:</label>
        <select id="new_program_id" name="new_program_id">
            <option value="">Select Program</option>
            <?php foreach ($programs as $program): ?>
                <option value="<?= esc($program['program_id']) ?>"><?= esc($program['program_acronym'] . ' - ' . $program['program']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="new_year_id">Academic Year Level:</label>
        <select id="new_year_id" name="new_year_id">
            <option value="">Select Year Level</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= esc($year['year_id']) ?>"><?= esc($year['year']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="new_school_year_id">School Year:</label>
        <select id="new_school_year_id" name="new_school_year_id">
            <option value="">Select School Year</option>
            <?php foreach ($school_years as $schoolYear): ?>
                <option value="<?= esc($schoolYear['school_year_id']) ?>"><?= esc($schoolYear['year']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="new_semester_id">Semester:</label>
        <select id="new_semester_id" name="new_semester_id">
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?= esc($semester['semester_id']) ?>"><?= esc($semester['semester']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="new_section_id">Section:</label>
        <select id="new_section_id" name="new_section_id">
            <option value="">Select Section</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= esc($section['sections_id']) ?>"><?= esc($section['section']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <fieldset>
            <legend>Student Course Assignment</legend>
            <?php foreach ($course_assignments as $assignment): ?>
                <label>
                    <input type="radio" name="new_assigned_course_id" value="<?= esc($assignment['assigned_course_id']) ?>">
                    <?= esc($assignment['label']) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
    </fieldset>
    <button type="submit">Update Student</button>
</form>
<p><a href="<?= base_url('myadmin/enrolled-students') ?>">Back to Enrolled Students</a></p>
<?= $this->endSection() ?>