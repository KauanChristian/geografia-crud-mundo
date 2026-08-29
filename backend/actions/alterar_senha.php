<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

if (!valid_csrf($_POST['_csrf'] ?? null)) {
    flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
    redirect('senha.php');
}

$user = current_user();
if ($user === null) {
    redirect('login.php');
}

$currentPassword = (string) ($_POST['senha_atual'] ?? '');
$newPassword = (string) ($_POST['nova_senha'] ?? '');
$confirmation = (string) ($_POST['confirmacao_senha'] ?? '');
$errors = [];

$statement = $pdo->prepare('SELECT id, senha_hash, ativo, bloqueado_em FROM usuarios WHERE id = :id LIMIT 1');
$statement->execute(['id' => $user['id']]);
$databaseUser = $statement->fetch();

if (!$databaseUser || !(bool) $databaseUser['ativo'] || $databaseUser['bloqueado_em'] !== null) {
    unset($_SESSION['user']);
    flash('error', 'Sua conta não está disponível para alteração de senha.');
    redirect('login.php');
}

if ($currentPassword === '' || !password_verify($currentPassword, (string) $databaseUser['senha_hash'])) {
    $errors['senha_atual'] = 'Informe corretamente a senha atual.';
}

if ($message = password_meets_policy($newPassword)) {
    $errors['nova_senha'] = $message;
} elseif (password_verify($newPassword, (string) $databaseUser['senha_hash'])) {
    $errors['nova_senha'] = 'A nova senha deve ser diferente da senha atual.';
}

if ($confirmation === '' || !hash_equals($newPassword, $confirmation)) {
    $errors['confirmacao_senha'] = 'A confirmação deve ser igual à nova senha.';
}

if ($errors !== []) {
    set_password_form_errors($errors);
    redirect('senha.php');
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash, primeiro_acesso = 0, tentativas_falhas = 0 WHERE id = :id');
$update->execute(['senha_hash' => $hash, 'id' => $user['id']]);
$_SESSION['user']['primeiro_acesso'] = false;
log_user_event($pdo, $user['id'], 'SENHA_ALTERADA', 'Senha de acesso alterada pelo usuário.');
flash('success', 'Senha atualizada com sucesso.');
redirect('index.php');
