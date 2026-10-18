<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

csrf_validar();

/** Vuelve al login del panel con el motivo del fallo. */
function admin_login_fallido(string $motivo): void
{
    $_SESSION['admin_login_error'] = $motivo;
    header('Location: login_admin.php');
    exit();
}

$usuario    = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena']   ?? '';

if ($usuario === '' || $contrasena === '') {
    admin_login_fallido('Debes ingresar usuario y contraseña.');
}

// Se busca solo por usuario; la contrasena se compara en PHP.
$stmt = $conn->prepare(
    "SELECT id_admin, usuario, contrasena FROM admin WHERE usuario = ? LIMIT 1"
);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$generico = 'Usuario o contraseña incorrectos.';

if (!$admin) {
    admin_login_fallido($generico);
}

$rehash = null;
if (!verificar_contrasena($contrasena, $admin['contrasena'], $rehash)) {
    admin_login_fallido($generico);
}

// Migracion transparente de cuentas guardadas en texto plano.
if ($rehash !== null) {
    $upd = $conn->prepare("UPDATE admin SET contrasena = ? WHERE id_admin = ?");
    $upd->bind_param("si", $rehash, $admin['id_admin']);
    $upd->execute();
    $upd->close();
}

sesion_regenerar();

$_SESSION['admin_id'] = (int) $admin['id_admin'];
$_SESSION['usuario']  = $admin['usuario'];

$conn->close();

header('Location: index_admin.php');
exit();
