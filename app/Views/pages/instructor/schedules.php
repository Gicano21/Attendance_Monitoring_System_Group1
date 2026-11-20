<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?php $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday']; ?>
<?= $this->include('partials/page-heading') ?>
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
<?= $this->endSection() ?>
