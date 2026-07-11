<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';
require_once __DIR__ . '/backend/includes/form_fields.php';

$entity = isset($_GET['entidade']) ? (string) $_GET['entidade'] : '';
$definition = entity_definition($entity);

if ($definition === null) {
    flash('error', 'O tipo de cadastro informado é inválido.');
    redirect('index.php');
}

$id = positive_id($_GET['id'] ?? null);

if (isset($_GET['id']) && $id === null) {
    flash('error', 'O registro solicitado é inválido.');
    redirect(entity_page($entity));
}

$defaults = [
    'continentes' => ['nome' => '', 'populacao' => '', 'area_km2' => '', 'total_paises' => 0],
    'paises' => ['nome' => '', 'continente_id' => '', 'populacao' => '', 'area_km2' => '', 'idioma' => '', 'clima' => '', 'regime_politico' => '', 'moeda' => ''],
    'cidades' => ['nome' => '', 'pais_id' => '', 'populacao' => '', 'area_km2' => '', 'clima' => '', 'data_fundacao' => ''],
    'governantes' => ['nome' => '', 'partido_politico' => '', 'data_nascimento' => '', 'idade' => '', 'data_inicio_mandato' => '', 'data_fim_mandato' => '', 'pais_id' => '', 'cidade_id' => '', 'vinculo_tipo' => 'pais'],
];

$record = $defaults[$entity];

