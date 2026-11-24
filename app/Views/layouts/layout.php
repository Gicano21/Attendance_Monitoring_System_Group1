<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($this->renderSection('title') ?: ($title ?? 'Attendance Monitor')) ?> | Group 1</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <?= $this->include('partials/navbar') ?>
    <?php $authSessionField = config('Auth')->sessionConfig['field']; ?>
    <?php $authSession = session($authSessionField) ?? []; ?>
    <main class="<?= isset($authSession['id']) ? 'app-main' : 'public-main' ?>">
        <?php if ($message = session()->getFlashdata('success')): ?>
            <div class="notice notice-success"><?= esc($message) ?></div>
        <?php endif ?>
        <?php if ($message = session()->getFlashdata('error')): ?>
            <div class="notice notice-error"><?= esc($message) ?></div>
        <?php endif ?>
        <?= $this->renderSection('content') ?>
    </main>
    <?= $this->include('partials/footer') ?>
</body>
</html>