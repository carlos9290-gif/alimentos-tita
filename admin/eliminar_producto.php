<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

// Solo el admin autenticado puede borrar
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

// Borrar es destructivo: exige POST con token valido.
csrf_validar();

/** Vuelve al listado con un mensaje. */
function volver(string $clave, string $texto): void
{
    $_SESSION[$clave] = $texto;
    header('Location: gestion_producto.php');
    exit();
}

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    volver('producto_error', '❌ No se especificó el producto a eliminar.');
}
$id = (int) $_POST['id'];

/* 1) Confirmar existencia del producto */
$stmt = $conn->prepare("SELECT nombre FROM producto WHERE id_producto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    volver('producto_error', '❌ Producto no encontrado.');
}

/* 2) Eliminar producto */
$del = $conn->prepare("DELETE FROM producto WHERE id_producto = ?");
$del->bind_param("i", $id);

try {
    $del->execute();
} catch (mysqli_sql_exception $e) {
    $del->close();
    // 1451 = el producto aparece en pedidos ya realizados (FK RESTRICT en
    // detalle_pedido.id_producto). Para retirarlo del catalogo sin perder
    // el historico se usa el estado 'inactivo'.
    if ($e->getCode() === 1451) {
        volver(
            'producto_error',
            "❌ No se puede eliminar \"{$producto['nombre']}\": ya aparece en pedidos. " .
            "Edítalo y ponlo como «inactivo» para retirarlo del catálogo."
        );
    }
    volver('producto_error', '❌ Error al eliminar el producto.');
}

$del->close();
volver('producto_ok', "🗑️ Producto \"{$producto['nombre']}\" eliminado correctamente.");
