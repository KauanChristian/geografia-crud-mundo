<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/entity_config.php';

try {
    $pdo = database_connection();
} catch (Throwable $exception) {
    http_response_code(500);
    error_log($exception->getMessage());
    exit('Não foi possível conectar ao banco de dados. Importe o arquivo database/bd_mundo.sql e confira as variáveis DB_HOST, DB_NAME, DB_USER e DB_PASS.');
}
