<?php $isEdit = $page === 'edit'; ?>
<?php $resourceName = $isEdit ? $resource : $page; ?>
<?php $config = [
    'programs' => ['program_id', 'Program'],
    'courses' => ['course_id', 'Course'],
    'students' => ['student_id', 'Student'],
    'instructors' => ['instructor_id', 'Instructor'],
][$resourceName]; ?>
<section class="panel resource-panel">
    <div class="section-title">
        <div><h2><?= $isEdit ? 'Edit ' . esc(rtrim($config[1], 's')) : 'Add ' . esc(rtrim($config[1], 's')) ?></h2><p class="muted">Manage <?= esc(strtolower($config[1])) ?> records.</p></div>
    </div>
    <form method="post" action="<?= site_url(($role === 'admin' ? 'myadmin' : 'instructor') . '/' . $resourceName . '/save') ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= esc($record[$config[0]] ?? 0) ?>">
        <?php foreach ($columns as $field): ?>
            <label><?= esc($labels[$field] ?? ucfirst(str_replace('_', ' ', $field))) ?>
                <input name="<?= esc($field) ?>" value="<?= esc(old($field, $record[$field] ?? '')) ?>" <?= in_array($field, ['first_name', 'last_name', 'birth_date', 'program', 'program_acronym', 'course', 'course_acronym'], true) ? 'required' : '' ?> <?= $field === 'birth_date' ? 'type="date"' : 'type="text"' ?>>
            </label>
        <?php endforeach ?>
        <?php if ($resourceName === 'students' && !$isEdit): ?>
            <label>Program<select name="program_id" required><option value="">Choose a program</option>
                <?php foreach ($lookup['programs'] as $option): ?><option value="<?= esc($option['program_id']) ?>"><?= esc($option['program_acronym'] . ' · ' . $option['program']) ?></option><?php endforeach ?>
            </select></label>
            <label>Course<select name="course_id" required><option value="">Choose a course</option>
                <?php foreach ($lookup['courses'] as $option): ?><option value="<?= esc($option['course_id']) ?>"><?= esc($option['course_acronym'] . ' · ' . $option['course']) ?></option><?php endforeach ?>
            </select></label>
            <label>Year level<select name="year_id" required><option value="">Choose a year</option>
                <?php foreach ($lookup['years'] as $option): ?><option value="<?= esc($option['year_id']) ?>"><?= esc($option['year']) ?></option><?php endforeach ?>
            </select></label>
            <label>School year<select name="school_year_id" required><option value="">Choose a school year</option>
                <?php foreach ($lookup['school_years'] as $option): ?><option value="<?= esc($option['school_year_id']) ?>"><?= esc($option['year']) ?></option><?php endforeach ?>
            </select></label>
            <label>Semester<select name="semester_id" required><option value="">Choose a semester</option>
                <?php foreach ($lookup['semesters'] as $option): ?><option value="<?= esc($option['semester_id']) ?>"><?= esc($option['semester']) ?></option><?php endforeach ?>
            </select></label>
            <label>Section<select name="section_id" required><option value="">Choose a section</option>
                <?php foreach ($lookup['sections'] as $option): ?><option value="<?= esc($option['sections_id']) ?>"><?= esc($option['section']) ?></option><?php endforeach ?>
            </select></label>
        <?php endif ?>
        <?php if (in_array($resourceName, ['students', 'instructors'], true)): ?>
            <label><?= $isEdit ? 'New password (optional)' : 'Initial password' ?>
                <input name="password" type="password" <?= $isEdit ? '' : 'required minlength="8"' ?> minlength="8" autocomplete="new-password">
            </label>
        <?php endif ?>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $isEdit ? 'Save changes' : 'Add record' ?></button>
            <?php if ($isEdit): ?><a class="button button-secondary" href="<?= site_url(($role === 'admin' ? 'myadmin' : 'instructor') . '/' . $resourceName) ?>">Cancel</a><?php endif ?>
        </div>
    </form>
</section>

