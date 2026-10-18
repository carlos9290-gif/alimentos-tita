<?php
require_once __DIR__ . '/../seguridad.php';
include '../conexion.php';

// Solo admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Validar ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("❌ Error: No se especificó el producto a editar.");
}
$id = (int) $_GET['id'];

/* 1) Traer el producto */
$stmt = $conn->prepare(
    "SELECT id_producto, nombre, descripcion, precio, stock, estado_producto, imagen_empanada
     FROM producto WHERE id_producto = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { die("❌ Producto no encontrado."); }
$producto = $res->fetch_assoc();
$stmt->close();

/* 1b) Canales de venta disponibles y los que ya tiene asignados */
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY id_categoria")
                  ->fetch_all(MYSQLI_ASSOC);

$q = $conn->prepare("SELECT id_categoria FROM producto_categoria WHERE id_producto = ?");
$q->bind_param("i", $id);
$q->execute();
$seleccionadas = array_map('intval', array_column($q->get_result()->fetch_all(MYSQLI_ASSOC), 'id_categoria'));
$q->close();

/* 2) Actualizar en POST */
$alerta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = $_POST['precio'] ?? '';
    $stock       = $_POST['stock'] ?? '';
    $estado      = $_POST['estado_producto'] ?? '';
    $rutaImg     = trim($_POST['imagen_empanada'] ?? '');

    // Solo se aceptan ids de categoria que existan de verdad.
    $idsValidos    = array_column($categorias, 'id_categoria');
    $seleccionadas = array_values(array_intersect(
        array_map('intval', (array) ($_POST['categorias'] ?? [])),
        $idsValidos
    ));

    // Validaciones básicas
    if ($nombre === '' || !in_array($estado, ['activo','inactivo'], true)) {
        $alerta = "❌ Nombre y estado son obligatorios.";
    } elseif (!is_numeric($precio) || (int)$precio < 0) {
        $alerta = "❌ El precio debe ser un número válido (≥ 0).";
    } elseif (!ctype_digit((string)$stock)) {
        $alerta = "❌ El stock debe ser un entero (≥ 0).";
    } elseif (!$seleccionadas) {
        $alerta = "❌ Marca al menos un canal de venta: si no, el producto no aparece en ninguna página.";
    } else {
        // Normaliza posibles dobles espacios y barras
        $rutaImg = preg_replace('#[\\\\]+#', '/', $rutaImg);
        $rutaImg = preg_replace('/\s+/', ' ', $rutaImg);

        // (Opcional) restringe prefijos permitidos para seguridad
        // if ($rutaImg !== '' && !str_starts_with($rutaImg, '../uploads/')) {
        //     $alerta = "❌ La ruta de imagen debe comenzar con ../uploads/";
        // }

        if ($alerta === '') {
            $precioInt = (int)$precio;
            $stockInt  = (int)$stock;

            $upd = $conn->prepare(
                "UPDATE producto
                 SET nombre = ?, descripcion = ?, precio = ?, stock = ?, estado_producto = ?, imagen_empanada = ?
                 WHERE id_producto = ?"
            );
            $upd->bind_param(
                "ssisssi",
                $nombre,
                $descripcion,
                $precioInt,
                $stockInt,
                $estado,
                $rutaImg,   // ← solo texto con la ruta
                $id
            );
            // Datos y clasificacion se actualizan juntos.
            try {
                $conn->begin_transaction();
                $upd->execute();
                $upd->close();

                $del = $conn->prepare("DELETE FROM producto_categoria WHERE id_producto = ?");
                $del->bind_param("i", $id);
                $del->execute();
                $del->close();

                $ins = $conn->prepare(
                    "INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)"
                );
                foreach ($seleccionadas as $idCat) {
                    $ins->bind_param("ii", $id, $idCat);
                    $ins->execute();
                }
                $ins->close();

                $conn->commit();
                $_SESSION['producto_ok'] = '✅ Producto actualizado correctamente.';
                header('Location: gestion_producto.php');
                exit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                error_log('Error al actualizar producto: ' . $e->getMessage());
                $alerta = "❌ No se pudo actualizar el producto.";
            }
        }
    }

    // persistir en el form si hubo error
    $producto['nombre']          = $nombre;
    $producto['descripcion']     = $descripcion;
    $producto['precio']          = $precio;
    $producto['stock']           = $stock;
    $producto['estado_producto'] = $estado;
    $producto['imagen_empanada'] = $rutaImg;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4 text-center">✏️ Editar Producto</h2>

    <?php if ($alerta): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($alerta, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <?= csrf_input() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                       value="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado_producto" class="form-select" required>
                    <option value="activo"   <?= ($producto['estado_producto']==='activo')?'selected':''; ?>>Activo</option>
                    <option value="inactivo" <?= ($producto['estado_producto']==='inactivo')?'selected':''; ?>>Inactivo</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Precio (COP)</label>
                <input type="number" name="precio" class="form-control" min="0" step="1"
                       value="<?= htmlspecialchars((string)$producto['precio'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" class="form-control" min="0" step="1"
                       value="<?= htmlspecialchars((string)$producto['stock'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Ruta de imagen (texto)</label>
                <input type="text" name="imagen_empanada" class="form-control"
                       placeholder="../uploads/empanadas/mi-imagen.jpg"
                       value="<?= htmlspecialchars($producto['imagen_empanada'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <small class="text-muted">Guarda solo la ruta accesible por el servidor (relativa o absoluta).</small>
            </div>

            <div class="col-12">
                <label class="form-label">Canales de venta *</label>
                <div class="d-flex gap-4 flex-wrap">
                    <?php foreach ($categorias as $cat): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="categorias[]" value="<?= (int) $cat['id_categoria'] ?>"
                                   id="cat<?= (int) $cat['id_categoria'] ?>"
                                   <?= in_array((int) $cat['id_categoria'], $seleccionadas, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat<?= (int) $cat['id_categoria'] ?>">
                                <?= esc($cat['nombre']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted">Define en qué páginas aparece el producto.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Previsualización</label>
                <?php if (!empty($producto['imagen_empanada'])): ?>
                    <img src="<?= htmlspecialchars($producto['imagen_empanada'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="Imagen" style="width:140px;height:140px;object-fit:cover;border-radius:8px;">
                <?php else: ?>
                    <span class="text-muted">Sin imagen</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-warning">💾 Guardar Cambios</button>
            <a href="gestion_producto.php" class="btn btn-secondary">⬅ Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>