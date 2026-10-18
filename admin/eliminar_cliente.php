<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

// Solo administradores autenticados.
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

// Borrar es destructivo: exige POST con token valido (antes bastaba con
// abrir una URL, asi que cualquier enlace o imagen externa podia dispararlo).
csrf_validar();

/** Vuelve al listado con un mensaje. */
function volver(string $clave, string $texto): void
{
    $_SESSION[$clave] = $texto;
    header('Location: gestion_cliente.php');
    exit();
}

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    volver('cliente_error', '❌ No se especificó el cliente a eliminar.');
}
$id = (int) $_POST['id'];

// Comprobar existencia.
$stmt = $conn->prepare("SELECT nombre, apellido FROM cliente WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    volver('cliente_error', '❌ Cliente no encontrado.');
}

$nombre = $cliente['nombre'] . ' ' . $cliente['apellido'];

$del = $conn->prepare("DELETE FROM cliente WHERE id_cliente = ?");
$del->bind_param("i", $id);

try {
    $del->execute();
} catch (mysqli_sql_exception $e) {
    $del->close();
    // 1451 = la fila esta referenciada por otra tabla. El historial de
    // pedidos se protege a proposito (FK RESTRICT en pedido.id_cliente).
    if ($e->getCode() === 1451) {
        volver(
            'cliente_error',
            "❌ No se puede eliminar a \"$nombre\": tiene pedidos registrados. " .
            "El historial de ventas no se borra."
        );
    }
    volver('cliente_error', '❌ No se pudo eliminar el cliente.');
}

$del->close();
volver('cliente_ok', "🗑️ Cliente \"$nombre\" eliminado correctamente.");
