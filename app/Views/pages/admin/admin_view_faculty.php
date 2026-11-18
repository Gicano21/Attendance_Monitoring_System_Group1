<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>View Faculty<?= $this->endSection() ?>

<?= $this->section('content') ?>


<h1>Faculty List</h1><br>
<button onclick="window.location.href='<?= base_url('myadmin/add_instructor') ?>'">Add New Instructor</button><br><br>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Gender</th>
            <th>Birth Date</th>
            <th>Instructor Level</th>
            <th>On Campus</th>
            <th>More Info</th>
            <th>Delete</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($instructors as $instructor): ?>
            <?php
            $name = implode(' ', array_filter([
                trim($instructor['first_name']),
                trim($instructor['middle_name']),
                trim($instructor['last_name']),
                trim($instructor['extension_name']),
            ]));
            ?>
            <tr>
                <td><?= esc($name) ?></td>
                <td><?= esc($instructor['gender']) ?></td>
                <td><?= esc($instructor['birth_date']) ?></td>
                <td><?= esc($instructor['instructor_level'] ?? 'Not assigned') ?></td>
                <td><?= $instructor['in_campus'] ? 'Yes' : 'No' ?></td>
                <td><a href="<?= base_url('myadmin/view_instructor_info/' . $instructor['instructor_id']) ?>">View</a></td>
                <td><a href="<?= base_url('myadmin/delete_instructor/' . $instructor['instructor_id']) ?>" onclick="return confirm('Are you sure you want to delete this instructor?')">Delete</a></td>
                <td><a href="<?= base_url('myadmin/edit_instructor/' . $instructor['instructor_id']) ?>">Update</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->endSection() ?>