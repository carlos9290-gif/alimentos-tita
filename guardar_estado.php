<?php
/*
 * ATENCIÓN - CÓDIGO SIN USO
 *
 * Este archivo actualiza la tabla `confirmacion` y redirige a
 * `estado_pedido_admin.php`. Ninguna de las dos cosas existe en el
 * proyecto, y ninguna página enlaza aquí: nunca llega a funcionar.
 *
 * Se mantiene, pero cerrado, porque tal como estaba era un endpoint
 * público con inyección SQL directa ($id y $estado se concatenaban en
 * la consulta y cualquiera podía invocarlo sin autenticarse).
 *
 * El cambio de estado de un pedido que SÍ se usa está en
 * admin/save_pedido_admin.php. Lo más probable es que este archivo deba
 * borrarse; se deja la decisión al equipo.
 */

require_once __DIR__ . '/seguridad.php';

// Solo administradores autenticados.
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin/login_admin.php');
    exit();
}

csrf_validar();

include 'conexion.php';

$id     = $_POST['id_confirmacion']     ?? '';
$estado = $_POST['estado_confirmacion'] ?? '';

if (!ctype_digit((string) $id) || $estado === '') {
    exit('Datos incompletos.');
}

$id = (int) $id;

$stmt = $conn->prepare(
    "UPDATE confirmacion SET estado_confirmacion = ? WHERE id_confirmacion = ?"
);

if (!$stmt) {
    exit('❌ Este módulo no está disponible: la tabla `confirmacion` no existe.');
}

$stmt->bind_param("si", $estado, $id);
$stmt->execute();
$stmt->close();

header('Location: admin/pedido_admin.php');
exit();
