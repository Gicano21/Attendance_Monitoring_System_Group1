<?= $this->extend('layouts/admin_layout') ?>
<?= $this->section('title') ?>Edit Programs<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Edit Programs</h1>
<button type="button" onclick="window.location.href='<?= base_url('/myadmin/view-programs') ?>'">Cancel</button>
<form action="<?= base_url('/myadmin/update-programs') ?>" method="post">
    <?= csrf_field() ?>
    <button type="submit">Save Programs</button>
    <table>
        <tr>
            <th>Program ID</th>
            <th>Program Acronym</th>
            <th>Program Name</th>
        </tr>
        <?php foreach ($programs as $program): ?>
            <tr>
                <td><?= $program['program_id'] ?><input type="hidden" name="program_id[]" value="<?= esc($program['program_id']) ?>"></td>
                <td><input type="text" name="program_acronym[]" value="<?= esc($program['program_acronym']) ?>" maxlength="8" required></td>
                <td><input type="text" name="program[]" value="<?= esc($program['program']) ?>" maxlength="100" required></td>
            </tr>
        <?php endforeach; ?>
    </table>
</form>
<?= $this->endSection() ?>