<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';

try {
    $countries = $pdo->query('SELECT id_pais, nome_pais, codigo_iso, capital, populacao, regiao, sub_regiao FROM paises_api ORDER BY nome_pais ASC')->fetchAll();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $countries = [];
    $loadError = 'Não foi possível carregar os países importados. Execute o script SQL complementar antes de usar esta tela.';
}

render_header('Países importados', 'api-paises');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">REST Countries</p>
        <h1>Países importados</h1>
        <p>Dados consultados na API e armazenados nesta aplicação.</p>
    </div>
    <a class="button button-primary" href="<?= e(url('api_paises.php')) ?>">Consultar país</a>
</section>

<?php if (isset($loadError)): ?>
    <div class="flash flash-error" role="alert"><?= e($loadError) ?></div>
<?php elseif ($countries === []): ?>
    <section class="empty-state">
        <h2>Nenhum país importado ainda.</h2>
        <p>Consulte um país na REST Countries para começar a listagem.</p>
        <a class="button button-primary" href="<?= e(url('api_paises.php')) ?>">Consultar e cadastrar</a>
    </section>
<?php else: ?>
    <section class="content-card">
        <div class="list-toolbar">
            <div><h2>Registros cadastrados</h2><p><?= count($countries) ?> <?= count($countries) === 1 ? 'país importado' : 'países importados' ?></p></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>ID</th><th>País</th><th>ISO</th><th>Capital</th><th>População</th><th>Região</th><th>Sub-região</th></tr></thead>
                <tbody>
                    <?php foreach ($countries as $country): ?>
                        <tr>
                            <td data-label="ID"><?= (int) $country['id_pais'] ?></td>
                            <td data-label="País" class="cell-primary"><?= e($country['nome_pais']) ?></td>
                            <td data-label="ISO"><?= e($country['codigo_iso']) ?></td>
                            <td data-label="Capital"><?= e($country['capital'] ?? '—') ?></td>
                            <td data-label="População"><?= $country['populacao'] === null ? '—' : e(number_format((int) $country['populacao'], 0, ',', '.')) ?></td>
                            <td data-label="Região"><?= e($country['regiao'] ?? '—') ?></td>
                            <td data-label="Sub-região"><?= e($country['sub_regiao'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
