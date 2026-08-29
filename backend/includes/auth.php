<?php
declare(strict_types=1);

/** @return array{id: int, login: string, nome: string, tipo: string, primeiro_acesso: bool}|null */
function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    if (!is_array($user) || !isset($user['id'], $user['login'], $user['nome'], $user['tipo'])) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'login' => (string) $user['login'],
        'nome' => (string) $user['nome'],
        'tipo' => (string) $user['tipo'],
        'primeiro_acesso' => !empty($user['primeiro_acesso']),
    ];
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function password_change_required(): bool
{
    $user = current_user();
    return $user !== null && $user['primeiro_acesso'];
}

function is_administrator(): bool
{
    $user = current_user();

    return $user !== null && $user['tipo'] === 'A';
}

function require_authentication(): void
{
    if (!is_authenticated()) {
        flash('error', 'Faça login para acessar o sistema.');
        redirect('login.php');
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $allowedWhileChangingPassword = ['senha.php', 'alterar_senha.php', 'sair.php'];

    if (password_change_required() && !in_array($script, $allowedWhileChangingPassword, true)) {
        flash('error', 'Por segurança, defina uma nova senha antes de continuar.');
        redirect('senha.php');
    }
}

function require_administrator(): void
{
    if (!is_administrator()) {
        flash('error', 'Apenas administradores podem criar, atualizar ou excluir registros.');
        redirect('index.php');
    }
}

function redirect_authenticated_user(): void
{
    if (!is_authenticated()) {
        return;
    }

    redirect(password_change_required() ? 'senha.php' : 'index.php');
}

function log_user_event(PDO $pdo, ?int $userId, string $event, string $description): void
{
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $statement = $pdo->prepare(
        'INSERT INTO logs (usuario_id, evento, descricao, endereco_ip) VALUES (:usuario_id, :evento, :descricao, :endereco_ip)'
    );
    $statement->execute([
        'usuario_id' => $userId,
        'evento' => substr($event, 0, 80),
        'descricao' => substr($description, 0, 255),
        'endereco_ip' => $ip !== '' ? $ip : null,
    ]);
}

function set_password_form_errors(array $errors): void
{
    $_SESSION['password_form_errors'] = $errors;
}

/** @return array<string, string> */
function pull_password_form_errors(): array
{
    $errors = $_SESSION['password_form_errors'] ?? [];
    unset($_SESSION['password_form_errors']);

    return is_array($errors) ? $errors : [];
}

function password_meets_policy(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'A nova senha deve ter pelo menos 8 caracteres.';
    }

    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'A nova senha deve conter letra maiúscula, letra minúscula e número.';
    }

    return null;
}
