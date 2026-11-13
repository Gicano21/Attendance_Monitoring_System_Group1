<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Change Authentication<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Change Authentication</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div style="color: red; margin-bottom: 1rem;">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div style="color: green; margin-bottom: 1rem;">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('/myadmin/update-authentication') ?>" method="post">
    <?= csrf_field() ?>

    <label for="current_username">Current Username</label><br>
    <input type="text" id="current_username" name="current_username" value="<?= old('current_username') ?? '' ?>" required><br><br>

    <label for="current_password">Current Password</label><br>
    <input type="password" id="current_password" name="current_password" value="<?= old('current_password') ?? '' ?>" required><br><br>

    <label for="new_username">New Username</label><br>
    <input type="text" id="new_username" name="new_username" value="<?= old('new_username') ?? '' ?>" required><br><br>

    <label for="new_password">New Password</label><br>
    <input type="password" id="new_password" name="new_password" value="<?= old('new_password') ?? '' ?>" required><br><br>
    <button type="submit">Save</button>
</form>

<?= $this->endSection() ?>