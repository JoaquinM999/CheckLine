<?php
/**
 * CHECK-LINE — PROCESAMIENTO ABMC NOVEDADES (Admin)
 * Patrón Post-Redirect-Get.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: novedades.php');
    exit;
}

$pdo    = getConexion();
$accion = $_POST['accion'] ?? '';

function validarDatosNovedad(array $datos): array
{
    $errores     = [];
    $titulo      = trim($datos['titulo']       ?? '');
    $contenido   = trim($datos['contenido']    ?? '');
    $fechaInicio = trim($datos['fecha_inicio'] ?? '');
    $fechaFin    = trim($datos['fecha_fin']    ?? '');

    if ($titulo === '' || mb_strlen($titulo) > 150) {
        $errores[] = 'El título es obligatorio y debe tener hasta 150 caracteres.';
    }
    if ($contenido === '') {
        $errores[] = 'El contenido es obligatorio.';
    }
    if ($fechaInicio === '' || !strtotime($fechaInicio)) {
        $errores[] = 'La fecha de inicio es inválida.';
    }
    if ($fechaFin === '' || !strtotime($fechaFin)) {
        $errores[] = 'La fecha de vencimiento es inválida.';
    }
    if (empty($errores) && $fechaInicio > $fechaFin) {
        $errores[] = 'La fecha de inicio no puede ser posterior a la de vencimiento.';
    }

    return [
        'errores'      => $errores,
        'titulo'       => $titulo,
        'contenido'    => $contenido,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
    ];
}

if ($accion === 'crear') {
    $v = validarDatosNovedad($_POST);
    if (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO novedades (titulo, contenido, fecha_inicio, fecha_fin, id_admin)
            VALUES (:titulo, :contenido, :inicio, :fin, :admin)
        ");
        $stmt->execute([
            'titulo'    => $v['titulo'],
            'contenido' => $v['contenido'],
            'inicio'    => $v['fecha_inicio'],
            'fin'       => $v['fecha_fin'],
            'admin'     => $_SESSION['id_usuario'],
        ]);
        setMensaje('success', "Novedad \"{$v['titulo']}\" publicada correctamente.");
    }

} elseif ($accion === 'editar') {
    $idNovedad = (int) ($_POST['id_novedad'] ?? 0);
    $v = validarDatosNovedad($_POST);

    if ($idNovedad <= 0) {
        setMensaje('danger', 'Identificador de novedad inválido.');
    } elseif (!empty($v['errores'])) {
        setMensaje('danger', implode(' ', $v['errores']));
    } else {
        $stmt = $pdo->prepare("
            UPDATE novedades
            SET titulo = :titulo, contenido = :contenido,
                fecha_inicio = :inicio, fecha_fin = :fin
            WHERE id_novedad = :id
        ");
        $stmt->execute([
            'titulo'    => $v['titulo'],
            'contenido' => $v['contenido'],
            'inicio'    => $v['fecha_inicio'],
            'fin'       => $v['fecha_fin'],
            'id'        => $idNovedad,
        ]);
        setMensaje('success', "Novedad \"{$v['titulo']}\" actualizada correctamente.");
    }

} elseif ($accion === 'eliminar') {
    $idNovedad = (int) ($_POST['id_novedad'] ?? 0);
    if ($idNovedad <= 0) {
        setMensaje('danger', 'Identificador de novedad inválido.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM novedades WHERE id_novedad = :id");
        $stmt->execute(['id' => $idNovedad]);
        setMensaje($stmt->rowCount() > 0 ? 'success' : 'danger',
                   $stmt->rowCount() > 0 ? 'Novedad eliminada correctamente.' : 'La novedad ya no existe.');
    }

} else {
    setMensaje('danger', 'Acción no reconocida.');
}

header('Location: novedades.php');
exit;
