<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - CONFIRMACIÓN DE RESERVA
 * ============================================================================
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Exigencia estricta: Solo pasajeros logueados pueden reservar
requerirRol('pasajero');
$id_pasajero = $_SESSION['id_usuario'];
$mensaje = obtenerYLimpiarMensaje();

$id_vuelo = $_GET['id_vuelo'] ?? $_POST['id_vuelo'] ?? null;

if (!$id_vuelo) {
    header('Location: vuelos.php');
    exit;
}

$pdo = getConexion();

// --- PROCESAMIENTO DE LA RESERVA (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precio_final = $_POST['precio_final'] ?? 0;

    try {
        // Iniciamos la Transacción (Paz operativa frente al caos)
        $pdo->beginTransaction();

        // 1. Verificamos disponibilidad en tiempo real bloqueando la fila
        $stmtCheck = $pdo->prepare("SELECT asientos_disponibles FROM vuelos WHERE id_vuelo = :id FOR UPDATE");
        $stmtCheck->execute(['id' => $id_vuelo]);
        $vueloLock = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$vueloLock || $vueloLock['asientos_disponibles'] <= 0) {
            throw new Exception("Lamentablemente, los asientos para este vuelo se han agotado.");
        }

        // 2. Registramos la reserva
        $stmtReserva = $pdo->prepare("
            INSERT INTO reservas (id_vuelo, id_usuario, estado, precio_final) 
            VALUES (:vuelo, :usuario, 'Confirmada', :precio)
        ");
        $stmtReserva->execute([
            'vuelo'   => $id_vuelo,
            'usuario' => $id_pasajero,
            'precio'  => $precio_final
        ]);

        // 3. Descontamos el asiento del itinerario
        $stmtAsiento = $pdo->prepare("
            UPDATE vuelos SET asientos_disponibles = asientos_disponibles - 1 
            WHERE id_vuelo = :id
        ");
        $stmtAsiento->execute(['id' => $id_vuelo]);

        // Sellamos la transacción
        $pdo->commit();
        
        setMensaje('success', 'Reserva confirmada con éxito. Verifique su itinerario en Mis Reservas.');
        header('Location: mis_reservas.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        setMensaje('danger', $e->getMessage());
    }
}

// --- VISUALIZACIÓN DEL DETALLE DEL VUELO (GET) ---
try {
    $sql = "
        SELECT 
            v.id_vuelo, v.codigo_vuelo, v.origen, v.destino, 
            v.fecha_salida, v.hora_salida, v.precio,
            a.nombre AS aerolinea,
            p.descuento_porcentaje
        FROM vuelos v
        INNER JOIN aerolineas a ON v.id_aerolinea = a.id_aerolinea
        LEFT JOIN promociones p ON v.id_vuelo = p.id_vuelo AND p.estado = 'Aprobada'
        WHERE v.id_vuelo = :id AND v.estado = 'activo'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id_vuelo]);
    $vuelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vuelo) {
        die("El vuelo solicitado no se encuentra operativo.");
    }

    $precio_base = (float) $vuelo['precio'];
    $descuento = $vuelo['descuento_porcentaje'] ? (float) $vuelo['descuento_porcentaje'] : 0;
    $precio_final = $precio_base - ($precio_base * ($descuento / 100));

} catch (PDOException $e) {
    die("Error de lectura: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Check-Line — Confirmar Reserva</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<main class="container py-5" style="max-width: 800px;">
  
  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> shadow-sm">
      <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 text-center">
      <h1 class="h4 fw-bold" style="color: #0A2342;">Confirmación de Pasaje</h1>
      <p class="text-muted small">Verifique los detalles antes de emitir la reserva.</p>
    </div>
    <div class="card-body p-4">
      
      <div class="row mb-4">
        <div class="col-6">
          <p class="text-muted small text-uppercase mb-1">Origen</p>
          <h2 class="h5 fw-bold text-dark"><?= htmlspecialchars($vuelo['origen']) ?></h2>
          <p class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($vuelo['fecha_salida'])) ?> | <?= date('H:i', strtotime($vuelo['hora_salida'])) ?> hs</p>
        </div>
        <div class="col-6 text-end">
          <p class="text-muted small text-uppercase mb-1">Destino</p>
          <h2 class="h5 fw-bold text-dark"><?= htmlspecialchars($vuelo['destino']) ?></h2>
          <span class="badge bg-light text-dark border"><i class="bi bi-airplane-fill me-1"></i><?= htmlspecialchars($vuelo['aerolinea']) ?> (<?= htmlspecialchars($vuelo['codigo_vuelo']) ?>)</span>
        </div>
      </div>

      <div class="bg-light p-3 rounded-3 mb-4 text-center">
        <p class="text-muted mb-1">Inversión Final</p>
        <h3 class="display-5 fw-bold" style="color: #0A2342;">$<?= number_format($precio_final, 2, ',', '.') ?></h3>
        <?php if ($descuento > 0): ?>
          <span class="badge bg-success bg-opacity-10 text-success border border-success">Beneficio aplicado: -<?= $descuento ?>%</span>
        <?php endif; ?>
      </div>

      <form method="POST" action="reservar.php">
        <input type="hidden" name="id_vuelo" value="<?= $vuelo['id_vuelo'] ?>">
        <input type="hidden" name="precio_final" value="<?= $precio_final ?>">
        
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary fw-bold py-2" style="background-color: #0A2342; border-color: #0A2342;">
            Confirmar y Emitir Pasaje
          </button>
          <a href="vuelos.php" class="btn btn-outline-secondary fw-bold">Cancelar y Volver</a>
        </div>
      </form>

    </div>
  </div>
</main>
</body>
</html>