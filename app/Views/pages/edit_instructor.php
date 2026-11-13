<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Edit Instructor<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Edit Instructor</h1>
<?php if (session()->getFlashdata('error')): ?>
    <p role="alert"><?= esc(session()->getFlashdata('error')) ?></p>
<?php endif; ?>
<form action="<?= base_url('myadmin/update_instructor/' . $instructor['instructor_id']) ?>" method="post">
    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" value="<?= esc($instructor['first_name']) ?>" required><br>
    <label for="middle_name">Middle Name:</label>
    <input type="text" id="middle_name" name="middle_name" value="<?= esc($instructor['middle_name']) ?>"><br>
    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" value="<?= esc($instructor['last_name']) ?>" required><br>
    <label for="extension_name">Extension Name:</label>
    <input type="text" id="extension_name" name="extension_name" value="<?= esc($instructor['extension_name']) ?>"><br>
    <label for="gender">Gender:</label>
    <select id="gender" name="gender" required>
        <option value="Male" <?= $instructor['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $instructor['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
    </select><br>
    <label for="birth_date">Birth Date:</label>
    <input type="date" id="birth_date" name="birth_date" value="<?= esc($instructor['birth_date']) ?>" required><br>
    <label for="instructor_level_id">Instructor Level:</label>
    <select id="instructor_level_id" name="instructor_level_id" required>
        <?php foreach ($instructor_levels as $level): ?>
            <option value="<?= esc($level['instructor_level_id']) ?>" <?= (int) ($instructor['instructor_level_id'] ?? 0) === (int) $level['instructor_level_id'] ? 'selected' : '' ?>><?= esc($level['instructor_level']) ?></option>
        <?php endforeach; ?>
    </select><br>
    <fieldset>
        <legend>Assigned Courses</legend>
        <?php foreach ($assigned_courses as $course): ?>
            <?php $courseId = $course['course_id']; ?>
            <label>
                <input
                    type="checkbox"
                    name="course_ids[]"
                    value="<?= esc($courseId) ?>"
                    <?= in_array($courseId, $current_course_ids, true) ? 'checked' : '' ?>
                >
                <?= esc($course['course']) ?>
            </label><br>
        <?php endforeach; ?>
    </fieldset>
    <button type="submit">Update Instructor</button>
</form>

<?= $this->endSection() ?>