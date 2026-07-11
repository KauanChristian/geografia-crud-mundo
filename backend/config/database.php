<?php
declare(strict_types=1);

/**
 * Cria a conexão PDO usada por toda a aplicação.
 *
 * As credenciais podem ser informadas por variáveis de ambiente, evitando que
 * dados sensíveis precisem ser gravados no repositório.
 */
function database_connection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'bd_mundo';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
