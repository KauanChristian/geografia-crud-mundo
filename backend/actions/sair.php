<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

if (!valid_csrf($_POST['_csrf'] ?? null)) {
    flash('error', 'Sua sessão expirou. Tente novamente.');
    redirect('index.php');
}

$user = current_user();
if ($user !== null) {
    log_user_event($pdo, $user['id'], 'LOGOUT_REALIZADO', 'Sessão encerrada pelo usuário.');
}

$_SESSION = [];
session_regenerate_id(true);
flash('success', 'Você saiu do sistema.');
redirect('login.php');
