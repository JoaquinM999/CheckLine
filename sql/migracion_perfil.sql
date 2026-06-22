-- ============================================================================
-- CHECK-LINE — MIGRACIÓN: columna teléfono en usuarios
-- ============================================================================
-- Ejecutar UNA SOLA VEZ en phpMyAdmin sobre la BD checkline existente.
-- ============================================================================

ALTER TABLE usuarios
  ADD COLUMN telefono VARCHAR(20) NULL DEFAULT NULL
             COMMENT 'Teléfono de contacto opcional del pasajero'
             AFTER email;

-- Verificación:
-- DESCRIBE usuarios;
