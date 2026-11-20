<section class="login-panel">
    <a class="back-link" href="<?= site_url('/') ?>">← All account types</a>
    <div class="eyebrow"><?= esc(ucfirst($role)) ?> Portal</div>
    <h1><?= $role === 'admin' ? 'Administrator sign in' : ucfirst($role) . ' sign in' ?></h1>
    <p class="muted">Use your <?= $role === 'admin' ? 'admin username' : 'account ID' ?> and password.</p>
    <form method="post" action="<?= esc($action) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label><?= $role === 'admin' ? 'Username' : 'Account ID' ?>
            <input name="identifier" type="<?= $role === 'admin' ? 'text' : 'number' ?>" required autofocus value="<?= esc(old('identifier')) ?>">
        </label>
        <label>Password
            <input name="password" type="password" required autocomplete="current-password">
        </label>
        <button class="button button-primary" type="submit">Sign in</button>
    </form>
</section>
