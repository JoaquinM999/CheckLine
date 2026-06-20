<?php
/**
 * CHECK-LINE — Procesamiento ABMC Aerolíneas
 * Patrón Post-Redirect-Get: procesa, guarda mensaje de feedback en sesión,
 * y redirige siempre a aerolineas.php (evita el reenvío de formularios con F5).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/aerolineas.php');
    exit;
}

$pdo = getConexion();
$accion = $_POST['accion'] ?? '';

// =====================================================================
// VALIDACIÓN SERVER-SIDE (obligatoria — nunca confiar solo en el cliente)
// =====================================================================
function validarDatosAerolinea(array $datos, PDO $pdo, ?int $idExcluir = null): array
{
    $errores = [];

    $nombre = trim($datos['nombre'] ?? '');
    $codigo = strtoupper(trim($datos['codigo'] ?? ''));
    $pais   = trim($datos['pais'] ?? '');
    $idCeo  = (int) ($datos['id_ceo'] ?? 0);

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre es obligatorio y debe tener hasta 100 caracteres.';
    }
    if (!preg_match('/^[A-Z0-9]{2,3}$/', $codigo)) {
        $errores[] = 'El código debe tener 2 o 3 caracteres alfanuméricos (ej: AT).';
    }
    if ($pais === '' || mb_strlen($pais) > 60) {
        $errores[] = 'El país es obligatorio y debe tener hasta 60 caracteres.';
    }
    if ($idCeo <= 0) {
        $errores[] = 'Debe seleccionar un CEO válido.';
    } else {
        // Verificar que el usuario seleccionado realmente tenga rol CEO
        $stmt = $pdo->prepare("
            SELECT u.id_usuario FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.id_usuario = :id AND r.nombre_rol = 'ceo'
        ");
        $stmt->execute(['id' => $idCeo]);
        if (!$stmt->fetch()) {
            $errores[] = 'El usuario seleccionado no tiene rol de CEO.';
        }
    }

    // Código único (excluyendo el propio registro en caso de edición)
    if (empty($errores)) {
        $sqlDup = "SELECT id_aerolinea FROM aerolineas WHERE codigo = :codigo";
        $params = ['codigo' => $codigo];
        if ($idExcluir !== null) {
            $sqlDup .= " AND id_aerolinea != :id";
            $params['id'] = $idExcluir;
        }
        $stmt = $pdo->prepare($sqlDup);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errores[] = "Ya existe otra aerolínea con el código \"$codigo\".";
        }
    }

    return [
        'errores' => $errores,
        'nombre'  => $nombre,
        'codigo'  => $codigo,
        'pais'    => $pais,
        'id_ceo'  => $idCeo,
    ];
}

// =====================================================================
// ALTA
// =====================================================================
if ($accion === 'crear') {
    $v = validarDatosAerolinea($_POST, $pdo);

    if (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO aerolineas (nombre, codigo, pais, id_ceo)
            VALUES (:nombre, :codigo, :pais, :id_ceo)
        ");
        $stmt->execute([
            'nombre' => $v['nombre'],
            'codigo' => $v['codigo'],
            'pais'   => $v['pais'],
            'id_ceo' => $v['id_ceo'],
        ]);
        setMensaje('success', "Aerolínea \"{$v['nombre']}\" creada correctamente.");
    }
}

// =====================================================================
// MODIFICACIÓN
// =====================================================================
elseif ($accion === 'editar') {
    $idAerolinea = (int) ($_POST['id_aerolinea'] ?? 0);
    $v = validarDatosAerolinea($_POST, $pdo, $idAerolinea);

    if ($idAerolinea <= 0) {
        setMensaje('danger', 'Identificador de aerolínea inválido.');
    } elseif (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            UPDATE aerolineas
            SET nombre = :nombre, codigo = :codigo, pais = :pais, id_ceo = :id_ceo
            WHERE id_aerolinea = :id
        ");
        $stmt->execute([
            'nombre' => $v['nombre'],
            'codigo' => $v['codigo'],
            'pais'   => $v['pais'],
            'id_ceo' => $v['id_ceo'],
            'id'     => $idAerolinea,
        ]);
        setMensaje('success', "Aerolínea \"{$v['nombre']}\" actualizada correctamente.");
    }
}

// =====================================================================
// BAJA
// =====================================================================
elseif ($accion === 'eliminar') {
    $idAerolinea = (int) ($_POST['id_aerolinea'] ?? 0);

    if ($idAerolinea <= 0) {
        setMensaje('danger', 'Identificador de aerolínea inválido.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM aerolineas WHERE id_aerolinea = :id");
            $stmt->execute(['id' => $idAerolinea]);

            if ($stmt->rowCount() > 0) {
                setMensaje('success', 'Aerolínea eliminada correctamente.');
            } else {
                setMensaje('danger', 'La aerolínea ya no existe.');
            }
        } catch (PDOException $e) {
            // Código 1451 de MySQL = violación de FK (tiene vuelos asociados)
            if (($e->errorInfo[1] ?? null) === 1451) {
                setMensaje('danger', 'No se puede eliminar: la aerolínea tiene vuelos asociados. Eliminá o reasigná esos vuelos primero.');
            } else {
                error_log('Error al eliminar aerolínea: ' . $e->getMessage());
                setMensaje('danger', 'Ocurrió un error al eliminar la aerolínea.');
            }
        }
    }
}

// =====================================================================
else {
    setMensaje('danger', 'Acción no reconocida.');
}

header('Location: /admin/aerolineas.php');
exit;
