<?php
declare(strict_types=1);

if (!isset($entidade) || !is_string($entidade)) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/layout.php';

$definition = entity_definition($entidade);

if ($definition === null) {
    http_response_code(404);
    render_header('Página não encontrada');
    ?>
    <section class="empty-state">
        <h1>Página não encontrada</h1>
        <p>O módulo solicitado não existe.</p>
        <a class="button button-primary" href="<?= e(url('index.php')) ?>">Voltar ao início</a>
    </section>
    <?php
    render_footer();
    exit;
}

try {
    $records = $pdo->query((string) $definition['query'])->fetchAll();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $records = [];
    $loadError = 'Não foi possível carregar os registros neste momento.';
}

render_header((string) $definition['plural'], $entidade);
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Gerenciamento</p>
        <h1><?= e($definition['plural']) ?></h1>
        <p><?= e($definition['description']) ?></p>
    </div>
    <a class="button button-primary" href="<?= e(form_url($entidade)) ?>">+ Novo <?= e($definition['singular']) ?></a>
</section>

<?php if (isset($loadError)): ?>
    <div class="flash flash-error" role="alert"><?= e($loadError) ?></div>
<?php endif; ?>

<section class="content-card" aria-labelledby="titulo-lista">
    <div class="list-toolbar">
        <div>
            <h2 id="titulo-lista">Registros cadastrados</h2>
            <p><strong data-record-count><?= count($records) ?></strong> <?= count($records) === 1 ? 'registro' : 'registros' ?></p>
        </div>
        <label class="table-search">
            <span class="visually-hidden">Filtrar <?= e(strtolower((string) $definition['plural'])) ?></span>
            <span aria-hidden="true">⌕</span>
            <input type="search" placeholder="Filtrar por nome" autocomplete="off" data-table-search>
        </label>
    </div>

    <?php if ($records === []): ?>
        <div class="empty-state compact">
            <h3>Ainda não há <?= e(strtolower((string) $definition['plural'])) ?>.</h3>
            <p>Comece cadastrando o primeiro <?= e($definition['singular']) ?>.</p>
            <a class="button button-primary" href="<?= e(form_url($entidade)) ?>">Cadastrar <?= e($definition['singular']) ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" data-listing>
                <thead>
                    <tr>
                        <?php foreach ($definition['columns'] as $column): ?>
                            <th scope="col"><?= e($column['label']) ?></th>
                        <?php endforeach; ?>
                        <th scope="col" class="actions-column">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr data-filter-row>
                            <?php foreach ($definition['columns'] as $column): ?>
                                <?php $value = display_value((string) $column['key'], $record[$column['key']] ?? null); ?>
                                <td data-label="<?= e($column['label']) ?>"<?= !empty($column['primary']) ? ' class="cell-primary"' : '' ?>><?= e($value) ?></td>
                            <?php endforeach; ?>
                            <td class="row-actions" data-label="Ações">
                                <a class="button button-small button-secondary" href="<?= e(form_url($entidade, (int) $record['id'])) ?>">Editar</a>
                                <form class="inline-form" action="<?= e(url('backend/actions/excluir.php')) ?>" method="post" data-delete-form data-record-name="<?= e($record['nome']) ?>">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="entidade" value="<?= e($entidade) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                    <button class="button button-small button-danger" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="filter-empty" data-empty-filter hidden>Nenhum registro corresponde à busca.</p>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
