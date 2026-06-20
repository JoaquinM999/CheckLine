USE checkline;

-- Usuarios de prueba (passwords reales generados con password_hash())
INSERT INTO usuarios (nombre, apellido, email, password_hash, id_rol, activo) VALUES
('Lucía', 'González', 'admin@checkline.com', '$2y$10$7d26eI75T4Qp612z7pv/EOiXM/kMkT2nx2bRjCxQ.RcgEHxha5WqS', 1, 1),
('Rodrigo', 'Pérez', 'ceo.airtest@checkline.com', '$2y$10$1PRAwaFhGXcOqmnlzxR/OOE1TMz1f5aJ2bsBVq2ket8BxrOcHMiYO', 2, 1),
('Carla', 'Funes', 'ceo.flynow@checkline.com', '$2y$10$1PRAwaFhGXcOqmnlzxR/OOE1TMz1f5aJ2bsBVq2ket8BxrOcHMiYO', 2, 1),
('Valentina', 'López', 'pasajero@checkline.com', '$2y$10$HacL3tKbj0z10GOc5Cgb0ODm1/zw8ZPlg2m2lu/6NA/18zCqUiSCK', 3, 1);

-- Aerolíneas de ejemplo (coinciden con los Bocetos del Punto 7)
INSERT INTO aerolineas (nombre, codigo, pais, id_ceo) VALUES
('AirTest', 'AT', 'Argentina', 2),
('FlyNow', 'FN', 'Argentina', 3);
