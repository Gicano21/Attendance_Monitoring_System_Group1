<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/page-heading') ?>
<?php if ($page === 'edit'): ?>
    <?= $this->include('partials/resource-management') ?>
<?php else: ?>
<section class="panel table-panel">
    <div class="section-title"><div><h2>My class roster</h2><p class="muted">Students already enrolled in your assigned Program, Course, Year Level, Semester, and Section appear here.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>ID</th><th>Student</th><th>Program</th><th>Course</th><th>Year level</th><th>Semester</th><th>Section</th><th>Gender</th><th>Birth date</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><tr><td><?= esc($row['id']) ?></td><td><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= esc($row['program_acronym']) ?></td><td><?= esc($row['course_acronym']) ?></td><td><?= esc($row['year']) ?></td><td><?= esc($row['semester']) ?></td><td><?= esc($row['section']) ?></td><td><?= esc($row['gender']) ?></td><td><?= esc($row['birth_date']) ?></td><td class="actions"><a class="text-link" href="<?= site_url('instructor/students/edit/' . $row['id']) ?>">Edit</a><form method="post" action="<?= site_url('instructor/students/delete/' . $row['id']) ?>" onsubmit="return confirm('Remove this student from your assigned roster?')"><?= csrf_field() ?><button class="text-button text-danger" type="submit">Remove</button></form></td></tr><?php endforeach ?>
        <?php if ($rows === []): ?><tr><td colspan="10" class="empty">No students are enrolled in your assigned classes.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php endif ?>
<?= $this->endSection() ?>
