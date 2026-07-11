-- Banco de dados da aplicação Mundo.
-- Requer MySQL 8.0.16+ para aplicar as regras CHECK.

CREATE DATABASE IF NOT EXISTS bd_mundo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bd_mundo;

CREATE TABLE IF NOT EXISTS continentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
    total_paises INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_continentes_nome UNIQUE (nome),
    CONSTRAINT ck_continentes_populacao CHECK (populacao >= 0),
    CONSTRAINT ck_continentes_area CHECK (area_km2 >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    continente_id INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
    idioma VARCHAR(150) NOT NULL,
    clima VARCHAR(100) NOT NULL,
    regime_politico VARCHAR(120) NOT NULL,
    moeda VARCHAR(100) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_paises_nome UNIQUE (nome),
    CONSTRAINT ck_paises_populacao CHECK (populacao >= 0),
    CONSTRAINT ck_paises_area CHECK (area_km2 >= 0),
    INDEX idx_paises_continente (continente_id),
    CONSTRAINT fk_paises_continentes
        FOREIGN KEY (continente_id) REFERENCES continentes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pais_id INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
    clima VARCHAR(100) NOT NULL,
    data_fundacao DATE NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_cidades_pais_nome UNIQUE (pais_id, nome),
    CONSTRAINT ck_cidades_populacao CHECK (populacao >= 0),
    CONSTRAINT ck_cidades_area CHECK (area_km2 >= 0),
    INDEX idx_cidades_pais (pais_id),
    CONSTRAINT fk_cidades_paises
        FOREIGN KEY (pais_id) REFERENCES paises(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS governantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    partido_politico VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    idade TINYINT UNSIGNED NOT NULL DEFAULT 0,
    data_inicio_mandato DATE NOT NULL,
    data_fim_mandato DATE NULL,
    pais_id INT UNSIGNED NULL,
    cidade_id INT UNSIGNED NULL,
    -- Valor derivado: permite somente um mandato sem data final por local.
    mandato_aberto TINYINT GENERATED ALWAYS AS (
        CASE WHEN data_fim_mandato IS NULL THEN 1 ELSE NULL END
    ) STORED,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_governantes_vinculo CHECK (
        (pais_id IS NOT NULL AND cidade_id IS NULL)
        OR (pais_id IS NULL AND cidade_id IS NOT NULL)
    ),
    CONSTRAINT ck_governantes_datas CHECK (
        data_fim_mandato IS NULL OR data_fim_mandato >= data_inicio_mandato
    ),
    INDEX idx_governantes_pais (pais_id),
    INDEX idx_governantes_cidade (cidade_id),
    CONSTRAINT uq_governante_pais_aberto UNIQUE (pais_id, mandato_aberto),
    CONSTRAINT uq_governante_cidade_aberto UNIQUE (cidade_id, mandato_aberto),
    CONSTRAINT fk_governantes_paises
        FOREIGN KEY (pais_id) REFERENCES paises(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_governantes_cidades
        FOREIGN KEY (cidade_id) REFERENCES cidades(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

DROP TRIGGER IF EXISTS trg_paises_ai;
DROP TRIGGER IF EXISTS trg_paises_ad;
DROP TRIGGER IF EXISTS trg_paises_au;

DELIMITER //

CREATE TRIGGER trg_paises_ai
AFTER INSERT ON paises
FOR EACH ROW
BEGIN
    UPDATE continentes
    SET total_paises = total_paises + 1
    WHERE id = NEW.continente_id;
END//

CREATE TRIGGER trg_paises_ad
AFTER DELETE ON paises
FOR EACH ROW
BEGIN
    UPDATE continentes
    SET total_paises = IF(total_paises > 0, total_paises - 1, 0)
    WHERE id = OLD.continente_id;
END//

CREATE TRIGGER trg_paises_au
AFTER UPDATE ON paises
FOR EACH ROW
BEGIN
    IF NOT (NEW.continente_id <=> OLD.continente_id) THEN
        UPDATE continentes
        SET total_paises = IF(total_paises > 0, total_paises - 1, 0)
        WHERE id = OLD.continente_id;

        UPDATE continentes
        SET total_paises = total_paises + 1
        WHERE id = NEW.continente_id;
    END IF;
END//

DELIMITER ;
