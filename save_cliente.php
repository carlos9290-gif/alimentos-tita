<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// Solo se registra por POST y con token valido.
csrf_validar();

/** Vuelve al formulario conservando lo escrito y mostrando el motivo. */
function volver_con_error(string $motivo, array $datos): void
{
    $_SESSION['registro_error'] = $motivo;
    $_SESSION['registro_datos'] = $datos;
    header('Location: crear_cuenta.php');
    exit();
}

$nombre     = trim($_POST['nombre']     ?? '');
$apellido   = trim($_POST['apellido']   ?? '');
$correo     = trim($_POST['correo']     ?? '');
$direccion  = trim($_POST['direccion']  ?? '');
$telefono   = trim($_POST['telefono']   ?? '');
$contrasena = $_POST['contrasena']      ?? '';

// Lo que se devuelve al formulario si algo falla (nunca la contrasena).
$previos = compact('nombre', 'apellido', 'correo', 'direccion', 'telefono');

// ---------------------------------------------------------------------
// Validaciones
// ---------------------------------------------------------------------
if ($nombre === '' || $apellido === '') {
    volver_con_error('El nombre y el apellido son obligatorios.', $previos);
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    volver_con_error('El correo electrónico no es válido.', $previos);
}

if (mb_strlen($contrasena) < 8) {
    volver_con_error('La contraseña debe tener al menos 8 caracteres.', $previos);
}

if ($telefono !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $telefono)) {
    volver_con_error('El teléfono solo puede contener números.', $previos);
}

// ---------------------------------------------------------------------
// Insercion
// ---------------------------------------------------------------------
$hash = hash_contrasena($contrasena);

$stmt = $conn->prepare(
    "INSERT INTO cliente (nombre, apellido, correo, direccion, telefono, contrasena)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssssss", $nombre, $apellido, $correo, $direccion, $telefono, $hash);

// Desde PHP 8.1 mysqli lanza excepciones en vez de devolver false.
try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    $stmt->close();
    // 1062 = clave duplicada -> el correo ya existe (uq_cliente_correo).
    if ($e->getCode() === 1062) {
        volver_con_error('Ya existe una cuenta registrada con ese correo.', $previos);
    }
    volver_con_error('No se pudo crear la cuenta. Inténtalo de nuevo.', $previos);
}

$stmt->close();
$conn->close();
$_SESSION['registro_ok'] = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
header('Location: inicio_sesion.php');
exit();
