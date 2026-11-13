<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Add Instructor<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Add Instructor</h1>
<?php if (session()->getFlashdata('error')): ?>
    <p role="alert"><?= esc(session()->getFlashdata('error')) ?></p>
<?php endif; ?>
<form action="<?= base_url('myadmin/save_instructor') ?>" method="post">
    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" required><br>
    <label for="middle_name">Middle Name:</label>
    <input type="text" id="middle_name" name="middle_name"><br>
    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" required><br>
    <label for="extension_name">Extension Name:</label>
    <input type="text" id="extension_name" name="extension_name"><br>
    <label for="gender">Gender:</label>
    <select id="gender" name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select><br>
    <label for="birth_date">Birth Date:</label>
    <input type="date" id="birth_date" name="birth_date" required><br>
    <button type="submit">Add Instructor</button>
</form>

<?= $this->endSection() ?>