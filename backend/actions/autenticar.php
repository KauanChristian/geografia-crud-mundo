<?php
declare(strict_types=1);

define('AUTH_PUBLIC_ROUTE', true);
require_once __DIR__ . '/../includes/bootstrap.php';

redirect_authenticated_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

if (!valid_csrf($_POST['_csrf'] ?? null)) {
    flash('error', 'Sua sessão expirou. Tente novamente.');
    redirect('login.php');
}

$login = normalize_text($_POST['login'] ?? '');
$password = (string) ($_POST['senha'] ?? '');

if ($login === '' || $password === '' || text_length($login) > 50) {
    flash('error', 'Usuário ou senha inválidos.');
    redirect('login.php');
}

$statement = $pdo->prepare('SELECT id, login, nome, senha_hash, tipo, ativo, primeiro_acesso, tentativas_falhas, bloqueado_em FROM usuarios WHERE login = :login LIMIT 1');
$statement->execute(['login' => $login]);
$user = $statement->fetch();

if (!$user || !(bool) $user['ativo'] || $user['bloqueado_em'] !== null) {
    log_user_event($pdo, $user ? (int) $user['id'] : null, 'LOGIN_NEGADO', 'Tentativa de acesso recusada para o usuário informado.');
    flash('error', 'Usuário ou senha inválidos, ou acesso bloqueado.');
    redirect('login.php');
}

if (!password_verify($password, (string) $user['senha_hash'])) {
    $attempts = min(3, (int) $user['tentativas_falhas'] + 1);
    $blocked = $attempts >= 3;
    $update = $pdo->prepare('UPDATE usuarios SET tentativas_falhas = :tentativas, bloqueado_em = :bloqueado_em WHERE id = :id');
    $update->execute([
        'tentativas' => $attempts,
        'bloqueado_em' => $blocked ? date('Y-m-d H:i:s') : null,
        'id' => (int) $user['id'],
    ]);
    log_user_event($pdo, (int) $user['id'], $blocked ? 'USUARIO_BLOQUEADO' : 'LOGIN_FALHOU', $blocked
        ? 'Usuário bloqueado após três tentativas consecutivas de senha incorreta.'
        : 'Senha incorreta informada no login.');
    flash('error', $blocked
        ? 'Acesso bloqueado após três tentativas incorretas consecutivas. Procure o administrador.'
        : 'Usuário ou senha inválidos.');
    redirect('login.php');
}

$pdo->prepare('UPDATE usuarios SET tentativas_falhas = 0, ultimo_acesso_em = NOW() WHERE id = :id')->execute(['id' => (int) $user['id']]);
log_user_event($pdo, (int) $user['id'], 'LOGIN_REALIZADO', 'Login realizado com sucesso.');
session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'login' => (string) $user['login'],
    'nome' => (string) $user['nome'],
    'tipo' => (string) $user['tipo'],
    'primeiro_acesso' => (bool) $user['primeiro_acesso'],
];
flash('success', 'Acesso realizado com sucesso.');
redirect((bool) $user['primeiro_acesso'] ? 'senha.php' : 'index.php');
