<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Enrolled Students<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>Enrolled Students</h1>

<?php if (session()->getFlashdata('success')): ?>
    <p role="status"><?= esc(session()->getFlashdata('success')) ?></p>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <p role="alert"><?= esc(session()->getFlashdata('error')) ?></p>
<?php endif; ?>

<p><a href="<?= base_url('myadmin/add_student') ?>">Add Student</a></p>
<table>
    <thead>
        <tr>
            <th>Student</th>
            <th>Gender</th>
            <th>Birth Date</th>
            <th>Enrollment(s)</th>
            <th>On Campus</th>
            <th>More Info</th>
            <th>Update</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($students === []): ?>
            <tr><td colspan="8">No students found.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $student): ?>
            <?php
            $name = implode(' ', array_filter([
                trim($student['first_name']),
                trim($student['middle_name']),
                trim($student['last_name']),
                trim($student['extension_name']),
            ]));
            ?>
            <tr>
                <td><?= esc($name) ?></td>
                <td><?= esc($student['gender']) ?></td>
                <td><?= esc($student['birth_date']) ?></td>
                <td>
                    <?php if ($student['enrolments'] === []): ?>
                        Not enrolled
                    <?php else: ?>
                        <ul>
                            <?php foreach ($student['enrolments'] as $enrollment): ?>
                                <li>
                                    <?= esc(implode(', ', $enrollment['courses'])) ?>,
                                    <?= esc($enrollment['program_acronym']) ?>,
                                    <?= esc($enrollment['academic_year']) ?>,
                                    <?= esc($enrollment['school_year']) ?>,
                                    <?= esc($enrollment['semester']) ?>,
                                    <?= esc($enrollment['section']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
                <td><?= $student['in_campus'] ? 'Yes' : 'No' ?></td>
                <td><a href="<?= base_url('myadmin/view_student_info/' . $student['student_id']) ?>">View</a></td>
                <td><a href="<?= base_url('myadmin/edit_student/' . $student['student_id']) ?>">Update</a></td>
                <td>
                    <form action="<?= base_url('myadmin/delete_student/' . $student['student_id']) ?>" method="post" onsubmit="return confirm('Delete this student and all related enrollments?')">
                        <?= csrf_field() ?>
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>