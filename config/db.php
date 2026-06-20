<?php
/**
 * CHECK-LINE — Conexión a Base de Datos
 * Usa PDO con prepared statements en TODO el sistema (previene inyección SQL).
 */

// --- Datos de conexión ---
// En producción, mover estos valores a variables de entorno (.env) fuera del raíz público.
define('DB_HOST', 'localhost:3306'); 
define('DB_NAME', 'checkline');
define('DB_USER', 'root');
define('DB_PASS', '1768');
define('DB_CHARSET', 'utf8mb4');

function getConexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        }         catch (PDOException $e) {
            die('ERROR REAL: ' . $e->getMessage());
        }
    }

    return $pdo;
}