<?php if (!$isEdit): ?>
    <section class="panel table-panel">
        <div class="section-title"><h2>Current <?= esc($config[1]) ?> records</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><?php foreach ($columns as $field): ?><th><?= esc($labels[$field]) ?></th><?php endforeach ?><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><td><?= esc($row[$config[0]]) ?></td><?php foreach ($columns as $field): ?><td><?= esc($row[$field]) ?></td><?php endforeach ?>
                    <td class="actions">
                        <a class="text-link" href="<?= site_url('myadmin/' . $resourceName . '/edit/' . $row[$config[0]]) ?>">Edit</a>
                        <form method="post" action="<?= site_url('myadmin/' . $resourceName . '/delete/' . $row[$config[0]]) ?>" onsubmit="return confirm('Delete this record?')"><?= csrf_field() ?><button class="text-button text-danger" type="submit">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if ($rows === []): ?><tr><td colspan="<?= count($columns) + 2 ?>" class="empty">No records yet.</td></tr><?php endif ?>
            </tbody>
        </table></div>
    </section>
<?php endif ?>

<?php if ($resourceName === 'instructors' && !$isEdit): ?>
    <section class="panel">
        <h2>Assign an instructor to a Program/Course</h2>
        <p class="muted">This creates or reuses the course and program assignment links needed for schedules and student enrolment.</p>
        <?php if ($lookup['instructors'] === [] || $lookup['levels'] === [] || $lookup['programs'] === [] || $lookup['courses'] === []): ?>
            <p class="empty">Add instructors, levels, programs, and courses before creating assignments.</p>
        <?php else: ?>
            <form method="post" action="<?= site_url('myadmin/assignments/save') ?>" class="form-grid">
                <?= csrf_field() ?>
                <label>Instructor<select name="instructor_id" required><option value="">Choose instructor</option><?php foreach ($lookup['instructors'] as $option): ?><option value="<?= esc($option['instructor_id']) ?>"><?= esc($option['first_name'] . ' ' . $option['last_name']) ?></option><?php endforeach ?></select></label>
                <label>Instructor level<select name="instructor_level_id" required><option value="">Choose level</option><?php foreach ($lookup['levels'] as $option): ?><option value="<?= esc($option['instructor_level_id']) ?>"><?= esc($option['instructor_level']) ?></option><?php endforeach ?></select></label>
                <label>Program<select name="program_id" required><option value="">Choose program</option><?php foreach ($lookup['programs'] as $option): ?><option value="<?= esc($option['program_id']) ?>"><?= esc($option['program_acronym'] . ' · ' . $option['program']) ?></option><?php endforeach ?></select></label>
                <label>Course<select name="course_id" required><option value="">Choose course</option><?php foreach ($lookup['courses'] as $option): ?><option value="<?= esc($option['course_id']) ?>"><?= esc($option['course_acronym'] . ' · ' . $option['course']) ?></option><?php endforeach ?></select></label>
                <div class="form-actions"><button class="button button-primary" type="submit">Save assignment</button></div>
            </form>
        <?php endif ?>
    </section>
    <section class="panel table-panel">
        <div class="section-title"><h2>Current instructor assignments</h2></div>
        <div class="table-wrap"><table><thead><tr><th>Instructor</th><th>Level</th><th>Program</th><th>Course</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($assignments as $assignment): ?><tr><td><?= esc($assignment['first_name'] . ' ' . $assignment['last_name']) ?></td><td><?= esc($assignment['instructor_level']) ?></td><td><?= esc($assignment['program_acronym']) ?></td><td><?= esc($assignment['course_acronym']) ?></td><td><form method="post" action="<?= site_url('myadmin/assignments/delete/' . $assignment['faculty_id']) ?>" onsubmit="return confirm('Remove this assignment?')"><?= csrf_field() ?><button type="submit" class="text-button text-danger">Remove</button></form></td></tr><?php endforeach ?>
            <?php if ($assignments === []): ?><tr><td colspan="5" class="empty">No instructor assignments yet.</td></tr><?php endif ?>
        </tbody></table></div>
    </section>
<?php endif ?>
