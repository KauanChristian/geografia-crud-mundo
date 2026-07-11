<?php
declare(strict_types=1);

/**
 * Centraliza apenas os metadados seguros usados pelas telas. Nenhum nome de
 * tabela ou coluna é aceito diretamente da URL.
 *
 * @return array<string, array<string, mixed>>
 */
function entity_definitions(): array
{
    static $definitions = null;

    if ($definitions !== null) {
        return $definitions;
    }

    $definitions = [
        'continentes' => [
            'table' => 'continentes',
            'singular' => 'continente',
            'plural' => 'Continentes',
            'page' => 'continentes.php',
            'description' => 'Registre os grandes blocos geográficos do mundo.',
            'query' => "SELECT c.*, COUNT(p.id) AS total_paises_calculado
                        FROM continentes c
                        LEFT JOIN paises p ON p.continente_id = c.id
                        GROUP BY c.id, c.nome, c.populacao, c.area_km2, c.total_paises, c.criado_em, c.atualizado_em
                        ORDER BY c.nome",
            'columns' => [
                ['key' => 'nome', 'label' => 'Nome', 'primary' => true],
                ['key' => 'populacao', 'label' => 'População'],
                ['key' => 'area_km2', 'label' => 'Área'],
                ['key' => 'total_paises_calculado', 'label' => 'Países'],
            ],
        ],
        'paises' => [
            'table' => 'paises',
            'singular' => 'país',
            'plural' => 'Países',
            'page' => 'paises.php',
            'description' => 'Organize países, seus dados e a cidade mais populosa cadastrada.',
            'query' => "SELECT p.*, c.nome AS continente_nome,
                        (SELECT COUNT(*) FROM cidades ci WHERE ci.pais_id = p.id) AS total_cidades,
                        (SELECT ci.nome FROM cidades ci WHERE ci.pais_id = p.id ORDER BY ci.populacao DESC, ci.nome ASC LIMIT 1) AS cidade_mais_populosa,
                        (SELECT g.nome FROM governantes g WHERE g.pais_id = p.id AND g.data_fim_mandato IS NULL ORDER BY g.data_inicio_mandato DESC LIMIT 1) AS governante_nome
                        FROM paises p
                        INNER JOIN continentes c ON c.id = p.continente_id
                        ORDER BY p.nome",
            'columns' => [
                ['key' => 'nome', 'label' => 'Nome', 'primary' => true],
                ['key' => 'continente_nome', 'label' => 'Continente'],
                ['key' => 'governante_nome', 'label' => 'Governante atual'],
                ['key' => 'total_cidades', 'label' => 'Cidades'],
                ['key' => 'cidade_mais_populosa', 'label' => 'Cidade mais populosa'],
                ['key' => 'moeda', 'label' => 'Moeda'],
            ],
        ],
        'cidades' => [
            'table' => 'cidades',
            'singular' => 'cidade',
            'plural' => 'Cidades',
            'page' => 'cidades.php',
            'description' => 'Cadastre cidades sempre vinculadas a um país existente.',
            'query' => "SELECT ci.*, p.nome AS pais_nome, co.nome AS continente_nome,
                        (SELECT g.nome FROM governantes g WHERE g.cidade_id = ci.id AND g.data_fim_mandato IS NULL ORDER BY g.data_inicio_mandato DESC LIMIT 1) AS governante_nome
                        FROM cidades ci
                        INNER JOIN paises p ON p.id = ci.pais_id
                        INNER JOIN continentes co ON co.id = p.continente_id
                        ORDER BY ci.nome, p.nome",
            'columns' => [
                ['key' => 'nome', 'label' => 'Nome', 'primary' => true],
                ['key' => 'pais_nome', 'label' => 'País'],
                ['key' => 'populacao', 'label' => 'População'],
                ['key' => 'governante_nome', 'label' => 'Governante atual'],
                ['key' => 'data_fundacao', 'label' => 'Fundação'],
            ],
        ],
        'governantes' => [
            'table' => 'governantes',
            'singular' => 'governante',
            'plural' => 'Governantes',
            'page' => 'governantes.php',
            'description' => 'Vincule cada mandato a um país ou a uma cidade.',
            'query' => "SELECT g.*, TIMESTAMPDIFF(YEAR, g.data_nascimento, CURDATE()) AS idade_atual,
                        CASE WHEN g.pais_id IS NOT NULL THEN 'País' ELSE 'Cidade' END AS tipo_vinculo,
                        COALESCE(p.nome, CONCAT(ci.nome, ' — ', pa.nome)) AS vinculo_nome
                        FROM governantes g
                        LEFT JOIN paises p ON p.id = g.pais_id
                        LEFT JOIN cidades ci ON ci.id = g.cidade_id
                        LEFT JOIN paises pa ON pa.id = ci.pais_id
                        ORDER BY g.nome",
            'columns' => [
                ['key' => 'nome', 'label' => 'Nome', 'primary' => true],
                ['key' => 'partido_politico', 'label' => 'Partido político'],
                ['key' => 'idade_atual', 'label' => 'Idade'],
                ['key' => 'tipo_vinculo', 'label' => 'Vínculo'],
                ['key' => 'vinculo_nome', 'label' => 'Local governado'],
                ['key' => 'data_inicio_mandato', 'label' => 'Início'],
            ],
        ],
    ];

    return $definitions;
}

/** @return array<string, mixed>|null */
function entity_definition(string $entity): ?array
{
    $definitions = entity_definitions();
    return $definitions[$entity] ?? null;
}

function entity_page(string $entity): string
{
    $definition = entity_definition($entity);
    return $definition['page'] ?? 'index.php';
}

function form_url(string $entity, ?int $id = null): string
{
    $query = 'formulario.php?entidade=' . rawurlencode($entity);

    if ($id !== null) {
        $query .= '&id=' . $id;
    }

    return url($query);
}
