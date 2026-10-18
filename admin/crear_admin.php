<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

/*
 * Antes esta pagina era publica: cualquiera que abriera la URL podia
 * crearse un administrador y tomar el panel.
 *
 * Ahora solo se permite en dos casos:
 *   1. No existe ningun administrador todavia (arranque del sistema).
 *   2. Ya hay una sesion de administrador abierta.
 */
$hay_admins = (int) $conn->query("SELECT COUNT(*) AS n FROM admin")->fetch_assoc()['n'];
$primer_admin = ($hay_admins === 0);

if (!$primer_admin && !isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

$alerta = '';
$exito  = '';
$val    = ['usuario' => '', 'correo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $val['usuario'] = trim($_POST['usuario'] ?? '');
    $val['correo']  = trim($_POST['correo']  ?? '');
    $contrasena     = $_POST['contrasena']   ?? '';

    if ($val['usuario'] === '') {
        $alerta = '❌ El usuario es obligatorio.';
    } elseif (!filter_var($val['correo'], FILTER_VALIDATE_EMAIL)) {
        $alerta = '❌ El correo electrónico no es válido.';
    } elseif (mb_strlen($contrasena) < 8) {
        $alerta = '❌ La contraseña debe tener al menos 8 caracteres.';
    } else {
        $hash = hash_contrasena($contrasena);

        $stmt = $conn->prepare(
            "INSERT INTO admin (usuario, correo, contrasena) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $val['usuario'], $val['correo'], $hash);

        try {
            $stmt->execute();
            $exito = '✅ Administrador creado correctamente.';
            $val   = ['usuario' => '', 'correo' => ''];
            // Si era el primero, a partir de ahora la pagina exige sesion.
            $primer_admin = false;
        } catch (mysqli_sql_exception $e) {
            $alerta = ($e->getCode() === 1062)
                ? '❌ Ese nombre de usuario ya existe.'
                : '❌ No se pudo crear el administrador.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5 col-md-4">
        <div class="card shadow">
            <div class="card-body">
                <h3 class="text-center mb-4">Crear Administrador</h3>

                <?php if ($primer_admin): ?>
                    <div class="alert alert-info">
                        No hay administradores registrados. Esta es la creación
                        del primer administrador del sistema.
                    </div>
                <?php endif; ?>

                <?php if ($alerta !== ''): ?>
                    <div class="alert alert-danger"><?= esc($alerta) ?></div>
                <?php endif; ?>

                <?php if ($exito !== ''): ?>
                    <div class="alert alert-success"><?= esc($exito) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrf_input() ?>
                    <div class="mb-3">
                        <label>Usuario</label>
                        <input type="text" name="usuario" class="form-control"
                               value="<?= esc($val['usuario']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Correo</label>
                        <input type="email" name="correo" class="form-control"
                               value="<?= esc($val['correo']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="password" name="contrasena" class="form-control"
                               minlength="8" required>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Guardar Administrador</button>
                </form>

                <div class="text-center mt-3">
                    <a class="text-dark" href="<?= isset($_SESSION['admin_id']) ? 'index_admin.php' : 'login_admin.php' ?>">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
