<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_administrator();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

/** @param array<string, string> $errors */
function post_required_text(string $field, string $label, int $maxLength, array &$errors): string
{
    $value = normalize_text($_POST[$field] ?? '');

    if ($value === '') {
        $errors[$field] = "Informe {$label}.";
    } elseif (text_length($value) > $maxLength) {
        $errors[$field] = "{$label} deve ter no máximo {$maxLength} caracteres.";
    }

    return $value;
}

/** @param array<string, string> $errors */
function post_integer(string $field, string $label, array &$errors): string
{
    $value = integer_input($_POST[$field] ?? '');

    if ($value === null) {
        $errors[$field] = "{$label} deve ser um número inteiro igual ou maior que zero.";
        return '';
    }

    return $value;
}

/** @param array<string, string> $errors */
function post_decimal(string $field, string $label, array &$errors): string
{
    $value = decimal_input($_POST[$field] ?? '');

    if ($value === null) {
        $errors[$field] = "{$label} deve ser um número igual ou maior que zero, com até duas casas decimais.";
        return '';
    }

    return $value;
}

/** @param array<string, string> $errors */
function post_date_value(string $field, string $label, array &$errors, bool $allowEmpty = false, bool $notFuture = false): ?string
{
    $value = trim((string) ($_POST[$field] ?? ''));

    if ($value === '' && $allowEmpty) {
        return null;
    }

    if ($value === '' || !valid_date($value)) {
        $errors[$field] = "Informe uma data válida para {$label}.";
        return '';
    }

    if ($notFuture && !date_is_not_future($value)) {
        $errors[$field] = "{$label} não pode estar no futuro.";
    }

    return $value;
}

/** @param array<string, string> $errors */
function post_relation_id(PDO $pdo, string $field, string $label, string $table, array &$errors): ?int
{
    $id = positive_id($_POST[$field] ?? null);

    if ($id === null) {
        $errors[$field] = "Selecione {$label}.";
        return null;
    }

    if (!database_record_exists($pdo, $table, $id)) {
        $errors[$field] = "O(A) {$label} selecionado(a) não existe mais.";
        return null;
    }

    return $id;
}

function duplicate_name_exists(PDO $pdo, string $entity, string $name, ?int $relatedId, ?int $ignoreId): bool
{
    $queries = [
        'continentes' => ['SELECT 1 FROM continentes WHERE nome = :nome', ['nome' => $name]],
        'paises' => ['SELECT 1 FROM paises WHERE nome = :nome', ['nome' => $name]],
        'cidades' => ['SELECT 1 FROM cidades WHERE nome = :nome AND pais_id = :related_id', ['nome' => $name, 'related_id' => $relatedId]],
    ];

    if (!isset($queries[$entity])) {
        return false;
    }

    [$sql, $parameters] = $queries[$entity];

    if ($ignoreId !== null) {
        $sql .= ' AND id <> :ignore_id';
        $parameters['ignore_id'] = $ignoreId;
    }

    $statement = $pdo->prepare($sql . ' LIMIT 1');
    $statement->execute($parameters);

    return (bool) $statement->fetchColumn();
}

/** @param array<string, string> $errors @param array<string, mixed> $old */
function return_to_form_with_errors(string $entity, ?int $id, array $errors, array $old): void
{
    set_form_state($entity, $errors, $old);
    $path = 'formulario.php?entidade=' . rawurlencode($entity);

    if ($id !== null) {
        $path .= '&id=' . $id;
    }

    redirect($path);
}

$entity = isset($_POST['entidade']) ? (string) $_POST['entidade'] : '';
$definition = entity_definition($entity);

if ($definition === null) {
    flash('error', 'O tipo de cadastro informado é inválido.');
    redirect('index.php');
}

if (!valid_csrf($_POST['_csrf'] ?? null)) {
    flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
    redirect(entity_page($entity));
}

$id = positive_id($_POST['id'] ?? null);

if (isset($_POST['id']) && $id === null) {
    flash('error', 'O registro informado é inválido.');
    redirect(entity_page($entity));
}

