<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/page-heading') ?>
<section class="stat-grid">
    <article class="stat-card"><span>Instructor accounts</span><strong><?= count($rows) ?></strong></article>
    <article class="stat-card"><span>Currently on campus</span><strong><?= count(array_filter($rows, static fn ($row) => (bool) $row['in_campus'])) ?></strong></article>
    <article class="stat-card"><span>Recent scan records</span><strong><?= count($records) ?></strong></article>
</section>
<section class="panel table-panel"><div class="section-title"><h2>Instructor campus status</h2></div>
    <div class="table-wrap"><table><thead><tr><th>ID</th><th>Instructor</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><tr><td><?= esc($row['id']) ?></td><td><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td><td><span class="status <?= $row['in_campus'] ? 'status-in' : 'status-out' ?>"><?= $row['in_campus'] ? 'On campus' : 'Off campus' ?></span></td></tr><?php endforeach ?>
    </tbody></table></div>
</section>
<?= $this->include('partials/attendance-records') ?>
<?= $this->endSection() ?>
