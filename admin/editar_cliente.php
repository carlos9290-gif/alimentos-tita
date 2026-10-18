<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

// Solo administradores autenticados.
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

if (!isset($_GET['id']) || !ctype_digit((string) $_GET['id'])) {
    exit('❌ Error: No se especificó el cliente a editar.');
}
$id = (int) $_GET['id'];

$alerta = '';
$exito  = '';

// ---------------------------------------------------------------------
// Guardar cambios
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nombre     = trim($_POST['nombre']    ?? '');
    $apellido   = trim($_POST['apellido']  ?? '');
    $correo     = trim($_POST['correo']    ?? '');
    $direccion  = trim($_POST['direccion'] ?? '');
    $telefono   = trim($_POST['telefono']  ?? '');
    $contrasena = $_POST['contrasena']     ?? '';   // vacia = no cambiar

    if ($nombre === '' || $apellido === '') {
        $alerta = '❌ El nombre y el apellido son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $alerta = '❌ El correo electrónico no es válido.';
    } elseif ($contrasena !== '' && mb_strlen($contrasena) < 8) {
        $alerta = '❌ La nueva contraseña debe tener al menos 8 caracteres.';
    } else {
        if ($contrasena !== '') {
            // Se guarda el hash, nunca el texto escrito.
            $hash = hash_contrasena($contrasena);
            $upd  = $conn->prepare(
                "UPDATE cliente
                    SET nombre = ?, apellido = ?, correo = ?, direccion = ?,
                        telefono = ?, contrasena = ?
                  WHERE id_cliente = ?"
            );
            $upd->bind_param("ssssssi", $nombre, $apellido, $correo, $direccion, $telefono, $hash, $id);
        } else {
            // Sin tocar la contrasena actual.
            $upd = $conn->prepare(
                "UPDATE cliente
                    SET nombre = ?, apellido = ?, correo = ?, direccion = ?, telefono = ?
                  WHERE id_cliente = ?"
            );
            $upd->bind_param("sssssi", $nombre, $apellido, $correo, $direccion, $telefono, $id);
        }

        try {
            $upd->execute();
            $exito = '✅ Cliente actualizado correctamente.';
        } catch (mysqli_sql_exception $e) {
            $alerta = ($e->getCode() === 1062)
                ? '❌ Ya existe otro cliente con ese correo.'
                : '❌ No se pudo actualizar el cliente.';
        }
        $upd->close();
    }
}

// ---------------------------------------------------------------------
// Cargar datos actuales (despues de guardar, para reflejar los cambios)
// ---------------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT id_cliente, nombre, apellido, correo, direccion, telefono
       FROM cliente WHERE id_cliente = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    exit('❌ Cliente no encontrado.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4 text-center">✏️ Editar Cliente</h2>

    <?php if ($alerta !== ''): ?>
        <div class="alert alert-danger"><?= esc($alerta) ?></div>
    <?php endif; ?>

    <?php if ($exito !== ''): ?>
        <div class="alert alert-success"><?= esc($exito) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <?= csrf_input() ?>

        <div class="mb-3">
            <label class="form-label">Nombre:</label>
            <input type="text" name="nombre" value="<?= esc($cliente['nombre']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Apellido:</label>
            <input type="text" name="apellido" value="<?= esc($cliente['apellido']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo:</label>
            <input type="email" name="correo" value="<?= esc($cliente['correo']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Dirección:</label>
            <input type="text" name="direccion" value="<?= esc($cliente['direccion']) ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Teléfono:</label>
            <input type="text" name="telefono" value="<?= esc($cliente['telefono']) ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña:</label>
            <input type="password" name="contrasena" class="form-control" minlength="8"
                   placeholder="Dejar vacío para no cambiarla">
            <div class="form-text">
                Las contraseñas se guardan cifradas: no se pueden consultar,
                solo reemplazar. Déjalo vacío si no quieres cambiarla.
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-warning">💾 Guardar Cambios</button>
            <a href="gestion_cliente.php" class="btn btn-secondary">⬅ Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
