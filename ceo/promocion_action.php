<?php
/**
 * CHECK-LINE — PROCESAMIENTO ABMC PROMOCIONES (CEO)
 * Verifica pertenencia de cada vuelo a la aerolínea del CEO.
 * Solo permite editar/eliminar promociones en estado 'Pendiente'.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: promociones.php');
    exit;
}

$pdo = getConexion();

// Aerolínea del CEO (siempre server-side)
$stmtAer = $pdo->prepare("SELECT id_aerolinea FROM aerolineas WHERE id_ceo = :id");
$stmtAer->execute(['id' => $_SESSION['id_usuario']]);
$aerolinea = $stmtAer->fetch();

if (!$aerolinea) {
    setMensaje('danger', 'No tenés una aerolínea asignada.');
    header('Location: promociones.php');
    exit;
}
$idAerolinea = (int) $aerolinea['id_aerolinea'];
$accion      = $_POST['accion'] ?? '';

function validarDatosPromocion(array $datos, PDO $pdo, int $idAerolinea): array
{
    $errores     = [];
    $idVuelo     = (int) ($datos['id_vuelo'] ?? 0);
    $descuento   = $datos['descuento_porcentaje'] ?? '';
    $fechaInicio = trim($datos['fecha_inicio'] ?? '');
    $fechaFin    = trim($datos['fecha_fin']    ?? '');

    if ($idVuelo <= 0) {
        $errores[] = 'Seleccioná un vuelo válido.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id_vuelo FROM vuelos
            WHERE id_vuelo = :id AND id_aerolinea = :id_aerolinea AND estado = 'activo'
        ");
        $stmt->execute(['id' => $idVuelo, 'id_aerolinea' => $idAerolinea]);
        if (!$stmt->fetch()) {
            $errores[] = 'El vuelo seleccionado no pertenece a tu aerolínea o no está activo.';
        }
    }
    if (!is_numeric($descuento) || (float) $descuento < 1 || (float) $descuento > 100) {
        $errores[] = 'El descuento debe ser un número entre 1 y 100.';
    }
    if ($fechaInicio === '' || !strtotime($fechaInicio)) {
        $errores[] = 'La fecha de inicio es inválida.';
    }
    if ($fechaFin === '' || !strtotime($fechaFin)) {
        $errores[] = 'La fecha de fin es inválida.';
    }
    if (empty($errores) && $fechaInicio > $fechaFin) {
        $errores[] = 'La fecha de inicio no puede ser posterior a la de fin.';
    }

    return [
        'errores'              => $errores,
        'id_vuelo'             => $idVuelo,
        'descuento_porcentaje' => (float) $descuento,
        'fecha_inicio'         => $fechaInicio,
        'fecha_fin'            => $fechaFin,
    ];
}

if ($accion === 'crear') {
    $v = validarDatosPromocion($_POST, $pdo, $idAerolinea);
    if (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO promociones
                (id_vuelo, descuento_porcentaje, fecha_inicio, fecha_fin,
                 estado, destacada, id_creador, fecha_creacion)
            VALUES
                (:vuelo, :descuento, :inicio, :fin,
                 'Pendiente', 0, :creador, NOW())
        ");
        $stmt->execute([
            'vuelo'     => $v['id_vuelo'],
            'descuento' => $v['descuento_porcentaje'],
            'inicio'    => $v['fecha_inicio'],
            'fin'       => $v['fecha_fin'],
            'creador'   => $_SESSION['id_usuario'],
        ]);
        setMensaje('success', 'Promoción enviada a auditoría. Aguardá la aprobación del Administrador.');
    }

} elseif ($accion === 'editar') {
    $idPromocion = (int) ($_POST['id_promocion'] ?? 0);

    if ($idPromocion <= 0) {
        setMensaje('danger', 'Identificador de promoción inválido.');
        header('Location: promociones.php');
        exit;
    }

    // Verificar pertenencia y que sea Pendiente
    $stmtCheck = $pdo->prepare("
        SELECT p.id_promocion, p.estado FROM promociones p
        INNER JOIN vuelos v ON v.id_vuelo = p.id_vuelo
        WHERE p.id_promocion = :id AND v.id_aerolinea = :id_aerolinea
    ");
    $stmtCheck->execute(['id' => $idPromocion, 'id_aerolinea' => $idAerolinea]);
    $promoActual = $stmtCheck->fetch();

    if (!$promoActual) {
        setMensaje('danger', 'La promoción no existe o no pertenece a tu aerolínea.');
    } elseif ($promoActual['estado'] !== 'Pendiente') {
        setMensaje('danger', 'Solo se pueden editar promociones en estado Pendiente.');
    } else {
        $v = validarDatosPromocion($_POST, $pdo, $idAerolinea);
        if (!empty($v['errores'])) {
            setMensaje('danger', implode(' ', $v['errores']));
        } else {
            $stmt = $pdo->prepare("
                UPDATE promociones
                SET id_vuelo = :vuelo,
                    descuento_porcentaje = :descuento,
                    fecha_inicio = :inicio,
                    fecha_fin    = :fin,
                    estado       = 'Pendiente',
                    id_aprobador = NULL,
                    fecha_resolucion = NULL
                WHERE id_promocion = :id
            ");
            $stmt->execute([
                'vuelo'     => $v['id_vuelo'],
                'descuento' => $v['descuento_porcentaje'],
                'inicio'    => $v['fecha_inicio'],
                'fin'       => $v['fecha_fin'],
                'id'        => $idPromocion,
            ]);
            setMensaje('success', 'Promoción actualizada y enviada nuevamente a auditoría.');
        }
    }

} elseif ($accion === 'eliminar') {
    $idPromocion = (int) ($_POST['id_promocion'] ?? 0);
    if ($idPromocion <= 0) {
        setMensaje('danger', 'Identificador de promoción inválido.');
    } else {
        $stmt = $pdo->prepare("
            DELETE p FROM promociones p
            INNER JOIN vuelos v ON v.id_vuelo = p.id_vuelo
            WHERE p.id_promocion = :id
              AND v.id_aerolinea = :id_aerolinea
              AND p.estado       = 'Pendiente'
        ");
        $stmt->execute(['id' => $idPromocion, 'id_aerolinea' => $idAerolinea]);
        setMensaje(
            $stmt->rowCount() > 0 ? 'success' : 'danger',
            $stmt->rowCount() > 0
                ? 'Promoción eliminada correctamente.'
                : 'No se pudo eliminar: la promoción no existe, no te pertenece, o ya fue auditada.'
        );
    }

} else {
    setMensaje('danger', 'Acción no reconocida.');
}

header('Location: promociones.php');
exit;
