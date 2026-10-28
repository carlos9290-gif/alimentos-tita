<?php
/**
 * Pantalla de pago del SIMULADOR.
 *
 * Imita una pasarela real (formulario de tarjeta, PSE, Nequi) pero no
 * mueve dinero. El aviso de simulación es permanente y deliberado: una
 * página que pide datos de tarjeta sin advertir que es falsa tiene
 * exactamente la forma de una página de phishing.
 *
 * Quien decide el resultado es pagos.php; quien lo registra es
 * procesar_pago.php. Aquí solo se pinta el formulario.
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/pagos.php';
include 'conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: inicio_sesion.php');
    exit();
}

if (!isset($_GET['id_pedido']) || !ctype_digit((string) $_GET['id_pedido'])) {
    header('Location: mis_pedidos.php');
    exit();
}

$id_pedido  = (int) $_GET['id_pedido'];
$id_cliente = (int) $_SESSION['cliente_id'];

// Misma defensa que detalle_pedido.php: el pedido tiene que ser suyo.
$stmt = $conn->prepare(
    "SELECT id_pedido, total, estado, direccion_envio, fecha_pedido
       FROM pedido
      WHERE id_pedido = ? AND id_cliente = ?"
);
$stmt->bind_param("ii", $id_pedido, $id_cliente);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    exit('❌ Pedido no encontrado.');
}

// Un pedido que ya no está Pendiente no se puede pagar aquí.
if ($pedido['estado'] !== 'Pendiente') {
    header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
    exit();
}

// Intentos previos: lo que hace de esto un historial y no un botón.
$stmtInt = $conn->prepare(
    "SELECT referencia, metodo, estado, mensaje, fecha_pago
       FROM pago
      WHERE id_pedido = ?
      ORDER BY id_pago DESC"
);
$stmtInt->bind_param("i", $id_pedido);
$stmtInt->execute();
$intentos = $stmtInt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtInt->close();

$error = $_SESSION['pago_error'] ?? '';
unset($_SESSION['pago_error']);

// Si vienen de un rechazo, se vuelve a abrir la pestaña que usaron.
$metodoActivo = $_SESSION['pago_metodo'] ?? 'Tarjeta';
unset($_SESSION['pago_metodo']);
if (!in_array($metodoActivo, PAGO_METODOS, true)) {
    $metodoActivo = 'Tarjeta';
}

$anioActual = (int) date('Y');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagar pedido #<?= (int) $id_pedido ?> - Alimentos Tita</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        body { background-color: #fafafa; }

        .cinta-simulacion {
            background: repeating-linear-gradient(
                45deg, #d32f2f, #d32f2f 18px, #b71c1c 18px, #b71c1c 36px);
            color: #fff;
            letter-spacing: .5px;
        }

        .caja-pago {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,.08);
        }

        .nav-pills .nav-link.active { background-color: #ffc107; color: #212529; }
        .nav-pills .nav-link        { color: #495057; }

        .credenciales code {
            font-size: .92rem;
            color: #0d6efd;
            background: #f1f3f5;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .credenciales td { padding: .3rem .5rem; vertical-align: middle; }
    </style>
</head>

<body>

<?php include 'Navbar.php'; ?>

<!-- Aviso permanente. No quitar: ver cabecera del archivo. -->
<div class="cinta-simulacion py-2 text-center fw-bold">
    MODO SIMULACIÓN &middot; NO SE COBRA DINERO REAL &middot; NO USES UNA TARJETA REAL
</div>

<div class="container py-4">

    <div class="row g-4">

        <!-- ================= FORMULARIOS ================= -->
        <div class="col-lg-7">
            <div class="caja-pago p-4">

                <h4 class="fw-bold mb-1">Pagar pedido #<?= (int) $id_pedido ?></h4>
                <p class="text-muted mb-4">Elige cómo quieres pagar.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= esc($error) ?></div>
                <?php endif; ?>

                <ul class="nav nav-pills mb-4 gap-2" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link <?= $metodoActivo === 'Tarjeta' ? 'active' : '' ?>"
                                data-bs-toggle="pill" data-bs-target="#tab-tarjeta" type="button">💳 Tarjeta</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $metodoActivo === 'PSE' ? 'active' : '' ?>"
                                data-bs-toggle="pill" data-bs-target="#tab-pse" type="button">🏦 PSE</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $metodoActivo === 'Nequi' ? 'active' : '' ?>"
                                data-bs-toggle="pill" data-bs-target="#tab-nequi" type="button">📱 Nequi</button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- ---------- TARJETA ---------- -->
                    <div class="tab-pane fade <?= $metodoActivo === 'Tarjeta' ? 'show active' : '' ?>" id="tab-tarjeta">
                        <form action="procesar_pago.php" method="POST" autocomplete="off">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id_pedido" value="<?= (int) $id_pedido ?>">
                            <input type="hidden" name="metodo" value="Tarjeta">

                            <div class="mb-3">
                                <label class="form-label">Nombre en la tarjeta</label>
                                <input type="text" name="nombre" class="form-control" placeholder="JUAN PEREZ" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Número de la tarjeta</label>
                                <input type="text" name="numero" class="form-control" inputmode="numeric"
                                       placeholder="4111 1111 1111 1111" maxlength="23" required>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-4">
                                    <label class="form-label">Mes</label>
                                    <select name="mes" class="form-select" required>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>"><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Año</label>
                                    <select name="anio" class="form-select" required>
                                        <?php for ($a = $anioActual; $a <= $anioActual + 10; $a++): ?>
                                            <option value="<?= $a ?>"><?= $a ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">CVV</label>
                                    <input type="text" name="cvv" class="form-control" inputmode="numeric"
                                           placeholder="123" maxlength="4" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Pagar $<?= number_format((float) $pedido['total'], 0, ',', '.') ?>
                            </button>
                        </form>
                    </div>

                    <!-- ---------- PSE ---------- -->
                    <div class="tab-pane fade <?= $metodoActivo === 'PSE' ? 'show active' : '' ?>" id="tab-pse">
                        <form action="procesar_pago.php" method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id_pedido" value="<?= (int) $id_pedido ?>">
                            <input type="hidden" name="metodo" value="PSE">

                            <div class="mb-3">
                                <label class="form-label">Banco</label>
                                <select name="banco" class="form-select" required>
                                    <option value="">Selecciona tu banco…</option>
                                    <?php foreach (pago_bancos_pse() as $slug => $datos): ?>
                                        <option value="<?= esc($slug) ?>"><?= esc($datos[0]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-4">
                                    <label class="form-label">Documento</label>
                                    <select name="tipo_documento" class="form-select" required>
                                        <option value="CC">C.C.</option>
                                        <option value="CE">C.E.</option>
                                        <option value="NIT">NIT</option>
                                        <option value="TI">T.I.</option>
                                    </select>
                                </div>
                                <div class="col-8">
                                    <label class="form-label">Número</label>
                                    <input type="text" name="documento" class="form-control" inputmode="numeric"
                                           placeholder="1020304050" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Pagar $<?= number_format((float) $pedido['total'], 0, ',', '.') ?>
                            </button>
                        </form>
                    </div>

                    <!-- ---------- NEQUI ---------- -->
                    <div class="tab-pane fade <?= $metodoActivo === 'Nequi' ? 'show active' : '' ?>" id="tab-nequi">
                        <form action="procesar_pago.php" method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id_pedido" value="<?= (int) $id_pedido ?>">
                            <input type="hidden" name="metodo" value="Nequi">

                            <div class="mb-3">
                                <label class="form-label">Celular Nequi</label>
                                <input type="text" name="celular" class="form-control" inputmode="numeric"
                                       placeholder="3001234567" maxlength="10" required>
                                <div class="form-text">Te llegaría una notificación para aprobar el pago.</div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100 mt-3">
                                Pagar $<?= number_format((float) $pedido['total'], 0, ',', '.') ?>
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Cancelar -->
            <form action="cancelar_pedido.php" method="POST" class="mt-3"
                  onsubmit="return confirm('¿Seguro que quieres cancelar este pedido? Los productos volverán al inventario.');">
                <?= csrf_input() ?>
                <input type="hidden" name="id_pedido" value="<?= (int) $id_pedido ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar este pedido</button>
                <span class="text-muted small ms-2">Los productos vuelven al inventario.</span>
            </form>
        </div>

        <!-- ================= RESUMEN Y CREDENCIALES ================= -->
        <div class="col-lg-5">

            <div class="caja-pago p-4 mb-4">
                <h6 class="text-uppercase text-muted small fw-bold mb-3">Resumen</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pedido</span><span class="fw-semibold">#<?= (int) $id_pedido ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Fecha</span><span><?= esc($pedido['fecha_pedido']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Entrega</span>
                    <span class="text-end small"><?= esc($pedido['direccion_envio']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fs-5 fw-bold">
                    <span>Total</span>
                    <span class="text-success">$<?= number_format((float) $pedido['total'], 0, ',', '.') ?></span>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    El importe lo toma el servidor del pedido, no de esta pantalla.
                </p>
            </div>

            <div class="caja-pago p-4 credenciales">
                <h6 class="text-uppercase text-muted small fw-bold mb-3">Datos de prueba</h6>

                <p class="small mb-2 fw-semibold">Tarjetas</p>
                <table class="table table-sm mb-3">
                    <?php foreach (pago_tarjetas_prueba() as $numero => $datos): ?>
                        <tr>
                            <td><code><?= esc(chunk_split($numero, 4, ' ')) ?></code></td>
                            <td class="small text-<?= $datos[0] === 'Aprobado' ? 'success' : 'danger' ?>">
                                <?= esc($datos[1]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p class="small text-muted">
                    Cualquier otra tarjeta se rechaza, aunque sea válida.
                    Usa un vencimiento futuro y un CVV de 3 dígitos.
                </p>

                <p class="small mb-2 fw-semibold">Nequi</p>
                <table class="table table-sm mb-3">
                    <?php foreach (pago_celulares_prueba() as $celular => $datos): ?>
                        <tr>
                            <td><code><?= esc($celular) ?></code></td>
                            <td class="small text-<?= $datos[0] === 'Aprobado' ? 'success' : 'danger' ?>">
                                <?= esc($datos[1]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <p class="small mb-0 text-muted">
                    En PSE el resultado lo decide el banco que elijas: el nombre lo dice.
                </p>
            </div>

            <?php if ($intentos): ?>
                <div class="caja-pago p-4 mt-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Intentos anteriores</h6>
                    <?php foreach ($intentos as $i): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <span class="badge bg-<?= $i['estado'] === 'Aprobado' ? 'success' : 'danger' ?>">
                                <?= esc($i['estado']) ?>
                            </span>
                            <span class="small ms-1"><?= esc($i['metodo']) ?></span>
                            <div class="small text-muted"><?= esc($i['mensaje']) ?></div>
                            <div class="small text-muted"><?= esc($i['referencia']) ?> &middot; <?= esc($i['fecha_pago']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'Footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
