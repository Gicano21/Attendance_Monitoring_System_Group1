<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<section class="welcome">
    <div class="eyebrow">Lyceum Of St. Dominic</div>
    <h1>Attendance Monitoring System</h1>
    <p>Login to access your account.</p>
    <div class="role-grid">
        <a class="role-card" href="<?= site_url('student') ?>">
            <span class="role-icon"><img src="<?= base_url('svg/study-group-icon.svg') ?>" alt=""></span>
            <span><strong>Student</strong><small>View your attendance record</small></span>
            <span class="arrow">→</span>
        </a>
        <a class="role-card" href="<?= site_url('instructor') ?>">
            <span class="role-icon"><img src="<?= base_url('svg/training-online-icon.svg') ?>" alt=""></span>
            <span><strong>Instructor</strong><small>Manage class and attendance</small></span>
            <span class="arrow">→</span>
        </a>
    </div>
</section>
<?= $this->endSection() ?>
