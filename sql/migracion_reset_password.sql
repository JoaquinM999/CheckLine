-- ============================================================================
-- CHECK-LINE — MIGRACIÓN: columnas para recuperar contraseña
-- ============================================================================
-- Ejecutar este script UNA SOLA VEZ sobre la BD checkline ya existente.
-- Si ya tenés la tabla vacía podés también re-ejecutar checkline_modelo_fisico.sql
-- (ya incluye estas columnas en la definición actualizada de abajo).
-- ============================================================================

ALTER TABLE usuarios
  ADD COLUMN token_reset         VARCHAR(100) NULL DEFAULT NULL
              COMMENT 'Token de un solo uso para restablecer contraseña (expira en 1h)'
              AFTER token_validacion,

  ADD COLUMN token_reset_expira  DATETIME     NULL DEFAULT NULL
              COMMENT 'Fecha/hora de expiración del token de reset'
              AFTER token_reset;

-- Verificación: debería mostrar las nuevas columnas
-- DESCRIBE usuarios;
