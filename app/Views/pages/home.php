<?= $this->extend('layouts/layout') ?>

<?= $this->section('content') ?>
<section class="welcome">
    <div class="eyebrow">Campus attendance, made clear</div>
    <h1>Welcome to CampusPulse</h1>
    <p>Choose your account type to view attendance or manage your class.</p>
    <div class="role-grid">
        <a class="role-card" href="<?= site_url('student') ?>">
            <span class="role-icon">S</span>
            <span><strong>Student</strong><small>View your attendance record</small></span>
            <span class="arrow">→</span>
        </a>
        <a class="role-card" href="<?= site_url('instructor') ?>">
            <span class="role-icon">I</span>
            <span><strong>Instructor</strong><small>Manage class and attendance</small></span>
            <span class="arrow">→</span>
        </a>
    </div>
</section>
<?= $this->endSection() ?>
