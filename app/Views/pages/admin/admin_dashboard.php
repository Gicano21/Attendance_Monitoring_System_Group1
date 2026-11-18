<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Admin Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>


<h1>Welcome to the Admin Dashboard</h1>
<button onclick="window.location.href='<?= base_url('/myadmin/faculty') ?>'">Go to Faculty Page</button>
<button onclick="window.location.href='<?= base_url('/myadmin/enrolled-students') ?>'">Go to Enrolled Students Page</button>
<button onclick="window.location.href='<?= base_url('/myadmin/instructor-attendance') ?>'">Go to Instructor Attendance Page</button>
<button onclick="window.location.href='<?= base_url('/myadmin/change-authentication') ?>'">Change Authentication</button>


<?= $this->endSection() ?>