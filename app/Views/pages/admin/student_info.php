<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Student Information<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>Student Information</h1>
<p><strong>Student ID:</strong> <?= esc($student['student_id']) ?></p>
<?php
$name = implode(' ', array_filter([
    trim($student['first_name']),
    trim($student['middle_name']),
    trim($student['last_name']),
    trim($student['extension_name']),
]));
?>
<p><strong>Name:</strong> <?= esc($name) ?></p>
<p><strong>Gender:</strong> <?= esc($student['gender']) ?></p>
<p><strong>Birth Date:</strong> <?= esc($student['birth_date']) ?></p>
<p><strong>On Campus:</strong> <?= $student['in_campus'] ? 'Yes' : 'No' ?></p>
<h2>Enrollments</h2>
<?php if ($enrolments === []): ?>
    <p>No enrollment records.</p>
<?php else: ?>
    <ul>
        <?php foreach ($enrolments as $enrollment): ?>
            <li>
                <strong>Courses:</strong> <?= esc(implode(', ', $enrollment['courses'])) ?> |
                <strong>Program:</strong> <?= esc($enrollment['program_acronym'] . ' - ' . $enrollment['program']) ?> |
                <strong>Year Level:</strong> <?= esc($enrollment['academic_year']) ?> |
                <strong>School Year:</strong> <?= esc($enrollment['school_year']) ?> |
                <strong>Semester:</strong> <?= esc($enrollment['semester']) ?> |
                <strong>Section:</strong> <?= esc($enrollment['section']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p><a href="<?= base_url('myadmin/edit_student/' . $student['student_id']) ?>">Update Student</a></p>
<p><a href="<?= base_url('myadmin/enrolled-students') ?>">Back to Enrolled Students</a></p>
<?= $this->endSection() ?>