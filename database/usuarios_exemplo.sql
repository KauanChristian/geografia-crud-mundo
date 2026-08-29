-- Contas de demonstração para uma base bd_mundo já existente.
-- A importação não altera as credenciais de usuários que já existem.
USE bd_mundo;

INSERT IGNORE INTO usuarios (login, nome, senha_hash, tipo, primeiro_acesso) VALUES
    ('admin', 'Administrador', '$2y$10$4n2YMUxnyKYhZlPRMiXCxOCAznV4CSwtn4jshGgtYRfSqul/nHYZu', 'A', 1),
    ('ana.souza', 'Ana Souza', '$2y$10$AkyJdr4Cdqtwl5IOt9HPIOBJHpMShZO/218WgogbMG2gefLyWyjfG', 'U', 1),
    ('bruno.lima', 'Bruno Lima', '$2y$10$hFt8RptfcmxDCiZDjZXqhOvi982zEc/sQ.axpFU08wnwIEkQZvdNO', 'U', 1),
    ('carla.mendes', 'Carla Mendes', '$2y$10$0jIrkind6GTNoxYIVatxeefyr8D/iC61x7DG0LC3bXzzVDZ171EeK', 'U', 1);
