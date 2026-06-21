<?php
/**
 * CHECK-LINE — Procesamiento ABMC Vuelos (CEO)
 * CRÍTICO: toda operación de editar/eliminar verifica que el vuelo
 * pertenezca a la aerolínea del CEO logueado (no confiar en el id_vuelo del POST).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vuelos.php');
    exit;
}

$pdo = getConexion();

// Aerolínea del CEO logueado (vuelve a resolverse acá, nunca confiar en datos del cliente)
$stmtAerolinea = $pdo->prepare("SELECT id_aerolinea FROM aerolineas WHERE id_ceo = :id_ceo");
$stmtAerolinea->execute(['id_ceo' => $_SESSION['id_usuario']]);
$aerolinea = $stmtAerolinea->fetch();

if (!$aerolinea) {
    setMensaje('danger', 'No tenés una aerolínea asignada.');
    header('Location: vuelos.php');
    exit;
}
$idAerolinea = (int) $aerolinea['id_aerolinea'];

$accion = $_POST['accion'] ?? '';

// =====================================================================
// VALIDACIÓN SERVER-SIDE
// =====================================================================
function validarDatosVuelo(array $datos, PDO $pdo, ?int $idExcluir = null): array
{
    $errores = [];

    $codigo   = strtoupper(trim($datos['codigo_vuelo'] ?? ''));
    $origen   = trim($datos['origen'] ?? '');
    $destino  = trim($datos['destino'] ?? '');
    $fSalida  = trim($datos['fecha_salida'] ?? '');
    $hSalida  = trim($datos['hora_salida'] ?? '');
    $fLlegada = trim($datos['fecha_llegada'] ?? '');
    $hLlegada = trim($datos['hora_llegada'] ?? '');
    $precio   = $datos['precio'] ?? '';
    $asientos = (int) ($datos['asientos_totales'] ?? 0);

    if (!preg_match('/^[A-Z0-9\-]{3,10}$/', $codigo)) {
        $errores[] = 'El código de vuelo debe tener entre 3 y 10 caracteres alfanuméricos (ej: AT-001).';
    }
    if ($origen === '' || mb_strlen($origen) > 60) {
        $errores[] = 'El origen es obligatorio (máx. 60 caracteres).';
    }
    if ($destino === '' || mb_strlen($destino) > 60) {
        $errores[] = 'El destino es obligatorio (máx. 60 caracteres).';
    }
    if ($origen !== '' && $destino !== '' && mb_strtolower($origen) === mb_strtolower($destino)) {
        $errores[] = 'El origen y el destino no pueden ser el mismo lugar.';
    }

    // Fechas válidas
    $tsSalida  = ($fSalida && $hSalida) ? strtotime("$fSalida $hSalida") : false;
    $tsLlegada = ($fLlegada && $hLlegada) ? strtotime("$fLlegada $hLlegada") : false;

    if ($tsSalida === false) {
        $errores[] = 'Fecha/hora de salida inválida.';
    }
    if ($tsLlegada === false) {
        $errores[] = 'Fecha/hora de llegada inválida.';
    }
    if ($tsSalida !== false && $tsLlegada !== false && $tsLlegada <= $tsSalida) {
        $errores[] = 'La llegada debe ser posterior a la salida.';
    }
    // Solo en alta exigimos que el vuelo sea a futuro (editar un vuelo pasado debe poder corregirse)
    if ($idExcluir === null && $tsSalida !== false && $tsSalida < time()) {
        $errores[] = 'La fecha de salida no puede ser en el pasado.';
    }

    if (!is_numeric($precio) || (float) $precio <= 0) {
        $errores[] = 'El precio debe ser un número mayor a 0.';
    }
    if ($asientos < 1 || $asientos > 500) {
        $errores[] = 'Los asientos totales deben estar entre 1 y 500.';
    }

    // Código de vuelo único en todo el sistema
    if (empty($errores)) {
        $sqlDup = "SELECT id_vuelo FROM vuelos WHERE codigo_vuelo = :codigo";
        $params = ['codigo' => $codigo];
        if ($idExcluir !== null) {
            $sqlDup .= " AND id_vuelo != :id";
            $params['id'] = $idExcluir;
        }
        $stmt = $pdo->prepare($sqlDup);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errores[] = "Ya existe otro vuelo con el código \"$codigo\".";
        }
    }

    return [
        'errores'          => $errores,
        'codigo_vuelo'     => $codigo,
        'origen'           => $origen,
        'destino'          => $destino,
        'fecha_salida'     => $fSalida,
        'hora_salida'      => $hSalida,
        'fecha_llegada'    => $fLlegada,
        'hora_llegada'     => $hLlegada,
        'precio'           => (float) $precio,
        'asientos_totales' => $asientos,
    ];
}

// =====================================================================
// ALTA
// =====================================================================
if ($accion === 'crear') {
    $v = validarDatosVuelo($_POST, $pdo);

    if (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO vuelos
                (id_aerolinea, codigo_vuelo, origen, destino, fecha_salida, hora_salida,
                 fecha_llegada, hora_llegada, precio, asientos_totales, asientos_disponibles, estado)
            VALUES
                (:id_aerolinea, :codigo, :origen, :destino, :f_sal, :h_sal,
                 :f_lleg, :h_lleg, :precio, :asientos_tot, :asientos_disp, 'activo')
        ");
        $stmt->execute([
            'id_aerolinea'  => $idAerolinea,
            'codigo'        => $v['codigo_vuelo'],
            'origen'        => $v['origen'],
            'destino'       => $v['destino'],
            'f_sal'         => $v['fecha_salida'],
            'h_sal'         => $v['hora_salida'],
            'f_lleg'        => $v['fecha_llegada'],
            'h_lleg'        => $v['hora_llegada'],
            'precio'        => $v['precio'],
            'asientos_tot'  => $v['asientos_totales'],
            'asientos_disp' => $v['asientos_totales'],
        ]);
        setMensaje('success', "Vuelo \"{$v['codigo_vuelo']}\" creado correctamente.");
    }
}

// =====================================================================
// MODIFICACIÓN
// =====================================================================
elseif ($accion === 'editar') {
    $idVuelo = (int) ($_POST['id_vuelo'] ?? 0);
    $estado  = $_POST['estado'] ?? 'activo';

    if (!in_array($estado, ['activo', 'cancelado', 'finalizado'], true)) {
        $estado = 'activo';
    }

    // Verificación de PERTENENCIA — el vuelo debe ser de la aerolínea de este CEO
    $stmtVuelo = $pdo->prepare("
        SELECT asientos_totales, asientos_disponibles
        FROM vuelos
        WHERE id_vuelo = :id AND id_aerolinea = :id_aerolinea
    ");
    $stmtVuelo->execute(['id' => $idVuelo, 'id_aerolinea' => $idAerolinea]);
    $vueloActual = $stmtVuelo->fetch();

    if (!$vueloActual) {
        setMensaje('danger', 'El vuelo no existe o no pertenece a tu aerolínea.');
        header('Location: vuelos.php');
        exit;
    }

    $v = validarDatosVuelo($_POST, $pdo, $idVuelo);

    // Validación adicional: no bajar la capacidad por debajo de lo ya ocupado
    $ocupados = (int) $vueloActual['asientos_totales'] - (int) $vueloActual['asientos_disponibles'];
    if (empty($v['errores']) && $v['asientos_totales'] < $ocupados) {
        $v['errores'][] = "No se puede bajar la capacidad a {$v['asientos_totales']}: ya hay $ocupados asientos ocupados por reservas.";
    }

    if (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $nuevosDisponibles = $v['asientos_totales'] - $ocupados;

        $stmt = $pdo->prepare("
            UPDATE vuelos
            SET codigo_vuelo = :codigo, origen = :origen, destino = :destino,
                fecha_salida = :f_sal, hora_salida = :h_sal,
                fecha_llegada = :f_lleg, hora_llegada = :h_lleg,
                precio = :precio, asientos_totales = :asientos,
                asientos_disponibles = :disponibles, estado = :estado
            WHERE id_vuelo = :id AND id_aerolinea = :id_aerolinea
        ");
        $stmt->execute([
            'codigo'      => $v['codigo_vuelo'],
            'origen'      => $v['origen'],
            'destino'     => $v['destino'],
            'f_sal'       => $v['fecha_salida'],
            'h_sal'       => $v['hora_salida'],
            'f_lleg'      => $v['fecha_llegada'],
            'h_lleg'      => $v['hora_llegada'],
            'precio'      => $v['precio'],
            'asientos'    => $v['asientos_totales'],
            'disponibles' => $nuevosDisponibles,
            'estado'      => $estado,
            'id'          => $idVuelo,
            'id_aerolinea'=> $idAerolinea,
        ]);
        setMensaje('success', "Vuelo \"{$v['codigo_vuelo']}\" actualizado correctamente.");
    }
}

// =====================================================================
// BAJA
// =====================================================================
elseif ($accion === 'eliminar') {
    $idVuelo = (int) ($_POST['id_vuelo'] ?? 0);

    try {
        // El WHERE con id_aerolinea asegura que un CEO no pueda borrar vuelos ajenos
        $stmt = $pdo->prepare("DELETE FROM vuelos WHERE id_vuelo = :id AND id_aerolinea = :id_aerolinea");
        $stmt->execute(['id' => $idVuelo, 'id_aerolinea' => $idAerolinea]);

        if ($stmt->rowCount() > 0) {
            setMensaje('success', 'Vuelo eliminado correctamente.');
        } else {
            setMensaje('danger', 'El vuelo no existe o no pertenece a tu aerolínea.');
        }
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === 1451) {
            setMensaje('danger', 'No se puede eliminar: el vuelo tiene reservas asociadas.');
        } else {
            error_log('Error al eliminar vuelo: ' . $e->getMessage());
            setMensaje('danger', 'Ocurrió un error al eliminar el vuelo.');
        }
    }
}

else {
    setMensaje('danger', 'Acción no reconocida.');
}

header('Location: vuelos.php');
exit;
