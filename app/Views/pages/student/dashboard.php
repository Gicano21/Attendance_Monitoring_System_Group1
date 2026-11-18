<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/page-heading') ?>

<?php if ($page === 'authentication'): ?>
    <?= $this->include('partials/authentication-form') ?>
<?php elseif ($page === 'attendance'): ?>
    <?php $recordsTitle = 'Your attendance history'; ?>
    <?php $recordsDescription = 'Attendance scans associated with your account.'; ?>
    <?= $this->include('partials/attendance-records') ?>
<?php endif ?>
<?= $this->endSection() ?>
