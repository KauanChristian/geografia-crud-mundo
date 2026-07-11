<?php
declare(strict_types=1);

function render_header(string $title, string $active = ''): void
{
    $navigation = [
        'dashboard' => ['label' => 'Visão geral', 'path' => 'index.php'],
        'continentes' => ['label' => 'Continentes', 'path' => 'continentes.php'],
        'paises' => ['label' => 'Países', 'path' => 'paises.php'],
        'cidades' => ['label' => 'Cidades', 'path' => 'cidades.php'],
        'governantes' => ['label' => 'Governantes', 'path' => 'governantes.php'],
    ];
    $flashes = pull_flashes();
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de gerenciamento geográfico de continentes, países, cidades e governantes.">
    <title><?= e($title) ?> · Mundo</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="Mundo — página inicial">
            <span class="brand-mark" aria-hidden="true">◉</span>
            <span>Mundo</span>
        </a>
        <form class="global-search" action="<?= e(url('busca.php')) ?>" method="get" data-global-search>
            <label class="visually-hidden" for="global-search-input">Buscar país ou cidade</label>
            <input id="global-search-input" name="q" type="search" placeholder="Buscar país ou cidade" autocomplete="off" data-global-search-input>
            <div class="search-results" data-search-results hidden aria-live="polite"></div>
        </form>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-menu-toggle>
            <span class="visually-hidden">Abrir navegação</span>
            <span aria-hidden="true">☰</span>
        </button>
    </div>
    <nav id="primary-navigation" class="primary-navigation" aria-label="Navegação principal" data-primary-navigation>
        <div class="nav-inner">
            <?php foreach ($navigation as $key => $item): ?>
                <a class="nav-link<?= $active === $key ? ' is-active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<main id="conteudo" class="main-content">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="status">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="flash-close" data-dismiss-flash aria-label="Fechar mensagem">×</button>
        </div>
    <?php endforeach; ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="site-footer">
    <p>Projeto acadêmico de gerenciamento geográfico · <?= date('Y') ?></p>
</footer>
<script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
