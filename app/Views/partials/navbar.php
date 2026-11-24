<?php
$authSessionField = config('Auth')->sessionConfig['field'];
$authSession = session($authSessionField) ?? [];
$authUser = isset($authSession['id']) ? auth()->user() : null;
$role = null;
if ($authUser !== null) {
    foreach (['admin', 'instructor', 'student'] as $candidateRole) {
        if ($authUser->inGroup($candidateRole)) {
            $role = $candidateRole;
            break;
        }
    }
}
?>
<?php if ($role !== null): ?>
    <header class="topbar">
        <a class="brand" href="<?= site_url('/') ?>"><span class="brand-mark">G1</span><span>Group 1<small>Attendance Monitoring System</small></span></a>
        <div class="topbar-user">
            <span><?= esc(session()->get('display_name')) ?></span>
            <a class="signout" href="<?= site_url('logout') ?>">Sign out</a>
        </div>
    </header>
    <nav class="side-nav" aria-label="Main navigation">
        <?php if ($role === 'admin'): ?>
            <a href="<?= site_url('myadmin/attendance') ?>">Instructors Attendance</a>
            <a href="<?= site_url('myadmin/instructors') ?>">Instructors</a>
            <a href="<?= site_url('myadmin/students') ?>">Enrolled Students</a>
            <a href="<?= site_url('myadmin/programs') ?>">Programs</a>
            <a href="<?= site_url('myadmin/courses') ?>">Courses</a>
            <a href="<?= site_url('myadmin/change-authentication') ?>">Change Authentication</a>
        <?php elseif ($role === 'instructor'): ?>
            <a href="<?= site_url('instructor/attendance') ?>">Students Attendance</a>
            <a href="<?= site_url('instructor/students') ?>">Students</a>
            <a href="<?= site_url('instructor/schedules') ?>">Set Attendance</a>
            <a href="<?= site_url('instructor/change-authentication') ?>">Change Authentication</a>
        <?php elseif ($role === 'student'): ?>
            <a href="<?= site_url('student/attendance') ?>">Show Attendance</a>
            <a href="<?= site_url('student/change-authentication') ?>">Change Authentication</a>
        <?php endif ?>
    </nav>
<?php endif ?>