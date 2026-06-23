<?php
/**
 * ============================================================================
 * CHECK-LINE — CANCELACIÓN DE RESERVA (RN #13)
 * ============================================================================
 * Regla de Negocio #13:
 *   Un usuario puede cancelar una reserva hasta 72 horas antes de la salida
 *   del vuelo, pasando la misma a estado "cancelada".
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requerirRol('pasajero');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mis_reservas.php');
    exit;
}

$id_reserva  = (int) ($_POST['id_reserva'] ?? 0);
$id_pasajero = (int) $_SESSION['id_usuario'];

if ($id_reserva <= 0) {
    setMensaje('danger', 'Identificador de reserva inválido.');
    header('Location: mis_reservas.php');
    exit;
}

try {
    $pdo = getConexion();

    // Obtener la reserva verificando que pertenece al pasajero logueado
    $stmt = $pdo->prepare("
        SELECT r.id_reserva, r.estado, r.id_vuelo,
               v.fecha_salida, v.hora_salida, v.codigo_vuelo
        FROM reservas r
        INNER JOIN vuelos v ON v.id_vuelo = r.id_vuelo
        WHERE r.id_reserva = :id_reserva
          AND r.id_usuario = :id_usuario
        LIMIT 1
    ");
    $stmt->execute(['id_reserva' => $id_reserva, 'id_usuario' => $id_pasajero]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        setMensaje('danger', 'La reserva no existe o no te pertenece.');
        header('Location: mis_reservas.php');
        exit;
    }

    // Verificar que la reserva esté en estado cancelable
    if (!in_array($reserva['estado'], ['pendiente_pago', 'Confirmada'], true)) {
        setMensaje('danger', 'Esta reserva ya fue cancelada o no puede modificarse.');
        header('Location: mis_reservas.php');
        exit;
    }

    // RN #13: verificar que faltan MÁS de 72 horas para la salida del vuelo
    $fechaHoraSalida = new DateTime($reserva['fecha_salida'] . ' ' . $reserva['hora_salida']);
    $ahora           = new DateTime();
    $diffSegundos    = $fechaHoraSalida->getTimestamp() - $ahora->getTimestamp();
    $horasRestantes  = $diffSegundos / 3600;

    if ($horasRestantes <= 72) {
        $horasStr = $horasRestantes > 0
            ? number_format($horasRestantes, 1) . ' horas'
            : 'menos de 0 horas (vuelo ya salió o está por salir)';

        setMensaje('danger',
            "No se puede cancelar: quedan $horasStr para la salida del vuelo " .
            "{$reserva['codigo_vuelo']}. El plazo límite de cancelación es 72 horas antes del despegue."
        );
        header('Location: mis_reservas.php');
        exit;
    }

    // Todo OK — cancelar en transacción y devolver el asiento
    $pdo->beginTransaction();

    $stmtCancelar = $pdo->prepare("
        UPDATE reservas
        SET estado = 'cancelada', fecha_cancelacion = NOW()
        WHERE id_reserva = :id_reserva
          AND id_usuario  = :id_usuario
    ");
    $stmtCancelar->execute([
        'id_reserva' => $id_reserva,
        'id_usuario' => $id_pasajero,
    ]);

    // Devolver el asiento al cupo disponible del vuelo
    $stmtAsiento = $pdo->prepare("
        UPDATE vuelos
        SET asientos_disponibles = asientos_disponibles + 1
        WHERE id_vuelo = :id_vuelo
    ");
    $stmtAsiento->execute(['id_vuelo' => $reserva['id_vuelo']]);

    $pdo->commit();

    setMensaje('success',
        "Reserva del vuelo {$reserva['codigo_vuelo']} cancelada correctamente. " .
        "El asiento fue liberado."
    );

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al cancelar reserva: ' . $e->getMessage());
    setMensaje('danger', 'Ocurrió un error al procesar la cancelación. Intentá nuevamente.');
}

header('Location: mis_reservas.php');
exit;
