<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>View Programs<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1>View Programs</h1>
<button>New Program</button>
<button onclick="window.location.href='<?= base_url('myadmin/edit-programs') ?>'">Edit Program</button>
<button>Remove Program</button>
<table>
    <tr>
        <th>Program ID</th>
        <th>Program Name</th>
    </tr>
    <?php foreach ($programs as $program):  ?>
        <tr>
            <td><?= $program['program_acronym'] ?></td>
            <td><?= $program['program'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?= $this->endSection() ?>