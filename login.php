<?php
declare(strict_types=1);

define('AUTH_PUBLIC_ROUTE', true);
require_once __DIR__ . '/backend/includes/bootstrap.php';

redirect_authenticated_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acesso ao sistema Mundo.">
    <title>Acessar · Mundo</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-panel" aria-labelledby="login-title">
        <a class="brand auth-brand" href="<?= e(url('login.php')) ?>" aria-label="Mundo — acesso">
            <span class="brand-mark" aria-hidden="true">◉</span>
            <span>Mundo</span>
        </a>
        <p class="eyebrow">Acesso restrito</p>
        <h1 id="login-title">Entre na sua conta</h1>
        <p class="auth-intro">Use suas credenciais para administrar os dados geográficos.</p>

        <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endforeach; ?>

        <form class="entity-form" action="<?= e(url('backend/actions/autenticar.php')) ?>" method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="login">Usuário</label>
                <input id="login" name="login" type="text" maxlength="50" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="senha">Senha</label>
                <input id="senha" name="senha" type="password" autocomplete="current-password" required>
            </div>
            <button class="button button-primary" type="submit">Entrar no sistema</button>
        </form>
        <p class="auth-help">Após três tentativas incorretas consecutivas, o acesso é bloqueado.</p>
    </section>
</main>
</body>
</html>
