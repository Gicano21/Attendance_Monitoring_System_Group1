<section class="panel narrow-panel">
    <h2>Update your password</h2>
    <p class="muted">Use at least 8 characters. Your new password is stored as a secure hash.</p>
    <form method="post" action="<?= site_url(($role === 'admin' ? 'myadmin' : $role) . '/change-authentication') ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
        <label>New password<input type="password" name="new_password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirm new password<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></label>
        <button class="button button-primary" type="submit">Update password</button>
    </form>
</section>
