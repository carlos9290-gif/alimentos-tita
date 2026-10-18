<?php
session_start();

// Vaciar las variables de sesión
$_SESSION = [];

// Borrar también la cookie de sesión: sin esto el identificador sigue
// viajando en el navegador y se puede reutilizar (session fixation).
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}

// Destruir la sesión en el servidor
session_destroy();

header("Location: index.php");
exit();
