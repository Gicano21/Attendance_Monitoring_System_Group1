<div class="page-heading">
    <div><div class="eyebrow"><?= esc(ucfirst($role)) ?> workspace</div><h1><?= esc($title) ?></h1></div>
    <?php if ($role === 'instructor' && $page === 'attendance'): ?>
        <a class="button button-primary" href="<?= site_url('instructor/schedules') ?>">Set an attendance window</a>
    <?php endif ?>
</div>
