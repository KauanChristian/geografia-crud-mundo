-- Redefine a senha do administrador já cadastrado para SenhaInicial0.
-- Execute este script somente quando desejar restaurar essa credencial inicial.
USE bd_mundo;

UPDATE usuarios
SET senha_hash = '$2y$10$4n2YMUxnyKYhZlPRMiXCxOCAznV4CSwtn4jshGgtYRfSqul/nHYZu',
    tentativas_falhas = 0,
    bloqueado_em = NULL
WHERE login = 'admin';
