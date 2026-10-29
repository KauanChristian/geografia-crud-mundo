<?php
declare(strict_types=1);

/**
 * Obtém a chave da REST Countries sem a expor ao navegador. Em produção,
 * prefira configurar REST_COUNTRIES_API_KEY no ambiente do Apache/PHP.
 */
function rest_countries_api_key(): string
{
    $environmentKey = getenv('REST_COUNTRIES_API_KEY');

    if (is_string($environmentKey) && trim($environmentKey) !== '') {
        return trim($environmentKey);
    }

    $localConfig = __DIR__ . '/api.local.php';
    if (is_file($localConfig)) {
        $localKey = require $localConfig;
        if (is_string($localKey) && trim($localKey) !== '') {
            return trim($localKey);
        }
    }

    // Chave de demonstração prevista no enunciado; limitada para testes.
    return 'rc_live_demo';
}