if ($id !== null && !database_record_exists($pdo, (string) $definition['table'], $id)) {
    flash('error', 'O registro que você tentou editar não existe mais.');
    redirect(entity_page($entity));
}

$errors = [];
$old = $_POST;
unset($old['_csrf']);

if ($entity === 'continentes') {
    $data = [
        'nome' => post_required_text('nome', 'o nome', 100, $errors),
        'populacao' => post_integer('populacao', 'A população', $errors),
        'area_km2' => post_decimal('area_km2', 'A área', $errors),
    ];

    if (!isset($errors['nome']) && duplicate_name_exists($pdo, $entity, $data['nome'], null, $id)) {
        $errors['nome'] = 'Já existe um continente com este nome.';
    }
} elseif ($entity === 'paises') {
    $data = [
        'nome' => post_required_text('nome', 'o nome', 120, $errors),
        'continente_id' => post_relation_id($pdo, 'continente_id', 'um continente', 'continentes', $errors),
        'populacao' => post_integer('populacao', 'A população', $errors),
        'area_km2' => post_decimal('area_km2', 'A área', $errors),
        'idioma' => post_required_text('idioma', 'o idioma', 150, $errors),
        'clima' => post_required_text('clima', 'o clima', 100, $errors),
        'regime_politico' => post_required_text('regime_politico', 'o regime político', 120, $errors),
        'moeda' => post_required_text('moeda', 'a moeda', 100, $errors),
    ];

    if (!isset($errors['nome']) && duplicate_name_exists($pdo, $entity, $data['nome'], null, $id)) {
        $errors['nome'] = 'Já existe um país com este nome.';
    }
} elseif ($entity === 'cidades') {
    $data = [
        'nome' => post_required_text('nome', 'o nome', 120, $errors),
        'pais_id' => post_relation_id($pdo, 'pais_id', 'um país', 'paises', $errors),
        'populacao' => post_integer('populacao', 'A população', $errors),
        'area_km2' => post_decimal('area_km2', 'A área', $errors),
        'clima' => post_required_text('clima', 'o clima', 100, $errors),
        'data_fundacao' => post_date_value('data_fundacao', 'a data de fundação', $errors, false, true),
    ];

    if (!isset($errors['nome'], $errors['pais_id']) && duplicate_name_exists($pdo, $entity, $data['nome'], $data['pais_id'], $id)) {
        $errors['nome'] = 'Já existe uma cidade com este nome no país selecionado.';
    }
} else {
    $birthDate = post_date_value('data_nascimento', 'a data de nascimento', $errors, false, true);
    $startDate = post_date_value('data_inicio_mandato', 'a data de início do mandato', $errors);
    $endDate = post_date_value('data_fim_mandato', 'a data de fim do mandato', $errors, true);
    $type = isset($_POST['vinculo_tipo']) ? (string) $_POST['vinculo_tipo'] : '';

    $data = [
        'nome' => post_required_text('nome', 'o nome completo', 150, $errors),
        'partido_politico' => post_required_text('partido_politico', 'o partido político', 150, $errors),
        'data_nascimento' => $birthDate,
        'idade' => $birthDate !== null && valid_date($birthDate) ? calculate_age($birthDate) : 0,
        'data_inicio_mandato' => $startDate,
        'data_fim_mandato' => $endDate,
        'pais_id' => null,
        'cidade_id' => null,
    ];

    if (!in_array($type, ['pais', 'cidade'], true)) {
        $errors['vinculo'] = 'Escolha se o mandato é de país ou de cidade.';
    } elseif ($type === 'pais') {
        $data['pais_id'] = post_relation_id($pdo, 'pais_id', 'um país', 'paises', $errors);
    } else {
        $data['cidade_id'] = post_relation_id($pdo, 'cidade_id', 'uma cidade', 'cidades', $errors);
    }

    if ($startDate !== null && $endDate !== null && valid_date($startDate) && valid_date($endDate) && $endDate < $startDate) {
        $errors['data_fim_mandato'] = 'O fim do mandato não pode ser anterior ao início.';
    }

    $targetId = $type === 'pais' ? $data['pais_id'] : $data['cidade_id'];
    if ($type !== '' && $endDate === null && $targetId !== null) {
        $column = $type === 'pais' ? 'pais_id' : 'cidade_id';
        $sql = "SELECT id FROM governantes WHERE {$column} = :target AND data_fim_mandato IS NULL";
        $parameters = ['target' => $targetId];

        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $id;
        }

        $statement = $pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        if ($statement->fetch()) {
            $errors['vinculo'] = 'Já há um governante com mandato vigente neste local. Finalize o mandato atual antes de cadastrar outro.';
        }
    }
}

