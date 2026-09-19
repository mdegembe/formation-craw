<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\Session;
use Craw\Core\View;

/**
 * Ecran de connexion : gabarit autonome, sans la navigation du back-office.
 *
 * @var array<string, string> $errors
 * @var array<string, mixed>  $old
 */
$flashes = Session::takeFlashes();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Administration</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('craw-theme');
                var dark = stored
                    ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="text-center mb-3">
            <span class="brand__mark" aria-hidden="true"
                  style="display:inline-grid;width:3.5rem;height:3.5rem;font-size:0.9rem;">CRA-W</span>
            <h1 class="mt-2" style="font-size: 1.5rem;">Administration</h1>
            <p class="text-muted small mb-0">Gestion de la formation</p>
        </div>

        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert--<?= e((string) $flash['type']) ?>" role="alert">
                <div class="alert__body"><?= e((string) $flash['message']) ?></div>
            </div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('/admin/connexion')) ?>" data-once>
            <?= Csrf::field() ?>

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input class="form-control" type="email" id="email" name="email"
                           value="<?= e((string) ($old['email'] ?? '')) ?>"
                           required autocomplete="username" autofocus
                        <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
                </div>

                <div class="form-field">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input class="form-control" type="password" id="password" name="password"
                           required autocomplete="current-password"
                        <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'password']) ?>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--pill w-100 mt-3">Se connecter</button>
        </form>

        <p class="small text-muted text-center mt-3 mb-0">
            Compte oublié ou verrouillé&nbsp;? Un administrateur peut le réinitialiser avec
            <code>php bin/console.php create-admin</code>.
        </p>

        <p class="small text-center mt-3 mb-0">
            <a href="<?= e(url('/')) ?>">← Retour au site</a>
        </p>
    </div>
</div>
</body>
</html>
