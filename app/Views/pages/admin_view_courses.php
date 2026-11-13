<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>View Courses<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>View Courses</h1>
<button>New Course</button>
<button onclick="window.location.href='<?= base_url('myadmin/edit-courses') ?>'">Edit Course</button>
<button>Remove Course</button>
<table>
    <tr>
        <th>Course ID</th>
        <th>Course Name</th>
    </tr>
    <?php foreach ($courses as $course):  ?>
        <tr>
            <td><?= $course['course_acronym'] ?></td>
            <td><?= $course['course'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?= $this->endSection() ?>