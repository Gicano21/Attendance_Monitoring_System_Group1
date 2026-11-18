<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>View Instructor<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Instructor Information</h1>
<img src="no-image-for-a-moment.png" alt="Instructor Image" width="200" height="200"> <br>
<strong>Name:</strong> <?= esc($instructor['first_name'] . ' ' . $instructor['middle_name'] . ' ' . $instructor['last_name'] . ' ' . $instructor['extension_name']) ?><br>
<strong>Gender:</strong> <?= esc($instructor['gender']) ?><br>
<strong>Birth Date:</strong> <?= esc($instructor['birth_date']) ?><br>
<strong>Instructor Level:</strong> <?= esc($instructor['instructor_level'] ?? 'Not assigned') ?><br>
<strong>On Campus:</strong> <?= $instructor['in_campus'] ? 'Yes' : 'No' ?><br>
<strong>Assigned Courses:</strong>
<?php if ($assigned_courses === []): ?>
    Not assigned
<?php else: ?>
    <ul>
        <?php foreach ($assigned_courses as $course): ?>
            <li><?= esc($course['course']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<strong>Assigned Programs:</strong>
<?php if ($assigned_programs === []): ?>
    Not assigned
<?php else: ?>
    <ul>
        <?php foreach ($assigned_programs as $program): ?>
            <li><?= esc($program['program']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?= $this->endSection() ?>