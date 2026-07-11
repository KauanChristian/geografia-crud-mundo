<?php
declare(strict_types=1);

/** @return string Texto seguro para uso em HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Descobre o caminho da aplicação quando ela estiver dentro de uma subpasta do
 * servidor web. Com o servidor interno do PHP, o resultado é uma string vazia.
 */
function app_base_url(): string
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $applicationRoot = realpath(dirname(__DIR__, 2));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($applicationRoot === false || $documentRoot === false) {
        $baseUrl = '';
        return $baseUrl;
    }

    $rootNormalized = str_replace('\\', '/', $applicationRoot);
    $documentNormalized = rtrim(str_replace('\\', '/', $documentRoot), '/');

    if (stripos($rootNormalized, $documentNormalized) === 0) {
        $baseUrl = rtrim(substr($rootNormalized, strlen($documentNormalized)), '/');
        return $baseUrl;
    }

    $baseUrl = '';
    return $baseUrl;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $baseUrl = app_base_url();

    return $baseUrl . ($path === '' ? '/' : '/' . $path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

/** @return array<int, array{type: string, message: string}> */
function pull_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return is_array($flashes) ? $flashes : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function valid_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** @param array<string, string> $errors @param array<string, mixed> $old */
function set_form_state(string $entity, array $errors, array $old): void
{
    $_SESSION['form_state'][$entity] = [
        'errors' => $errors,
        'old' => $old,
    ];
}

/** @return array{errors: array<string, string>, old: array<string, mixed>} */
function pull_form_state(string $entity): array
{
    $state = $_SESSION['form_state'][$entity] ?? ['errors' => [], 'old' => []];
    unset($_SESSION['form_state'][$entity]);

    return [
        'errors' => is_array($state['errors'] ?? null) ? $state['errors'] : [],
        'old' => is_array($state['old'] ?? null) ? $state['old'] : [],
    ];
}

function positive_id($value): ?int
{
    if (is_int($value) && $value > 0) {
        return $value;
    }

    if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value)) {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    return null;
}

function normalize_text($value): string
{
    $text = trim((string) $value);
    $normalized = preg_replace('/\s+/u', ' ', $text);

    return $normalized === null ? $text : $normalized;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/** @return string|null Número inteiro não negativo em formato seguro para SQL. */
function integer_input($value): ?string
{
    $value = trim((string) $value);

    if (!preg_match('/^[0-9]+$/', $value)) {
        return null;
    }

    $value = ltrim($value, '0');
    return $value === '' ? '0' : $value;
}

/** @return string|null Número decimal não negativo em formato seguro para SQL. */
function decimal_input($value): ?string
{
    $value = str_replace(',', '.', trim((string) $value));

    if (!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $value)) {
        return null;
    }

    return number_format((float) $value, 2, '.', '');
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function date_is_not_future(string $value): bool
{
    return valid_date($value) && $value <= date('Y-m-d');
}

function calculate_age(string $birthDate): int
{
    $birth = new DateTime($birthDate);
    $today = new DateTime('today');

    return max(0, $birth->diff($today)->y);
}

function database_record_exists(PDO $pdo, string $table, int $id): bool
{
    $allowedTables = ['continentes', 'paises', 'cidades', 'governantes'];

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $statement = $pdo->prepare("SELECT 1 FROM {$table} WHERE id = :id LIMIT 1");
    $statement->execute(['id' => $id]);

    return (bool) $statement->fetchColumn();
}

function format_population($value): string
{
    return number_format((float) $value, 0, ',', '.') . ' hab.';
}

function format_area($value): string
{
    return number_format((float) $value, 2, ',', '.') . ' km²';
}

function format_date_br($value): string
{
    if (!$value || !valid_date((string) $value)) {
        return '—';
    }

    return date('d/m/Y', strtotime((string) $value));
}

function display_value(string $key, $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    if (in_array($key, ['populacao'], true)) {
        return format_population($value);
    }

    if (in_array($key, ['area_km2'], true)) {
        return format_area($value);
    }

    if (in_array($key, ['data_fundacao', 'data_nascimento', 'data_inicio_mandato', 'data_fim_mandato'], true)) {
        return format_date_br($value);
    }

    if (in_array($key, ['total_paises', 'total_paises_calculado', 'total_cidades', 'idade_atual', 'idade'], true)) {
        return number_format((float) $value, 0, ',', '.');
    }

    return (string) $value;
}