if ($errors !== []) {
    return_to_form_with_errors($entity, $id, $errors, $old);
}

try {
    if ($entity === 'continentes') {
        if ($id === null) {
            $statement = $pdo->prepare('INSERT INTO continentes (nome, populacao, area_km2) VALUES (:nome, :populacao, :area_km2)');
            $statement->execute($data);
        } else {
            $statement = $pdo->prepare('UPDATE continentes SET nome = :nome, populacao = :populacao, area_km2 = :area_km2 WHERE id = :id');
            $statement->execute($data + ['id' => $id]);
        }
    } elseif ($entity === 'paises') {
        if ($id === null) {
            $statement = $pdo->prepare('INSERT INTO paises (nome, continente_id, populacao, area_km2, idioma, clima, regime_politico, moeda) VALUES (:nome, :continente_id, :populacao, :area_km2, :idioma, :clima, :regime_politico, :moeda)');
            $statement->execute($data);
        } else {
            $statement = $pdo->prepare('UPDATE paises SET nome = :nome, continente_id = :continente_id, populacao = :populacao, area_km2 = :area_km2, idioma = :idioma, clima = :clima, regime_politico = :regime_politico, moeda = :moeda WHERE id = :id');
            $statement->execute($data + ['id' => $id]);
        }
    } elseif ($entity === 'cidades') {
        if ($id === null) {
            $statement = $pdo->prepare('INSERT INTO cidades (nome, pais_id, populacao, area_km2, clima, data_fundacao) VALUES (:nome, :pais_id, :populacao, :area_km2, :clima, :data_fundacao)');
            $statement->execute($data);
        } else {
            $statement = $pdo->prepare('UPDATE cidades SET nome = :nome, pais_id = :pais_id, populacao = :populacao, area_km2 = :area_km2, clima = :clima, data_fundacao = :data_fundacao WHERE id = :id');
            $statement->execute($data + ['id' => $id]);
        }
    } else {
        if ($id === null) {
            $statement = $pdo->prepare('INSERT INTO governantes (nome, partido_politico, data_nascimento, idade, data_inicio_mandato, data_fim_mandato, pais_id, cidade_id) VALUES (:nome, :partido_politico, :data_nascimento, :idade, :data_inicio_mandato, :data_fim_mandato, :pais_id, :cidade_id)');
            $statement->execute($data);
        } else {
            $statement = $pdo->prepare('UPDATE governantes SET nome = :nome, partido_politico = :partido_politico, data_nascimento = :data_nascimento, idade = :idade, data_inicio_mandato = :data_inicio_mandato, data_fim_mandato = :data_fim_mandato, pais_id = :pais_id, cidade_id = :cidade_id WHERE id = :id');
            $statement->execute($data + ['id' => $id]);
        }
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errors['geral'] = (string) $exception->getCode() === '23000'
        ? 'Não foi possível salvar: há um registro duplicado ou um vínculo que viola a integridade dos dados.'
        : 'Não foi possível salvar o registro. Tente novamente.';
    return_to_form_with_errors($entity, $id, $errors, $old);
}

$action = $id === null ? 'cadastrado' : 'atualizado';
flash('success', ucfirst((string) $definition['singular']) . " {$action} com sucesso.");
redirect(entity_page($entity));
