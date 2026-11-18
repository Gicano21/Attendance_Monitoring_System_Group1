<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?php $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday']; ?>
<?= $this->include('partials/page-heading') ?>

<?php if ($page === 'authentication'): ?>
    <?= $this->include('partials/authentication-form') ?>

<?php elseif ($page === 'edit'): ?>
    <?= $this->include('partials/resource-management') ?>

<?php elseif ($page === 'schedules'): ?>
    <section class="panel">
        <h2>Open a class attendance window</h2>
        <p class="muted">Choose the weekly class day and time. ESP32 attendance submissions are accepted during this recurring window.</p>
        <?php if ($assignments === []): ?>
            <div class="empty">There are no Program/Course assignments for this instructor yet. Ask an administrator to configure the instructor's faculty assignment.</div>
        <?php else: ?>
            <form method="post" action="<?= site_url('instructor/schedules/save') ?>" class="form-grid">
                <?= csrf_field() ?>
                <label>Assigned Program &amp; Course
                    <select id="assignment-choice" required>
                        <option value="">Choose an assignment</option>
                        <?php foreach ($assignments as $assignment): ?>
                            <option value="<?= esc($assignment['faculty_id'] . '|' . $assignment['program_id'] . '|' . $assignment['course_id']) ?>">
                                <?= esc($assignment['program_acronym'] . ' · ' . $assignment['course_acronym']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </label>
                <input type="hidden" name="faculty_id" id="faculty-id">
                <input type="hidden" name="program_id" id="program-id">
                <input type="hidden" name="course_id" id="course-id">
                <label>Class Day
                    <select name="day_of_week" required>
                        <option value="">Choose a day</option>
                        <?php foreach ($dayNames as $dayNumber => $dayName): ?>
                            <option value="<?= $dayNumber ?>" <?= (string) old('day_of_week') === (string) $dayNumber ? 'selected' : '' ?>><?= esc($dayName) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label>Starts at<input type="time" name="starts_at" required value="<?= esc(old('starts_at')) ?>"></label>
                <label>Ends at<input type="time" name="ends_at" required value="<?= esc(old('ends_at')) ?>"></label>
                <div class="form-actions"><button class="button button-primary" type="submit">Save attendance window</button></div>
            </form>
        <?php endif ?>
    </section>
    <section class="panel table-panel">
        <div class="section-title"><h2>Weekly attendance windows</h2></div>
        <div class="table-wrap"><table><thead><tr><th>Class Day</th><th>Program</th><th>Course</th><th>Time</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?>
                <?php $dayLabel = $row['day_of_week'] === null ? date('l, Y-m-d', strtotime($row['schedule_date'])) : ($dayNames[(int) $row['day_of_week']] ?? 'Unknown day'); ?>
                <tr><td><?= esc($dayLabel) ?></td><td><?= esc($row['program_acronym']) ?></td><td><?= esc($row['course_acronym']) ?></td><td><?= esc(substr($row['starts_at'], 0, 5) . '–' . substr($row['ends_at'], 0, 5)) ?></td></tr>
            <?php endforeach ?>
            <?php if ($rows === []): ?><tr><td colspan="4" class="empty">No attendance windows have been set.</td></tr><?php endif ?>
        </tbody></table></div>
    </section>
    <script>
        document.getElementById('assignment-choice')?.addEventListener('change', function () {
            const values = this.value.split('|');
            document.getElementById('faculty-id').value = values[0] || '';
            document.getElementById('program-id').value = values[1] || '';
            document.getElementById('course-id').value = values[2] || '';
        });
    </script>

<?php elseif ($page === 'students'): ?>
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
                <label>Gender<input name="gender" maxlength="6" required></label><label>Birth date<input type="date" name="birth_date" required></label>
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

<?php elseif ($page === 'attendance'): ?>
    <section class="panel table-panel"><div class="section-title"><div><h2>Your attendance windows</h2><p class="muted">Set a schedule before students check in with the ESP32.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Class Day</th><th>Program</th><th>Course</th><th>Time</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?>
                <?php $dayLabel = $row['day_of_week'] === null ? date('l, Y-m-d', strtotime($row['schedule_date'])) : ($dayNames[(int) $row['day_of_week']] ?? 'Unknown day'); ?>
                <tr><td><?= esc($dayLabel) ?></td><td><?= esc($row['program_acronym']) ?></td><td><?= esc($row['course_acronym']) ?></td><td><?= esc(substr($row['starts_at'], 0, 5) . '–' . substr($row['ends_at'], 0, 5)) ?></td></tr>
            <?php endforeach ?>
            <?php if ($rows === []): ?><tr><td colspan="4" class="empty">No schedules yet. Set your first attendance window.</td></tr><?php endif ?>
        </tbody></table></div>
    </section>
    <?= $this->include('partials/attendance-records') ?>
<?php endif ?>
<?= $this->endSection() ?>
