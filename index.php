<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';

$statistics = ['continentes' => 0, 'paises' => 0, 'cidades' => 0, 'governantes' => 0];
$topCities = [];
$citiesByContinent = [];
$countryHighlights = [];

try {
    $statistics = $pdo->query("SELECT
        (SELECT COUNT(*) FROM continentes) AS continentes,
        (SELECT COUNT(*) FROM paises) AS paises,
        (SELECT COUNT(*) FROM cidades) AS cidades,
        (SELECT COUNT(*) FROM governantes) AS governantes")->fetch() ?: $statistics;

    $topCities = $pdo->query("SELECT c.nome, c.populacao, p.nome AS pais_nome
        FROM cidades c
        INNER JOIN paises p ON p.id = c.pais_id
        ORDER BY c.populacao DESC, c.nome ASC
        LIMIT 5")->fetchAll();

    $citiesByContinent = $pdo->query("SELECT co.nome, COUNT(ci.id) AS total_cidades
        FROM continentes co
        LEFT JOIN paises p ON p.continente_id = co.id
        LEFT JOIN cidades ci ON ci.pais_id = p.id
        GROUP BY co.id, co.nome
        ORDER BY total_cidades DESC, co.nome ASC")->fetchAll();

    $countryHighlights = $pdo->query("SELECT p.nome,
        (SELECT COUNT(*) FROM cidades c WHERE c.pais_id = p.id) AS total_cidades,
        (SELECT c.nome FROM cidades c WHERE c.pais_id = p.id ORDER BY c.populacao DESC, c.nome ASC LIMIT 1) AS cidade_mais_populosa
        FROM paises p
        ORDER BY total_cidades DESC, p.nome ASC
        LIMIT 5")->fetchAll();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $dashboardError = 'Algumas estatísticas não puderam ser carregadas.';
}

render_header('Visão geral', 'dashboard');
?>
<section class="dashboard-intro">
    <div>
        <p class="eyebrow">Painel geográfico</p>
        <h1>Seu mapa de dados começa aqui.</h1>
        <p>Cadastre continentes, países, cidades e governantes em uma base organizada e conectada.</p>
    </div>
    <div class="quick-actions" aria-label="Ações rápidas">
        <a class="button button-primary" href="<?= e(form_url('paises')) ?>">+ Novo país</a>
        <a class="button button-secondary" href="<?= e(form_url('cidades')) ?>">+ Nova cidade</a>
    </div>
</section>

<?php if (isset($dashboardError)): ?>
    <div class="flash flash-error" role="alert"><?= e($dashboardError) ?></div>
<?php endif; ?>

<section class="stats-grid" aria-label="Resumo dos cadastros">
    <a class="stat-card" href="<?= e(url('continentes.php')) ?>">
        <span class="stat-label">Continentes</span>
        <strong><?= number_format((int) $statistics['continentes'], 0, ',', '.') ?></strong>
        <span>Gerenciar registros →</span>
    </a>
    <a class="stat-card" href="<?= e(url('paises.php')) ?>">
        <span class="stat-label">Países</span>
        <strong><?= number_format((int) $statistics['paises'], 0, ',', '.') ?></strong>
        <span>Gerenciar registros →</span>
    </a>
    <a class="stat-card" href="<?= e(url('cidades.php')) ?>">
        <span class="stat-label">Cidades</span>
        <strong><?= number_format((int) $statistics['cidades'], 0, ',', '.') ?></strong>
        <span>Gerenciar registros →</span>
    </a>
    <a class="stat-card" href="<?= e(url('governantes.php')) ?>">
        <span class="stat-label">Governantes</span>
        <strong><?= number_format((int) $statistics['governantes'], 0, ',', '.') ?></strong>
        <span>Gerenciar registros →</span>
    </a>
</section>

<section class="dashboard-grid">
    <article class="content-card dashboard-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Destaque</p>
                <h2>Cidades mais populosas</h2>
            </div>
            <a href="<?= e(url('cidades.php')) ?>">Ver cidades</a>
        </div>
        <?php if ($topCities === []): ?>
            <p class="muted">Cadastre cidades para ver este ranking.</p>
        <?php else: ?>
            <ol class="rank-list">
                <?php foreach ($topCities as $index => $city): ?>
                    <li>
                        <span class="rank-number"><?= $index + 1 ?></span>
                        <span><strong><?= e($city['nome']) ?></strong><small><?= e($city['pais_nome']) ?></small></span>
                        <span><?= e(format_population($city['populacao'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </article>

    <article class="content-card dashboard-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Distribuição</p>
                <h2>Cidades por continente</h2>
            </div>
            <a href="<?= e(url('continentes.php')) ?>">Ver continentes</a>
        </div>
        <?php if ($citiesByContinent === []): ?>
            <p class="muted">Cadastre continentes e cidades para ver esta estatística.</p>
        <?php else: ?>
            <ul class="metric-list">
                <?php foreach ($citiesByContinent as $continent): ?>
                    <li><span><?= e($continent['nome']) ?></span><strong><?= number_format((int) $continent['total_cidades'], 0, ',', '.') ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<section class="content-card dashboard-panel country-summary">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Consulta rápida</p>
            <h2>Cidade mais populosa por país</h2>
        </div>
        <a href="<?= e(url('paises.php')) ?>">Ver países</a>
    </div>
    <?php if ($countryHighlights === []): ?>
        <p class="muted">Cadastre países e cidades para identificar os destaques de cada país.</p>
    <?php else: ?>
        <div class="highlight-grid">
            <?php foreach ($countryHighlights as $country): ?>
                <article class="highlight-item">
                    <span><?= e($country['nome']) ?></span>
                    <strong><?= e($country['cidade_mais_populosa'] ?: 'Sem cidades cadastradas') ?></strong>
                    <small><?= number_format((int) $country['total_cidades'], 0, ',', '.') ?> <?= (int) $country['total_cidades'] === 1 ? 'cidade cadastrada' : 'cidades cadastradas' ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
