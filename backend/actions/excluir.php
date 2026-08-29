<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_administrator();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$entity = isset($_POST['entidade']) ? (string) $_POST['entidade'] : '';
$definition = entity_definition($entity);

if ($definition === null) {
    flash('error', 'O tipo de registro informado é inválido.');
    redirect('index.php');
}

if (!valid_csrf($_POST['_csrf'] ?? null)) {
    flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
    redirect(entity_page($entity));
}

$id = positive_id($_POST['id'] ?? null);

if ($id === null) {
    flash('error', 'O registro informado é inválido.');
    redirect(entity_page($entity));
}

$recordStatement = $pdo->prepare('SELECT id, nome FROM ' . $definition['table'] . ' WHERE id = :id LIMIT 1');
$recordStatement->execute(['id' => $id]);
$record = $recordStatement->fetch();

if (!$record) {
    flash('error', 'O registro já não existe.');
    redirect(entity_page($entity));
}

$blockedReasons = [];

if ($entity === 'continentes') {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM paises WHERE continente_id = :id');
    $statement->execute(['id' => $id]);
    $count = (int) $statement->fetchColumn();

    if ($count > 0) {
        $blockedReasons[] = "há {$count} país(es) vinculado(s)";
    }
} elseif ($entity === 'paises') {
    $cityStatement = $pdo->prepare('SELECT COUNT(*) FROM cidades WHERE pais_id = :id');
    $cityStatement->execute(['id' => $id]);
    $cities = (int) $cityStatement->fetchColumn();
    $governorStatement = $pdo->prepare('SELECT COUNT(*) FROM governantes WHERE pais_id = :id');
    $governorStatement->execute(['id' => $id]);
    $governors = (int) $governorStatement->fetchColumn();

    if ($cities > 0) {
        $blockedReasons[] = "há {$cities} cidade(s) vinculada(s)";
    }
    if ($governors > 0) {
        $blockedReasons[] = "há {$governors} mandato(s) nacional(is) registrado(s)";
    }
} elseif ($entity === 'cidades') {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM governantes WHERE cidade_id = :id');
    $statement->execute(['id' => $id]);
    $count = (int) $statement->fetchColumn();

    if ($count > 0) {
        $blockedReasons[] = "há {$count} mandato(s) municipal(is) registrado(s)";
    }
}

if ($blockedReasons !== []) {
    flash('error', 'Não é possível excluir "' . $record['nome'] . '" porque ' . implode(' e ', $blockedReasons) . '. Remova ou edite os vínculos antes.');
    redirect(entity_page($entity));
}

try {
    $statement = $pdo->prepare('DELETE FROM ' . $definition['table'] . ' WHERE id = :id');
    $statement->execute(['id' => $id]);
    flash('success', ucfirst((string) $definition['singular']) . ' excluído(a) com sucesso.');
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    flash('error', 'Não foi possível excluir o registro porque existem dados relacionados a ele.');
}

redirect(entity_page($entity));