if ($id !== null) {
    $statement = $pdo->prepare('SELECT * FROM ' . $definition['table'] . ' WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $savedRecord = $statement->fetch();

    if (!$savedRecord) {
        flash('error', 'O registro solicitado não foi encontrado.');
        redirect(entity_page($entity));
    }

    $record = array_merge($record, $savedRecord);

    if ($entity === 'governantes') {
        $record['vinculo_tipo'] = !empty($record['pais_id']) ? 'pais' : 'cidade';
    }
}

$formState = pull_form_state($entity);
$errors = $formState['errors'];

if ($formState['old'] !== []) {
    $record = array_merge($record, $formState['old']);
}

$continentOptions = [];
$countryOptions = [];
$cityOptions = [];

if ($entity === 'paises') {
    $continentOptions = $pdo->query('SELECT id, nome AS label FROM continentes ORDER BY nome')->fetchAll();
}

if ($entity === 'cidades' || $entity === 'governantes') {
    $countryOptions = $pdo->query('SELECT id, nome AS label FROM paises ORDER BY nome')->fetchAll();
}

if ($entity === 'governantes') {
    $cityOptions = $pdo->query('SELECT c.id, CONCAT(c.nome, " — ", p.nome) AS label FROM cidades c INNER JOIN paises p ON p.id = c.pais_id ORDER BY c.nome, p.nome')->fetchAll();
}

$isEditing = $id !== null;
$title = ($isEditing ? 'Editar ' : 'Novo ') . $definition['singular'];

render_header($title, $entity);
?>
<section class="page-heading">
    <div>
        <p class="eyebrow"><?= $isEditing ? 'Atualização' : 'Novo cadastro' ?></p>
        <h1><?= e(ucfirst($title)) ?></h1>
        <p>Campos com <span class="required" aria-label="obrigatório">*</span> são obrigatórios.</p>
    </div>
    <a class="button button-secondary" href="<?= e(url(entity_page($entity))) ?>">← Voltar à lista</a>
</section>

<section class="form-card">
    <?php if ($errors !== []): ?>
        <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
            <h2>Revise os campos indicados</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= e(url('backend/actions/salvar.php')) ?>" method="post" class="entity-form" data-validate-form>
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="entidade" value="<?= e($entity) ?>">
        <?php if ($id !== null): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <?php if ($entity === 'continentes'): ?>
            <div class="form-grid">
                <?php render_input_field('nome', 'Nome', $record['nome'], $errors, 'text', ['required' => true, 'maxlength' => 100, 'autocomplete' => 'off']); ?>
                <?php render_input_field('populacao', 'População', $record['populacao'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => 1, 'inputmode' => 'numeric', 'data-positive-number' => 'integer']); ?>
                <?php render_input_field('area_km2', 'Área (km²)', $record['area_km2'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => '0.01', 'inputmode' => 'decimal', 'data-positive-number' => 'decimal']); ?>
                <?php render_input_field('total_paises', 'Total de países', $record['total_paises'] ?? 0, [], 'number', ['readonly' => true, 'tabindex' => -1], 'Calculado automaticamente a partir dos países vinculados.'); ?>
            </div>
        <?php elseif ($entity === 'paises'): ?>
            <div class="form-grid">
                <?php render_input_field('nome', 'Nome', $record['nome'], $errors, 'text', ['required' => true, 'maxlength' => 120, 'autocomplete' => 'off']); ?>
                <?php render_select_field('continente_id', 'Continente', $record['continente_id'], $continentOptions, $errors, ['required' => true], 'Selecione o continente'); ?>
                <?php render_input_field('populacao', 'População', $record['populacao'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => 1, 'inputmode' => 'numeric', 'data-positive-number' => 'integer']); ?>
                <?php render_input_field('area_km2', 'Área (km²)', $record['area_km2'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => '0.01', 'inputmode' => 'decimal', 'data-positive-number' => 'decimal']); ?>
                <?php render_input_field('idioma', 'Idioma', $record['idioma'], $errors, 'text', ['required' => true, 'maxlength' => 150]); ?>
                <?php render_input_field('clima', 'Clima', $record['clima'], $errors, 'text', ['required' => true, 'maxlength' => 100]); ?>
                <?php render_input_field('regime_politico', 'Regime político', $record['regime_politico'], $errors, 'text', ['required' => true, 'maxlength' => 120]); ?>
                <?php render_input_field('moeda', 'Moeda', $record['moeda'], $errors, 'text', ['required' => true, 'maxlength' => 100]); ?>
            </div>
            <p class="form-note">Após salvar o país, use o módulo <strong>Governantes</strong> para registrar o mandato nacional correspondente.</p>
        <?php elseif ($entity === 'cidades'): ?>
            <div class="form-grid">
                <?php render_input_field('nome', 'Nome', $record['nome'], $errors, 'text', ['required' => true, 'maxlength' => 120, 'autocomplete' => 'off']); ?>
                <?php render_select_field('pais_id', 'País', $record['pais_id'], $countryOptions, $errors, ['required' => true], 'Selecione o país'); ?>
                <?php render_input_field('populacao', 'População', $record['populacao'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => 1, 'inputmode' => 'numeric', 'data-positive-number' => 'integer']); ?>
                <?php render_input_field('area_km2', 'Área (km²)', $record['area_km2'], $errors, 'number', ['required' => true, 'min' => 0, 'step' => '0.01', 'inputmode' => 'decimal', 'data-positive-number' => 'decimal']); ?>
                <?php render_input_field('clima', 'Clima', $record['clima'], $errors, 'text', ['required' => true, 'maxlength' => 100]); ?>
                <?php render_input_field('data_fundacao', 'Data de fundação', $record['data_fundacao'], $errors, 'date', ['required' => true, 'max' => date('Y-m-d'), 'data-not-future' => true]); ?>
            </div>
            <p class="form-note">Após salvar a cidade, use o módulo <strong>Governantes</strong> para registrar o mandato municipal correspondente.</p>
        <?php elseif ($entity === 'governantes'): ?>
            <div class="form-grid">
                <?php render_input_field('nome', 'Nome completo', $record['nome'], $errors, 'text', ['required' => true, 'maxlength' => 150, 'autocomplete' => 'name']); ?>
                <?php render_input_field('partido_politico', 'Partido político', $record['partido_politico'], $errors, 'text', ['required' => true, 'maxlength' => 150]); ?>
                <?php render_input_field('data_nascimento', 'Data de nascimento', $record['data_nascimento'], $errors, 'date', ['required' => true, 'max' => date('Y-m-d'), 'data-birth-date' => true, 'data-not-future' => true]); ?>
                <?php render_input_field('idade', 'Idade', $record['idade'], [], 'number', ['readonly' => true, 'tabindex' => -1, 'data-age-output' => true], 'Calculada automaticamente a partir da data de nascimento.'); ?>
                <?php render_input_field('data_inicio_mandato', 'Início do mandato', $record['data_inicio_mandato'], $errors, 'date', ['required' => true, 'data-mandate-start' => true]); ?>
                <?php render_input_field('data_fim_mandato', 'Fim do mandato', $record['data_fim_mandato'], $errors, 'date', ['data-mandate-end' => true], 'Deixe em branco para indicar mandato vigente.'); ?>
                <div class="field<?= isset($errors['vinculo']) ? ' has-error' : '' ?>">
                    <label for="vinculo_tipo">Tipo de vínculo <span class="required" aria-label="obrigatório">*</span></label>
                    <select id="vinculo_tipo" name="vinculo_tipo" required data-governor-type>
                        <option value="pais"<?= $record['vinculo_tipo'] === 'pais' ? ' selected' : '' ?>>País</option>
                        <option value="cidade"<?= $record['vinculo_tipo'] === 'cidade' ? ' selected' : '' ?>>Cidade</option>
                    </select>
                    <?php if (isset($errors['vinculo'])): ?><small class="field-error"><?= e($errors['vinculo']) ?></small><?php endif; ?>
                </div>
                <div data-governor-target="pais">
                    <?php render_select_field('pais_id', 'País governado', $record['pais_id'], $countryOptions, $errors, ['data-required-when-active' => true], 'Selecione o país'); ?>
                </div>
                <div data-governor-target="cidade">
                    <?php render_select_field('cidade_id', 'Cidade governada', $record['cidade_id'], $cityOptions, $errors, ['data-required-when-active' => true], 'Selecione a cidade'); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a class="button button-secondary" href="<?= e(url(entity_page($entity))) ?>">Cancelar</a>
            <button class="button button-primary" type="submit"><?= $isEditing ? 'Salvar alterações' : 'Cadastrar ' . $definition['singular'] ?></button>
        </div>
    </form>
</section>
<?php render_footer(); ?>
