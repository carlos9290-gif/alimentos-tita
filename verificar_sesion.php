<?php
/**
 * Guardia de sesión para páginas de cliente.
 *
 * Incluir al principio de cualquier página que solo deban ver clientes
 * autenticados:   require_once __DIR__ . '/verificar_sesion.php';
 *
 * Antes comprobaba $_SESSION['contrasena'] === true, una variable que no
 * se define en ninguna parte del proyecto: la comprobación siempre
 * fallaba y ninguna página llegó a usar este archivo.
 */

require_once __DIR__ . '/seguridad.php';

if (empty($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}
