<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Add Student<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>Add Student</h1>
<?php if (session()->getFlashdata('error')): ?>
    <p role="alert"><?= esc(session()->getFlashdata('error')) ?></p>
<?php endif; ?>
<form action="<?= base_url('myadmin/save_student') ?>" method="post">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Student Details</legend>
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" maxlength="50" required><br>
        <label for="middle_name">Middle Name:</label>
        <input type="text" id="middle_name" name="middle_name" maxlength="50"><br>
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" maxlength="50" required><br>
        <label for="extension_name">Extension Name:</label>
        <input type="text" id="extension_name" name="extension_name" maxlength="50"><br>
        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select><br>
        <label for="birth_date">Birth Date:</label>
        <input type="date" id="birth_date" name="birth_date" required><br>
        <label for="password">Student Password:</label>
        <input type="password" id="password" name="password" maxlength="50" required autocomplete="new-password"><br>
    </fieldset>
    <fieldset>
        <legend>Enrollment Details</legend>
        <label for="program_id">Program:</label>
        <select id="program_id" name="program_id" required>
            <option value="">Select Program</option>
            <?php foreach ($programs as $program): ?>
                <option value="<?= esc($program['program_id']) ?>"><?= esc($program['program_acronym'] . ' - ' . $program['program']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="year_id">Academic Year Level:</label>
        <select id="year_id" name="year_id" required>
            <option value="">Select Year Level</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= esc($year['year_id']) ?>"><?= esc($year['year']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="school_year_id">School Year:</label>
        <select id="school_year_id" name="school_year_id" required>
            <option value="">Select School Year</option>
            <?php foreach ($school_years as $schoolYear): ?>
                <option value="<?= esc($schoolYear['school_year_id']) ?>"><?= esc($schoolYear['year']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="semester_id">Semester:</label>
        <select id="semester_id" name="semester_id" required>
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?= esc($semester['semester_id']) ?>"><?= esc($semester['semester']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="section_id">Section:</label>
        <select id="section_id" name="section_id" required>
            <option value="">Select Section</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= esc($section['sections_id']) ?>"><?= esc($section['section']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <fieldset>
            <legend>Student Course Assignment</legend>
            <?php foreach ($course_assignments as $assignment): ?>
                <label>
                    <input type="radio" name="assigned_course_id" value="<?= esc($assignment['assigned_course_id']) ?>" required>
                    <?= esc($assignment['label']) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
    </fieldset>
    <button type="submit">Add Student</button>
</form>
<p><a href="<?= base_url('myadmin/enrolled-students') ?>">Back to Enrolled Students</a></p>
<?= $this->endSection() ?>