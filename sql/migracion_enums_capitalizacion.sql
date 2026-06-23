-- ============================================================================
-- CHECK-LINE — MIGRACIÓN: Actualización de ENUMs para coincidir con el PHP
-- ============================================================================
-- El código PHP usa valores capitalizados ('Confirmada', 'Pendiente', etc.)
-- Esta migración actualiza los ENUMs de la BD para que coincidan exactamente,
-- evitando problemas en MySQL con modo STRICT activado.
--
-- Ejecutar UNA SOLA VEZ sobre la BD checkline existente.
-- ============================================================================

USE checkline;

-- 1. Actualizar ENUM de reservas
ALTER TABLE reservas
  MODIFY COLUMN estado ENUM(
    'pendiente_pago',
    'Confirmada',
    'cancelada'
  ) NOT NULL DEFAULT 'pendiente_pago';

-- 2. Actualizar ENUM de promociones
ALTER TABLE promociones
  MODIFY COLUMN estado ENUM(
    'Pendiente',
    'Aprobada',
    'Denegada'
  ) NOT NULL DEFAULT 'Pendiente';

-- Verificación:
-- DESCRIBE reservas;
-- DESCRIBE promociones;
