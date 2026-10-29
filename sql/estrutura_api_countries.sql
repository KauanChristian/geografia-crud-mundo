-- Estrutura complementar para a atividade de importação REST Countries.
-- Não altera a tabela `paises` original, que possui vínculos obrigatórios
-- com continentes, cidades e governantes.
USE bd_mundo;

CREATE TABLE IF NOT EXISTS paises_api (
    id_pais INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_pais VARCHAR(100) NOT NULL,
    codigo_iso CHAR(2) NOT NULL,
    capital VARCHAR(100) NULL,
    populacao BIGINT UNSIGNED NULL,
    regiao VARCHAR(100) NULL,
    sub_regiao VARCHAR(100) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_paises_api_codigo_iso UNIQUE (codigo_iso),
    CONSTRAINT ck_paises_api_populacao CHECK (populacao IS NULL OR populacao >= 0),
    INDEX idx_paises_api_nome (nome_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
