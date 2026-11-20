<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?php $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday']; ?>
<?= $this->include('partials/page-heading') ?>
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
<?= $this->endSection() ?>
