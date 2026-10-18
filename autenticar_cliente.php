<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// Unico punto de autenticacion de clientes del sitio.
csrf_validar();

/** Vuelve al formulario con el motivo del fallo. */
function login_fallido(string $motivo): void
{
    $_SESSION['login_error'] = $motivo;
    header('Location: inicio_sesion.php');
    exit();
}

$correo     = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena']  ?? '';

if ($correo === '' || $contrasena === '') {
    login_fallido('Debes ingresar tu correo y tu contraseña.');
}

// Se busca solo por correo: la contrasena se compara en PHP, nunca en SQL.
$stmt = $conn->prepare(
    "SELECT id_cliente, nombre, correo, contrasena FROM cliente WHERE correo = ? LIMIT 1"
);
$stmt->bind_param("s", $correo);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mensaje identico si no existe el correo o si la contrasena no coincide:
// asi no se revela que correos estan registrados.
$generico = 'Correo o contraseña incorrectos.';

if (!$cliente) {
    login_fallido($generico);
}

$rehash = null;
if (!verificar_contrasena($contrasena, $cliente['contrasena'], $rehash)) {
    login_fallido($generico);
}

// La cuenta venia en texto plano (o con un hash obsoleto): se actualiza
// de forma transparente ahora que sabemos que la contrasena es correcta.
if ($rehash !== null) {
    $upd = $conn->prepare("UPDATE cliente SET contrasena = ? WHERE id_cliente = ?");
    $upd->bind_param("si", $rehash, $cliente['id_cliente']);
    $upd->execute();
    $upd->close();
}

// Sesion nueva tras autenticar: evita fijacion de sesion.
sesion_regenerar();

$_SESSION['cliente_id'] = (int) $cliente['id_cliente'];
$_SESSION['nombre']     = $cliente['nombre'];
$_SESSION['correo']     = $cliente['correo'];

$conn->close();

header('Location: index.php');
exit();
