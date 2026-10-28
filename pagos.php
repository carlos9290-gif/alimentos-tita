<?php
/**
 * Motor del SIMULADOR de pasarela de pagos.
 *
 * No hay ninguna conexión con un banco real: este archivo decide si un
 * pago se aprueba o se rechaza a partir de unas credenciales de prueba
 * fijas, igual que hacen los entornos sandbox de Stripe, Wompi o
 * Mercado Pago.
 *
 * Aquí no se toca la base de datos ni la sesión a propósito: son
 * funciones puras, para poder razonar sobre ellas (y probarlas) sin
 * montar un pedido. Quien guarda el resultado es procesar_pago.php.
 *
 * REGLA GENERAL: solo las credenciales documentadas abajo se aprueban.
 * Cualquier otra tarjeta o celular -- incluido uno real -- se rechaza.
 * Es deliberado: así nadie se lleva la impresión de haber pagado de
 * verdad, y teclear una tarjeta real nunca tiene recompensa.
 *
 * NUNCA se devuelve el número completo ni el CVV. Solo marca y
 * últimos 4 dígitos, que es lo único que se guarda.
 */

/** Métodos que el simulador acepta. Deben coincidir con el ENUM de `pago`. */
const PAGO_METODOS = ['Tarjeta', 'PSE', 'Nequi'];


// =====================================================================
//  Credenciales de prueba
// =====================================================================

/**
 * Tarjetas que fuerzan cada desenlace.
 *
 * Son los números de prueba estándar de la industria (los mismos que
 * publica Stripe), así que pasan la validación de Luhn: el rechazo
 * viene de la decisión del "banco", no de un número mal formado.
 */
function pago_tarjetas_prueba(): array
{
    return [
        '4111111111111111' => ['Aprobado',  'Pago aprobado.'],
        '5555555555554444' => ['Aprobado',  'Pago aprobado.'],
        '4000000000000002' => ['Rechazado', 'Fondos insuficientes.'],
        '4000000000000069' => ['Rechazado', 'Tarjeta vencida.'],
        '4000000000000127' => ['Rechazado', 'Código de seguridad incorrecto.'],
        '4000000000000119' => ['Rechazado', 'Error de comunicación con el banco. Intenta de nuevo.'],
    ];
}

/**
 * Bancos del simulador de PSE.
 *
 * En PSE la autorización ocurre dentro del banco, no en el comercio, y
 * no hay forma de simular banco por banco. Por eso la lista son bancos
 * de prueba con el desenlace escrito en el nombre, tal como funciona el
 * ambiente de pruebas de PSE real.
 */
function pago_bancos_pse(): array
{
    return [
        'pse_ok'     => ['Banco de Pruebas - autoriza el débito',         'Aprobado',  'Débito autorizado por el banco.'],
        'pse_fondos' => ['Banco de Pruebas - rechaza por fondos',         'Rechazado', 'El banco rechazó el débito: fondos insuficientes.'],
        'pse_clave'  => ['Banco de Pruebas - rechaza por clave inválida', 'Rechazado', 'El banco rechazó el débito: clave inválida.'],
        'pse_caido'  => ['Banco de Pruebas - fuera de servicio',          'Rechazado', 'El banco no responde en este momento.'],
    ];
}

/** Celulares Nequi que fuerzan cada desenlace. */
function pago_celulares_prueba(): array
{
    return [
        '3001234567' => ['Aprobado',  'Pago aprobado desde Nequi.'],
        '3009999999' => ['Rechazado', 'Saldo insuficiente en la cuenta Nequi.'],
        '3008888888' => ['Rechazado', 'El usuario rechazó la notificación de pago.'],
    ];
}


// =====================================================================
//  Utilidades de tarjeta
// =====================================================================

/** Deja solo los dígitos: el formulario permite escribir con espacios. */
function pago_solo_digitos(string $valor): string
{
    return preg_replace('/[^0-9]+/', '', $valor) ?? '';
}

/**
 * Algoritmo de Luhn: el dígito verificador que llevan todas las
 * tarjetas. Detecta un número tecleado con un error, antes de molestar
 * al banco. No dice nada sobre si la tarjeta existe o tiene fondos.
 */
function pago_luhn(string $numero): bool
{
    $suma  = 0;
    $doble = false;

    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $d = (int) $numero[$i];

        if ($doble) {
            $d *= 2;
            if ($d > 9) {
                $d -= 9;
            }
        }

        $suma += $d;
        $doble = !$doble;
    }

    return $suma > 0 && $suma % 10 === 0;
}

/** Marca deducida del prefijo, igual que hace cualquier formulario de pago. */
function pago_marca(string $numero): string
{
    if ($numero === '') {
        return 'Desconocida';
    }

    $p2 = (int) substr($numero, 0, 2);
    $p4 = (int) substr($numero, 0, 4);

    if ($numero[0] === '4') {
        return 'Visa';
    }
    if ($p2 >= 51 && $p2 <= 55) {
        return 'Mastercard';
    }
    if ($p4 >= 2221 && $p4 <= 2720) {
        return 'Mastercard';
    }
    if ($p2 === 34 || $p2 === 37) {
        return 'American Express';
    }
    if ($p2 === 36 || $p2 === 38 || $p2 === 30) {
        return 'Diners Club';
    }

    return 'Desconocida';
}

/**
 * Referencia única del intento, con el formato que suele devolver una
 * pasarela: TITA-20261026-9F3A1C. Es lo que el cliente cita si reclama.
 */
