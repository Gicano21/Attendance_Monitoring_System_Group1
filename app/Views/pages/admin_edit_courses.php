<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Edit Courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Edit Courses</h1>
<button type="button" onclick="window.location.href='<?= base_url('/myadmin/view-courses') ?>'">Cancel</button>
<form action="<?= base_url('/myadmin/update-courses') ?>" method="post">
    <?= csrf_field() ?>
    <button type="submit">Save Courses</button>
    <table>
        <tr>
            <th>Course ID</th>
            <th>Course Acronym</th>
            <th>Course Name</th>
        </tr>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= $course['course_id'] ?><input type="hidden" name="course_id[]" value="<?= esc($course['course_id']) ?>"></td>
                <td><input type="text" name="course_acronym[]" value="<?= esc($course['course_acronym']) ?>" maxlength="8" required></td>
                <td><input type="text" name="course[]" value="<?= esc($course['course']) ?>" maxlength="100" required></td>
            </tr>
        <?php endforeach; ?>
    </table>
</form>
<?= $this->endSection() ?>