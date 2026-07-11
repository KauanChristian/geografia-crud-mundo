-- Dados opcionais para demonstrar a aplicação após importar bd_mundo.sql.
-- Pode ser executado mais de uma vez; os registros duplicados são ignorados.

USE bd_mundo;

INSERT IGNORE INTO continentes (nome, populacao, area_km2) VALUES
    ('América do Sul', 434254119, 17840000.00),
    ('Europa', 744398000, 10180000.00),
    ('Ásia', 4750000000, 44579000.00);

SELECT id INTO @continente_america_sul FROM continentes WHERE nome = 'América do Sul' LIMIT 1;
SELECT id INTO @continente_europa FROM continentes WHERE nome = 'Europa' LIMIT 1;
SELECT id INTO @continente_asia FROM continentes WHERE nome = 'Ásia' LIMIT 1;

INSERT IGNORE INTO paises (continente_id, nome, populacao, area_km2, idioma, clima, regime_politico, moeda)
VALUES (@continente_america_sul, 'Brasil', 203080756, 8515767.05, 'Português', 'Tropical', 'República federativa presidencialista', 'Real brasileiro');

INSERT IGNORE INTO paises (continente_id, nome, populacao, area_km2, idioma, clima, regime_politico, moeda)
VALUES (@continente_america_sul, 'Argentina', 46654581, 2780400.00, 'Espanhol', 'Temperado', 'República federal presidencialista', 'Peso argentino');

INSERT IGNORE INTO paises (continente_id, nome, populacao, area_km2, idioma, clima, regime_politico, moeda)
VALUES (@continente_europa, 'Portugal', 10467366, 92212.00, 'Português', 'Mediterrâneo', 'República semipresidencialista', 'Euro');

INSERT IGNORE INTO paises (continente_id, nome, populacao, area_km2, idioma, clima, regime_politico, moeda)
VALUES (@continente_asia, 'Japão', 123900000, 377975.00, 'Japonês', 'Temperado', 'Monarquia constitucional parlamentarista', 'Iene');

INSERT IGNORE INTO cidades (pais_id, nome, populacao, area_km2, clima, data_fundacao)
SELECT id, 'São Paulo', 11451999, 1521.11, 'Subtropical úmido', '1554-01-25'
FROM paises WHERE nome = 'Brasil';

INSERT IGNORE INTO cidades (pais_id, nome, populacao, area_km2, clima, data_fundacao)
SELECT id, 'Rio de Janeiro', 6211423, 1200.33, 'Tropical atlântico', '1565-03-01'
FROM paises WHERE nome = 'Brasil';

INSERT IGNORE INTO cidades (pais_id, nome, populacao, area_km2, clima, data_fundacao)
SELECT id, 'Buenos Aires', 3120612, 203.00, 'Temperado', '1536-02-02'
FROM paises WHERE nome = 'Argentina';

INSERT IGNORE INTO cidades (pais_id, nome, populacao, area_km2, clima, data_fundacao)
SELECT id, 'Lisboa', 545923, 100.05, 'Mediterrâneo', '1147-10-25'
FROM paises WHERE nome = 'Portugal';

INSERT IGNORE INTO cidades (pais_id, nome, populacao, area_km2, clima, data_fundacao)
SELECT id, 'Tóquio', 14100000, 2194.07, 'Subtropical úmido', '1603-03-24'
FROM paises WHERE nome = 'Japão';

-- Nomes fictícios, usados exclusivamente como demonstração do relacionamento.
INSERT IGNORE INTO governantes (nome, partido_politico, data_nascimento, idade, data_inicio_mandato, data_fim_mandato, pais_id, cidade_id)
SELECT 'Alex Silva', 'Partido Exemplo', '1974-04-12', 52, '2024-01-01', NULL, id, NULL
FROM paises WHERE nome = 'Brasil';

INSERT IGNORE INTO governantes (nome, partido_politico, data_nascimento, idade, data_inicio_mandato, data_fim_mandato, pais_id, cidade_id)
SELECT 'Marina Costa', 'Movimento Cidadão', '1980-09-20', 45, '2025-01-01', NULL, NULL, id
FROM cidades WHERE nome = 'Lisboa';
