<?php
require_once __DIR__ . '/../seguridad.php';
include '../conexion.php';

// Solo admin autenticado
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

$alerta = '';

// Canales de venta disponibles (tabla categoria).
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY id_categoria")
                  ->fetch_all(MYSQLI_ASSOC);
$seleccionadas = [];

$val = [
    'nombre'          => '',
    'descripcion'     => '',
    'precio'          => '',
    'stock'           => '',
    'estado_producto' => 'activo',
    'imagen_empanada' => 'imagenes/'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    // Capturar datos
    $val['nombre']          = trim($_POST['nombre'] ?? '');
    $val['descripcion']     = trim($_POST['descripcion'] ?? '');
    $val['precio']          = $_POST['precio'] ?? '';
    $val['stock']           = $_POST['stock'] ?? '';
    $val['estado_producto'] = $_POST['estado_producto'] ?? 'activo';
    $val['imagen_empanada'] = trim($_POST['imagen_empanada'] ?? '');

    // Canales marcados; solo se aceptan ids que existan en la tabla.
    $idsValidos    = array_column($categorias, 'id_categoria');
    $seleccionadas = array_values(array_intersect(
        array_map('intval', (array) ($_POST['categorias'] ?? [])),
        $idsValidos
    ));

    // Validar datos
    if ($val['nombre'] === '') {
        $alerta = "❌ El nombre es obligatorio.";
    } elseif (!in_array($val['estado_producto'], ['activo','inactivo'], true)) {
        $alerta = "❌ Estado inválido.";
    } elseif (!is_numeric($val['precio']) || (int)$val['precio'] < 0) {
        $alerta = "❌ El precio debe ser un número válido.";
    } elseif (!ctype_digit((string)$val['stock'])) {
        $alerta = "❌ El stock debe ser un número entero.";
    } elseif ($val['imagen_empanada'] === '' || !str_starts_with($val['imagen_empanada'], 'imagenes/')) {
        $alerta = "❌ La ruta de imagen debe comenzar con 'imagenes/'.";
    } elseif (!$seleccionadas) {
        $alerta = "❌ Marca al menos un canal de venta: si no, el producto no aparece en ninguna página.";
    } else {
        $precioInt = (int) $val['precio'];
        $stockInt  = (int) $val['stock'];

        // Insertar producto
        $stmt = $conn->prepare(
            "INSERT INTO producto (nombre, descripcion, precio, stock, estado_producto, imagen_empanada)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssiiss",
            $val['nombre'],
            $val['descripcion'],
            $precioInt,
            $stockInt,
            $val['estado_producto'],
            $val['imagen_empanada']
        );

        // El producto y su clasificacion se guardan juntos o no se
        // guarda nada: un producto sin canal no se veria en ningun lado.
        try {
            $conn->begin_transaction();
            $stmt->execute();
            $idNuevo = $conn->insert_id;
            $stmt->close();

            $pc = $conn->prepare(
                "INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)"
            );
            foreach ($seleccionadas as $idCat) {
                $pc->bind_param("ii", $idNuevo, $idCat);
                $pc->execute();
            }
            $pc->close();

            $conn->commit();
            $_SESSION['producto_ok'] = '✅ Producto creado correctamente.';
            header('Location: gestion_producto.php');
            exit();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('Error al crear producto: ' . $e->getMessage());
            $alerta = "❌ No se pudo crear el producto.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="text-center mb-4">➕ Crear Producto</h2>

    <?php if ($alerta): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($alerta, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <?= csrf_input() ?>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nombre del producto *</label>
                <input type="text" name="nombre" class="form-control" required
                       value="<?= htmlspecialchars($val['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Estado *</label>
                <select name="estado_producto" class="form-select" required>
                    <option value="activo" <?= $val['estado_producto']==='activo'?'selected':''; ?>>Activo</option>
                    <option value="inactivo" <?= $val['estado_producto']==='inactivo'?'selected':''; ?>>Inactivo</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($val['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Precio (COP) *</label>
                <input type="number" name="precio" class="form-control" min="0" step="1" required
                       value="<?= htmlspecialchars((string)$val['precio'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Stock *</label>
                <input type="number" name="stock" class="form-control" min="0" step="1" required
                       value="<?= htmlspecialchars((string)$val['stock'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ruta de imagen *</label>
                <input type="text" name="imagen_empanada" class="form-control"
                       placeholder="imagenes/Carne.jpeg"
                       value="<?= htmlspecialchars($val['imagen_empanada'], ENT_QUOTES, 'UTF-8') ?>" required>
                <small class="text-muted">Ejemplo: imagenes/Carne.jpeg</small>
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
                <img id="preview" src="<?= $val['imagen_empanada'] ? htmlspecialchars($val['imagen_empanada'], ENT_QUOTES, 'UTF-8') : '' ?>"
                     alt="Vista previa"
                     style="width:140px;height:140px;object-fit:cover;border-radius:8px;<?= $val['imagen_empanada']?'':'display:none;' ?>">
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary">💾 Guardar Producto</button>
            <a href="gestion_producto.php" class="btn btn-secondary">⬅ Cancelar</a>
        </div>
    </form>
</div>


</body>
</html>