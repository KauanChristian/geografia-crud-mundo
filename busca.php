<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';

/** @return array{paises: array<int, array<string, mixed>>, cidades: array<int, array<string, mixed>>} */
function search_geography(PDO $pdo, string $query): array
{
    $empty = ['paises' => [], 'cidades' => []];

    if (text_length($query) < 2) {
        return $empty;
    }

    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
    $term = '%' . $escaped . '%';

    $countryStatement = $pdo->prepare('SELECT p.id, p.nome, c.nome AS contexto
        FROM paises p
        INNER JOIN continentes c ON c.id = p.continente_id
        WHERE p.nome LIKE :term
        ORDER BY p.nome
        LIMIT 8');
    $countryStatement->execute(['term' => $term]);
    $countries = $countryStatement->fetchAll();

    $cityStatement = $pdo->prepare('SELECT ci.id, ci.nome, CONCAT(p.nome, " — ", co.nome) AS contexto
        FROM cidades ci
        INNER JOIN paises p ON p.id = ci.pais_id
        INNER JOIN continentes co ON co.id = p.continente_id
        WHERE ci.nome LIKE :term
        ORDER BY ci.nome, p.nome
        LIMIT 8');
    $cityStatement->execute(['term' => $term]);
    $cities = $cityStatement->fetchAll();

    $canManageRecords = is_administrator();

    foreach ($countries as &$country) {
        $country['url'] = $canManageRecords
            ? form_url('paises', (int) $country['id'])
            : url(entity_page('paises'));
    }
    unset($country);

    foreach ($cities as &$city) {
        $city['url'] = $canManageRecords
            ? form_url('cidades', (int) $city['id'])
            : url(entity_page('cidades'));
    }
    unset($city);

    return ['paises' => $countries, 'cidades' => $cities];
}

$query = normalize_text($_GET['q'] ?? '');
$results = search_geography($pdo, $query);

if (($_GET['formato'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/backend/includes/layout.php';
render_header('Busca');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Busca global</p>
        <h1>Resultados da busca</h1>
        <?php if ($query === ''): ?>
            <p>Digite o nome de um país ou cidade para pesquisar.</p>
        <?php elseif (text_length($query) < 2): ?>
            <p>Digite ao menos dois caracteres para pesquisar.</p>
        <?php else: ?>
            <p>Resultados para “<?= e($query) ?>”.</p>
        <?php endif; ?>
    </div>
</section>

<?php if (text_length($query) >= 2): ?>
    <section class="search-page-results">
        <article class="content-card">
            <h2>Países</h2>
            <?php if ($results['paises'] === []): ?>
                <p class="muted">Nenhum país encontrado.</p>
            <?php else: ?>
                <ul class="result-list">
                    <?php foreach ($results['paises'] as $country): ?>
                        <li><a href="<?= e($country['url']) ?>"><strong><?= e($country['nome']) ?></strong><span><?= e($country['contexto']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
        <article class="content-card">
            <h2>Cidades</h2>
            <?php if ($results['cidades'] === []): ?>
                <p class="muted">Nenhuma cidade encontrada.</p>
            <?php else: ?>
                <ul class="result-list">
                    <?php foreach ($results['cidades'] as $city): ?>
                        <li><a href="<?= e($city['url']) ?>"><strong><?= e($city['nome']) ?></strong><span><?= e($city['contexto']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
