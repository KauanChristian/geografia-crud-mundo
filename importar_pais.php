<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';
require_once __DIR__ . '/config/api.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

$message = null;
$messageType = 'error';
$importedCountry = null;
$submittedCountry = '';

/** @return array<string, mixed>|null */
function first_api_country(array $payload): ?array
{
    $objects = $payload['data']['objects'] ?? null;

    if (!is_array($objects)) {
        return null;
    }

    foreach ($objects as $country) {
        if (is_array($country)) {
            return $country;
        }
    }

    return null;
}

/** @param mixed $value */
function nullable_api_text($value, int $maxLength): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $value = normalize_text($value);
    return $value !== '' && text_length($value) <= $maxLength ? $value : null;
}

require_administrator();
$submittedCountry = normalize_text($_POST['pais'] ?? '');

    if (!valid_csrf($_POST['_csrf'] ?? null)) {
        $message = 'Sua sessão expirou. Recarregue a página e tente novamente.';
    } elseif ($submittedCountry === '') {
        $message = 'Informe o nome de um país.';
    } elseif (text_length($submittedCountry) > 100) {
        $message = 'O nome do país deve ter no máximo 100 caracteres.';
    } elseif (!function_exists('curl_init')) {
        error_log('A extensão cURL não está disponível para importar países.');
        $message = 'A extensão cURL do PHP não está disponível neste servidor.';
    } else {
        $apiKey = rest_countries_api_key();

        if ($apiKey === '') {
            $message = 'A chave da API não está configurada.';
        } else {
            $url = 'https://api.restcountries.com/countries/v5/names.common/' . rawurlencode($submittedCountry);
            $curl = curl_init($url);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
            ]);

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response === false) {
                error_log('Falha na REST Countries: ' . $curlError);
                $message = 'Não foi possível comunicar com a API de países. Tente novamente.';
            } elseif ($httpStatus === 401 || $httpStatus === 403) {
                error_log('A REST Countries recusou a chave da API (HTTP ' . $httpStatus . ').');
                $message = 'A API recusou a chave configurada. Confira a configuração do servidor.';
            } elseif ($httpStatus === 404) {
                $message = 'Nenhum país foi encontrado com esse nome.';
            } elseif ($httpStatus < 200 || $httpStatus >= 300) {
                error_log('Resposta inesperada da REST Countries: HTTP ' . $httpStatus);
                $message = 'A API retornou uma resposta inesperada. Tente novamente mais tarde.';
            } else {
                try {
                    $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
                    $apiCountry = is_array($payload) ? first_api_country($payload) : null;
                } catch (JsonException $exception) {
                    error_log('JSON inválido da REST Countries: ' . $exception->getMessage());
                    $apiCountry = null;
                }

                if ($apiCountry === null) {
                    $message = 'A API não retornou dados válidos para esse país.';
                } else {
                    $name = nullable_api_text($apiCountry['names']['common'] ?? null, 100);
                    $iso = nullable_api_text($apiCountry['codes']['alpha_2'] ?? null, 2);
                    $capital = nullable_api_text($apiCountry['capitals'][0]['name'] ?? null, 100);
                    $region = nullable_api_text($apiCountry['region'] ?? null, 100);
                    $subregion = nullable_api_text($apiCountry['subregion'] ?? null, 100);
                    $population = $apiCountry['population'] ?? null;

                    if ($name === null || $iso === null || !preg_match('/^[A-Za-z]{2}$/', $iso)) {
                        error_log('REST Countries retornou país sem nome ou ISO válido.');
                        $message = 'A API retornou dados obrigatórios incompletos para esse país.';
                    } elseif ($population !== null && (!is_int($population) || $population < 0)) {
                        error_log('REST Countries retornou população inválida.');
                        $message = 'A API retornou uma população inválida para esse país.';
                    } else {
                        $country = [
                            'nome_pais' => $name,
                            'codigo_iso' => strtoupper($iso),
                            'capital' => $capital,
                            'populacao' => $population,
                            'regiao' => $region,
                            'sub_regiao' => $subregion,
                        ];

                        try {
                            $duplicate = $pdo->prepare('SELECT 1 FROM paises_api WHERE codigo_iso = :codigo_iso LIMIT 1');
                            $duplicate->execute(['codigo_iso' => $country['codigo_iso']]);

                            if ($duplicate->fetchColumn()) {
                                $message = "{$country['nome_pais']} já está cadastrado na importação da API.";
                                $messageType = 'warning';
                            } else {
                                $insert = $pdo->prepare('INSERT INTO paises_api (nome_pais, codigo_iso, capital, populacao, regiao, sub_regiao) VALUES (:nome_pais, :codigo_iso, :capital, :populacao, :regiao, :sub_regiao)');
                                $insert->execute($country);
                                $country['id_pais'] = (int) $pdo->lastInsertId();
                                $importedCountry = $country;
                                $message = 'País consultado e cadastrado com sucesso.';
                                $messageType = 'success';
                            }
                        } catch (PDOException $exception) {
                            error_log($exception->getMessage());
                            $message = (string) $exception->getCode() === '23000'
                                ? 'Este país já está cadastrado na importação da API.'
                                : 'Não foi possível gravar o país no banco de dados.';
                        }
                    }
                }
            }
        }
    }

render_header('Importar países', 'api-paises');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">REST Countries</p>
        <h1>Consultar e cadastrar país</h1>
        <p>Busque um país pelo nome para armazenar seus dados oficiais na base complementar.</p>
    </div>
    <a class="button button-secondary" href="<?= e(url('listar_paises.php')) ?>">Ver países importados</a>
</section>

<?php if ($message !== null): ?>
    <div class="flash flash-<?= e($messageType) ?>" role="alert"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($importedCountry !== null): ?>
    <section class="content-card">
        <h2>Dados importados</h2>
        <dl class="detail-list">
            <div><dt>ID</dt><dd><?= (int) $importedCountry['id_pais'] ?></dd></div>
            <div><dt>País</dt><dd><?= e($importedCountry['nome_pais']) ?></dd></div>
            <div><dt>Código ISO</dt><dd><?= e($importedCountry['codigo_iso']) ?></dd></div>
            <div><dt>Capital</dt><dd><?= e($importedCountry['capital'] ?? '—') ?></dd></div>
            <div><dt>População</dt><dd><?= $importedCountry['populacao'] === null ? '—' : e(number_format($importedCountry['populacao'], 0, ',', '.')) ?></dd></div>
            <div><dt>Região</dt><dd><?= e($importedCountry['regiao'] ?? '—') ?></dd></div>
            <div><dt>Sub-região</dt><dd><?= e($importedCountry['sub_regiao'] ?? '—') ?></dd></div>
        </dl>
    </section>
<?php endif; ?>
<p><a href="<?= e(url('api_paises.php')) ?>">← Voltar à consulta</a></p>
<?php render_footer(); ?>
