<section class="panel table-panel">
    <div class="section-title"><div><h2><?= $recordsTitle ?? ($role === 'admin' ? 'Recent attendance scans' : 'Your recent attendance scans') ?></h2>
        <?php if (!empty($recordsDescription)): ?><p class="muted"><?= esc($recordsDescription) ?></p><?php endif ?>
    </div></div>
    <div class="table-wrap"><table><thead><tr><th>Person</th><th>Program</th><th>Course</th><th>Direction</th><th>Scanned at</th></tr></thead><tbody>
        <?php foreach ($records as $record): ?><tr><td><?= esc(ucfirst($record['participant_role']) . ' #' . $record['participant_id']) ?></td><td><?= esc($record['program_acronym']) ?></td><td><?= esc($record['course_acronym']) ?></td><td><span class="status <?= $record['direction'] === 'IN' ? 'status-in' : 'status-out' ?>"><?= esc($record['direction']) ?></span></td><td><?= esc($record['scanned_at']) ?></td></tr><?php endforeach ?>
        <?php if ($records === []): ?><tr><td colspan="5" class="empty">No attendance records yet.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
