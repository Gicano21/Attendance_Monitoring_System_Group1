<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/page-heading') ?>
<?php $recordsTitle = 'Your attendance history'; ?>
<?php $recordsDescription = 'Attendance scans associated with your account.'; ?>
<?= $this->include('partials/attendance-records') ?>
<?= $this->endSection() ?>