function pago_referencia(): string
{
    return 'TITA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}


// =====================================================================
//  Cobro
// =====================================================================

/*
 * Las tres funciones de cobro devuelven siempre la misma forma:
 *
 *   ['ok' => false, 'error' => '...']
 *       Los datos están mal escritos. NO es un intento de pago: la
 *       pasarela ni siquiera habría consultado al banco, así que no se
 *       registra nada y el cliente corrige el formulario.
 *
 *   ['ok' => true, 'estado' => 'Aprobado'|'Rechazado', 'mensaje' => ...,
 *    'marca' => ?, 'ultimos4' => ?, 'detalle' => ?]
 *       El "banco" respondió. Esto SÍ se guarda en la tabla `pago`,
 *       se haya aprobado o no.
 */

/** Cobro con tarjeta de crédito o débito. */
function pago_cobrar_tarjeta(array $in): array
{
    $numero = pago_solo_digitos($in['numero'] ?? '');
    $nombre = trim($in['nombre'] ?? '');
    $mes    = pago_solo_digitos($in['mes'] ?? '');
    $anio   = pago_solo_digitos($in['anio'] ?? '');
    $cvv    = pago_solo_digitos($in['cvv'] ?? '');

    if ($nombre === '') {
        return ['ok' => false, 'error' => 'Escribe el nombre que aparece en la tarjeta.'];
    }

    if (strlen($numero) < 13 || strlen($numero) > 19) {
        return ['ok' => false, 'error' => 'El número de la tarjeta debe tener entre 13 y 19 dígitos.'];
    }

    if (!pago_luhn($numero)) {
        return ['ok' => false, 'error' => 'El número de la tarjeta no es válido. Revísalo.'];
    }

    if ((int) $mes < 1 || (int) $mes > 12) {
        return ['ok' => false, 'error' => 'El mes de vencimiento debe estar entre 01 y 12.'];
    }

    if (strlen($anio) !== 2 && strlen($anio) !== 4) {
        return ['ok' => false, 'error' => 'Escribe el año de vencimiento (AA o AAAA).'];
    }

    $anioCompleto = strlen($anio) === 2 ? 2000 + (int) $anio : (int) $anio;

    // Una tarjeta vence el último día de su mes: día 0 del mes siguiente.
    $vence = mktime(23, 59, 59, (int) $mes + 1, 0, $anioCompleto);
    if ($vence < time()) {
        return ['ok' => false, 'error' => 'La tarjeta está vencida.'];
    }

    $marca     = pago_marca($numero);
    $largoCvv  = $marca === 'American Express' ? 4 : 3;

    if (strlen($cvv) !== $largoCvv) {
        return ['ok' => false, 'error' => "El código de seguridad debe tener $largoCvv dígitos."];
    }

    // A partir de aquí el número completo ya no se usa ni se devuelve.
    $ultimos4 = substr($numero, -4);
    $tarjetas = pago_tarjetas_prueba();

    if (isset($tarjetas[$numero])) {
        [$estado, $mensaje] = $tarjetas[$numero];
    } else {
        // Cualquier tarjeta no documentada -- incluida una real.
        $estado  = 'Rechazado';
        $mensaje = 'Tarjeta no autorizada por el banco emisor.';
    }

    return [
        'ok'       => true,
        'estado'   => $estado,
        'mensaje'  => $mensaje,
        'marca'    => $marca,
        'ultimos4' => $ultimos4,
        'detalle'  => $marca . ' terminada en ' . $ultimos4,
    ];
}

/** Cobro por PSE (débito desde cuenta bancaria). */
function pago_cobrar_pse(array $in): array
{
    $banco   = $in['banco'] ?? '';
    $tipoDoc = $in['tipo_documento'] ?? '';
    $doc     = pago_solo_digitos($in['documento'] ?? '');

    $bancos = pago_bancos_pse();

    if (!isset($bancos[$banco])) {
        return ['ok' => false, 'error' => 'Selecciona el banco desde el que vas a pagar.'];
    }

    if (!in_array($tipoDoc, ['CC', 'CE', 'NIT', 'TI'], true)) {
        return ['ok' => false, 'error' => 'Selecciona un tipo de documento válido.'];
    }

    if (strlen($doc) < 5 || strlen($doc) > 12) {
        return ['ok' => false, 'error' => 'El número de documento debe tener entre 5 y 12 dígitos.'];
    }

    [$nombreBanco, $estado, $mensaje] = $bancos[$banco];

    return [
        'ok'       => true,
        'estado'   => $estado,
        'mensaje'  => $mensaje,
        'marca'    => null,
        'ultimos4' => null,
        'detalle'  => $nombreBanco . ' / ' . $tipoDoc . ' ' . $doc,
    ];
}

/** Cobro por Nequi (notificación al celular). */
function pago_cobrar_nequi(array $in): array
{
    $celular = pago_solo_digitos($in['celular'] ?? '');

    if (strlen($celular) !== 10 || $celular[0] !== '3') {
        return ['ok' => false, 'error' => 'El celular debe tener 10 dígitos y empezar por 3.'];
    }

    $celulares = pago_celulares_prueba();

    if (isset($celulares[$celular])) {
        [$estado, $mensaje] = $celulares[$celular];
    } else {
        $estado  = 'Rechazado';
        $mensaje = 'El número no está registrado en Nequi.';
    }

    return [
        'ok'       => true,
        'estado'   => $estado,
        'mensaje'  => $mensaje,
        'marca'    => null,
        'ultimos4' => substr($celular, -4),
        'detalle'  => 'Nequi ' . substr($celular, 0, 3) . '***' . substr($celular, -4),
    ];
}

/** Despacha al método correspondiente. */
function pago_cobrar(string $metodo, array $in): array
{
    switch ($metodo) {
        case 'Tarjeta':
            return pago_cobrar_tarjeta($in);
        case 'PSE':
            return pago_cobrar_pse($in);
        case 'Nequi':
            return pago_cobrar_nequi($in);
    }

    return ['ok' => false, 'error' => 'Método de pago no reconocido.'];
}
