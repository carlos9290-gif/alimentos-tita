<?php
/**
 * Utilidades de seguridad compartidas - Alimentos Tita
 *
 * Incluir con:  require_once __DIR__ . '/seguridad.php';
 * (desde /admin:  require_once __DIR__ . '/../seguridad.php';)
 *
 * Cubre tres cosas que antes estaban repetidas o ausentes:
 *   - token CSRF para las acciones que modifican o borran datos
 *   - escape de HTML en las salidas
 *   - verificacion de contrasenas con password_hash / password_verify
 */

// Arranque de sesion tolerante: no falla si la pagina ya la inicio.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================================
 *  CSRF
 * ===================================================================== */

/** Token CSRF de la sesion actual; lo genera la primera vez. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto listo para pegar dentro de un <form>. */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

/**
 * Corta la ejecucion si la peticion no es POST o el token no coincide.
 * Llamar al principio de todo script que borre o modifique datos.
 */
function csrf_validar(): void
{
    $ok = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && !empty($_POST['csrf_token'])
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);

    if (!$ok) {
        http_response_code(403);
        exit('❌ Solicitud no válida o expirada. Vuelve atrás y reinténtalo.');
    }
}


/* =====================================================================
 *  Salida
 * ===================================================================== */

/** Escapa un valor para imprimirlo dentro de HTML. */
function esc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}


/* =====================================================================
 *  Contrasenas
 * ===================================================================== */

/**
 * Compara la contrasena escrita con la guardada en la base de datos.
 *
 * Acepta dos formatos:
 *   - hash de password_hash()  -> caso normal
 *   - texto plano              -> cuentas creadas antes de la Fase 2
 *
 * Si la cuenta venia en texto plano (o el hash quedo obsoleto) y la
 * contrasena es correcta, deja en $rehash el hash nuevo para que quien
 * llama lo guarde. Asi las cuentas viejas migran solas al iniciar sesion
 * y nadie se queda por fuera.
 */
function verificar_contrasena(string $entrada, string $guardada, ?string &$rehash = null): bool
{
    $rehash = null;

    // ¿Lo guardado es un hash reconocible?
    if (password_get_info($guardada)['algo']) {
        if (!password_verify($entrada, $guardada)) {
            return false;
        }
        if (password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
            $rehash = password_hash($entrada, PASSWORD_DEFAULT);
        }
        return true;
    }

    // Valor heredado en texto plano.
    if (hash_equals($guardada, $entrada)) {
        $rehash = password_hash($entrada, PASSWORD_DEFAULT);
        return true;
    }

    return false;
}

/** Genera el hash con el que se deben guardar las contrasenas nuevas. */
function hash_contrasena(string $contrasena): string
{
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

/**
 * Renueva el identificador de sesion tras un inicio de sesion correcto.
 * Evita la fijacion de sesion (session fixation).
 */
function sesion_regenerar(): void
{
    session_regenerate_id(true);
}
