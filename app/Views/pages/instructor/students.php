<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/page-heading') ?>
<?php if ($page === 'edit'): ?>
    <?= $this->include('partials/resource-management') ?>
<?php else: ?>
<section class="panel">
    <h2>Add student to your class roster</h2>
    <?php if ($assignments === []): ?>
        <p class="empty">There are no assigned Program/Course pairs available.</p>
    <?php else: ?>
        <form method="post" action="<?= site_url('instructor/students/save') ?>" class="form-grid">
            <?= csrf_field() ?><input type="hidden" name="id" value="0">
            <label>Program and course<select id="roster-assignment" required><option value="">Choose your assignment</option>
                <?php foreach ($assignments as $assignment): ?><option value="<?= esc($assignment['faculty_id'] . '|' . $assignment['program_id'] . '|' . $assignment['course_id']) ?>"><?= esc($assignment['program_acronym'] . ' · ' . $assignment['course_acronym']) ?></option><?php endforeach ?>
            </select></label>
            <input type="hidden" name="faculty_id" id="roster-faculty"><input type="hidden" name="program_id" id="roster-program"><input type="hidden" name="course_id" id="roster-course">
            <label>First name<input name="first_name" required></label><label>Last name<input name="last_name" required></label>
            <label>Middle name<input name="middle_name"></label><label>Name extension<input name="extension_name"></label>
            <label>Gender<select name="gender" required><option value="">Choose gender</option><option value="Male">Male</option><option value="Female">Female</option></select></label><label>Birth date<input type="date" name="birth_date" required></label>
            <label>Initial password<input name="password" type="password" minlength="8" required autocomplete="new-password"></label>
            <label>Year level<select name="year_id" required><option value="">Choose a year</option><?php foreach ($lookup['years'] as $option): ?><option value="<?= esc($option['year_id']) ?>"><?= esc($option['year']) ?></option><?php endforeach ?></select></label>
            <label>School year<select name="school_year_id" required><option value="">Choose a school year</option><?php foreach ($lookup['school_years'] as $option): ?><option value="<?= esc($option['school_year_id']) ?>"><?= esc($option['year']) ?></option><?php endforeach ?></select></label>
            <label>Semester<select name="semester_id" required><option value="">Choose a semester</option><?php foreach ($lookup['semesters'] as $option): ?><option value="<?= esc($option['semester_id']) ?>"><?= esc($option['semester']) ?></option><?php endforeach ?></select></label>
            <label>Section<select name="section_id" required><option value="">Choose a section</option><?php foreach ($lookup['sections'] as $option): ?><option value="<?= esc($option['sections_id']) ?>"><?= esc($option['section']) ?></option><?php endforeach ?></select></label>
            <div class="form-actions"><button class="button button-primary" type="submit">Add student</button></div>
        </form>
        <script>
            document.getElementById('roster-assignment').addEventListener('change', function () {
                const values = this.value.split('|');
                document.getElementById('roster-faculty').value = values[0] || '';
                document.getElementById('roster-program').value = values[1] || '';
                document.getElementById('roster-course').value = values[2] || '';
            });
        </script>
    <?php endif ?>
</section>
<section class="panel table-panel">
    <div class="section-title"><div><h2>My class roster</h2><p class="muted">Students enrolled in your assigned Program/Course pairs.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>ID</th><th>Student</th><th>Gender</th><th>Birth date</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><tr><td><?= esc($row['id']) ?></td><td><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= esc($row['gender']) ?></td><td><?= esc($row['birth_date']) ?></td><td class="actions"><a class="text-link" href="<?= site_url('instructor/students/edit/' . $row['id']) ?>">Edit</a><form method="post" action="<?= site_url('instructor/students/delete/' . $row['id']) ?>" onsubmit="return confirm('Remove this student from your assigned roster?')"><?= csrf_field() ?><button class="text-button text-danger" type="submit">Remove</button></form></td></tr><?php endforeach ?>
        <?php if ($rows === []): ?><tr><td colspan="5" class="empty">No students are enrolled in your assigned classes.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php endif ?>
<?= $this->endSection() ?>
