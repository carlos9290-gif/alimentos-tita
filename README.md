# Alimentos Tita — Tienda en línea

E-commerce para **Alimentos Tita**, fábrica de empanadas congeladas 100% de maíz
ubicada en Bogotá (Calle 75 # 54-21, Barrio 12 de Octubre).

El sitio permite a los clientes registrarse, explorar el catálogo según el canal
de consumo (casa, negocio o evento), armar un carrito y confirmar pedidos. Incluye
un panel de administración para gestionar productos, clientes y el estado de los
pedidos.

**Integrantes:** · Carlos Pérez 

---

## Tecnologías

| Componente | Versión |
|---|---|
| PHP | **8.1 o superior** (probado en 8.2) |
| MySQL / MariaDB | MariaDB 10.4 (XAMPP) |
| Bootstrap | 5.3 (vía CDN) |
| Swiper | 11 (vía CDN) |

No usa framework, Composer ni npm: es PHP procedural con `mysqli`.

> **Por qué PHP 8.1 como mínimo:** a partir de esa versión `mysqli` lanza
> excepciones en lugar de devolver `false`. El manejo de errores del proyecto
> (transacciones, claves duplicadas, restricciones de llave foránea) depende de
> ese comportamiento. En PHP 8.0 los errores pasarían en silencio.

---

## Instalación

### 1. Requisitos

XAMPP con PHP 8.1+ y MariaDB/MySQL.

### 2. Configurar el puerto de MySQL

El proyecto usa el puerto **3307**, no el 3306, para no chocar con otras
instalaciones de MySQL que puedan existir en la máquina.

Edita `xampp\mysql\bin\my.ini` y deja el puerto así en **ambas** secciones:

```ini
[client]
port=3307

[mysqld]
port=3307
```

Luego inicia MySQL desde el panel de XAMPP.

> Si prefieres el 3306, cambia también `'puerto'` en tu `config.php` (paso 3).

### 3. Crear tu archivo de configuración

Las credenciales no están en el repositorio. Copia la plantilla:

```bash
# Windows
copy config.php.example config.php

# Linux / Mac
cp config.php.example config.php
```

Y ajusta los valores si tu instalación difiere:

```php
return [
    'host'     => 'localhost',
    'puerto'   => 3307,
    'usuario'  => 'root',
    'password' => '',
    'base'     => 'alimentos_tita',
    'charset'  => 'utf8mb4',
];
```

`config.php` está en `.gitignore`: cada integrante tiene el suyo.

### 4. Crear la base de datos

```bash
mysql -u root -P 3307 < Script_BD_AlimentosTita.sql
```

O impórtalo desde phpMyAdmin. El script es **idempotente**: se puede volver a
ejecutar sin duplicar datos. Crea las tablas y carga 7 productos de ejemplo ya
clasificados por canal.

### 5. Levantar el sitio

El proyecto debe servirse con la **carpeta del proyecto como raíz del servidor**,
porque las páginas de autenticación enlazan sus estilos con rutas absolutas
(`/assets/...`).

La forma más simple:

```bash
php -S localhost:8000
```

Y abrir <http://localhost:8000>.

> Si prefieres Apache, no copies el proyecto a una subcarpeta de `htdocs`
> (`htdocs/tita/`): esas cuatro páginas quedarían sin estilos. Usa un
> VirtualHost cuyo `DocumentRoot` apunte a la carpeta del proyecto.

### 6. Primer administrador

Entra **una vez** a `/admin/crear_admin.php` y crea la cuenta.

Esa página solo está abierta mientras no exista ningún administrador. En cuanto
creas el primero, queda cerrada: para crear más hay que estar dentro del panel.

---

## Estructura

```
├── index.php                 Portada (Header + Cartas + Carrusel + Contáctenos)
├── Navbar.php                Barra de navegación compartida
├── Header.php  Footer.php    Parciales de la portada
├── Cartas.php  Carrusel_productos.php  Contactenos.php
│
├── conexion.php              Conexión a MySQL (lee config.php)
├── config.php.example        Plantilla de credenciales
├── seguridad.php             CSRF, escape de HTML y hashing de contraseñas
├── verificar_sesion.php      Guardia de sesión para páginas de cliente
│
├── Catálogo
│   ├── productos.php         Portada de productos
│   ├── casa.php              Canal "casa"
│   ├── negocio.php           Canal "negocio"
│   └── evento.php            Canal "evento"
│
├── Cuenta
│   ├── crear_cuenta.php   → save_cliente.php
│   ├── inicio_sesion.php  → autenticar_cliente.php
│   ├── cerrar_sesion.php
│   └── olvido_contrasena.php → recuperar_contrasena.php
│
├── Compra
│   ├── agregar_carrito.php
│   ├── carrito.php        eliminar_carrito.php   vaciar_carrito.php
│   ├── pedido.php         → guardar_pedido.php
│   ├── detalle_pedido.php    mis_pedidos.php
│   ├── cancelar_pedido.php   (devuelve el stock al inventario)
│   ├── pedidos.php           Operaciones de pedido compartidas
│   └── calculadora_total.php (endpoint JSON)
│
├── Pago (SIMULADO)
│   ├── pagos.php             Motor del simulador: Luhn, marcas, credenciales de prueba
│   ├── pasarela.php          Pantalla de pago (Tarjeta / PSE / Nequi)
│   └── procesar_pago.php     Registra el intento y mueve el pedido a Pagado
│
├── Contenido
│   ├── nosotros.php
│   └── contacto.php       → save_contactenos.php
│
├── admin/
│   ├── login_admin.php    → auth_admin.php
│   ├── crear_admin.php       logout_admin.php
│   ├── index_admin.php       Panel principal
│   ├── gestion_producto.php  crear_producto.php  editar_producto.php  eliminar_producto.php
│   ├── gestion_cliente.php   editar_cliente.php  eliminar_cliente.php
│   └── pedido_admin.php      detalle_pedido_admin.php  save_pedido_admin.php
│
├── assets/                   Estilos de la plantilla (solo lo que se usa)
├── imagenes/                 Fotos del negocio y productos
└── Script_BD_AlimentosTita.sql
```

---

## Modelo de datos

```
cliente ──< pedido ──< detalle_pedido >── producto
   │          │                              │
   │          └──< pago                      │
   │                                         │
   └──< carrito >────────────────────────────┤
                                             │
                     producto_categoria >── categoria
admin          contacto
```

| Tabla | Para qué |
|---|---|
| `cliente` | Cuentas de clientes (contraseña cifrada, token de recuperación) |
| `admin` | Cuentas del panel |
| `producto` | Catálogo: precio, stock, estado activo/inactivo, ruta de imagen |
| `categoria` | Canales de venta: `casa`, `negocio`, `evento` |
| `producto_categoria` | Relación muchos a muchos: en qué páginas aparece cada producto |
| `carrito` | Carrito por cliente (una fila por producto) |
| `pedido` | Cabecera: dirección, total, estado, fecha |
| `detalle_pedido` | Líneas del pedido con `precio_unitario` congelado |
| `pago` | Un registro **por intento** de pago, aprobado o rechazado |
| `contacto` | Mensajes del formulario de contacto |

### Decisiones de integridad

- **El total del pedido se calcula en el servidor**, nunca se recibe del navegador.
- `detalle_pedido.precio_unitario` guarda el precio del momento: si mañana cambia
  el precio del producto, el histórico del pedido no se altera.
- El stock se descuenta dentro de la transacción del pedido, con bloqueo de filas
  (`SELECT ... FOR UPDATE`) para que dos pedidos simultáneos no vendan el mismo stock.
- **No se puede borrar** un cliente con pedidos ni un producto ya vendido: el
  historial de ventas está protegido por llaves foráneas. Para retirar un producto
  del catálogo se marca como `inactivo`.
- **El stock se reserva al crear el pedido, no al pagarlo.** Un pedido
  `Pendiente` ya tiene producto apartado, así que **cancelarlo devuelve el stock
  al inventario** (`pedidos.php`). Por eso `Cancelado` es un estado terminal: el
  stock ya volvió y revivir el pedido obligaría a descontarlo de nuevo, cuando
  quizá ya se vendió.
- `pago` guarda **un intento por fila**, no un pago por pedido. Un rechazo
  seguido de un reintento exitoso deja las dos filas, que es lo que permite
  auditar por qué un pedido tardó en pagarse.

---

## Seguridad

- Contraseñas con `password_hash()` / `password_verify()`. Las cuentas antiguas
  guardadas en texto plano migran solas al iniciar sesión.
- Todas las consultas usan sentencias preparadas.
- Token CSRF en todos los formularios POST; las acciones destructivas
  (eliminar, vaciar carrito, cambiar estado) solo aceptan POST.
- Escape de HTML en todas las salidas provenientes de la base de datos.
- Cada cliente solo puede ver sus propios pedidos.
- El identificador de sesión se renueva al iniciar sesión.
- Los mensajes de error no revelan detalles internos (usuario, host, versión del
  motor): esos datos van al log del servidor.

---

## Pasarela de pago (simulada)

**No hay dinero real de por medio.** `pagos.php` imita un entorno sandbox:
decide aprobar o rechazar según unas credenciales de prueba fijas, igual que
hacen Stripe, Wompi o Mercado Pago en sus ambientes de desarrollo.

Flujo:

```
carrito → pedido.php → guardar_pedido.php ──→ pasarela.php ──→ procesar_pago.php ──→ detalle_pedido.php
                       (Pendiente, stock         (Tarjeta,        (aprueba o            (estado + referencia)
                        reservado)                PSE, Nequi)      rechaza)
                                                      ↑                 │
                                                      └─── rechazo ─────┘   reintento
                                            cancelar_pedido.php → devuelve el stock
```

### Credenciales de prueba

| Tarjeta | Resultado |
|---|---|
| `4111 1111 1111 1111` · `5555 5555 5555 4444` | Aprobada |
| `4000 0000 0000 0002` | Fondos insuficientes |
| `4000 0000 0000 0069` | Tarjeta vencida |
| `4000 0000 0000 0127` | Código de seguridad incorrecto |
| `4000 0000 0000 0119` | Error de comunicación con el banco |

Vencimiento futuro y CVV de 3 dígitos. **Nequi:** `3001234567` aprueba,
`3009999999` y `3008888888` rechazan. **PSE:** el resultado lo dice el nombre
del banco que elijas.

Cualquier otra tarjeta o celular **se rechaza**, aunque sea válido. Es
deliberado: teclear una tarjeta real nunca debe tener recompensa.

### Lo que hace que sea un pago y no un `UPDATE`

- **Nunca se guarda el número de tarjeta ni el CVV**, solo marca y últimos 4.
- **Idempotencia:** la fila del pedido se bloquea con `FOR UPDATE` antes de
  mirar su estado. Dos pagos simultáneos del mismo pedido producen **un solo
  cobro**; el segundo encuentra el pedido ya pagado y no escribe nada.
- Un error de formato **no** se registra como intento: la pasarela tampoco
  habría consultado al banco.
- El importe sale del pedido, nunca del formulario.
- El aviso de **MODO SIMULACIÓN** es permanente. Una pantalla que pide datos de
  tarjeta sin advertir que es falsa tiene la misma forma que una de phishing.

---

## Pendientes conocidos

| Tema | Detalle |
|---|---|
| Envío de correo | La recuperación de contraseña no envía correo: falta configurar SMTP en `php.ini`. Mientras tanto el enlace se registra en el archivo temporal `tita_recuperacion.log` del servidor, fuera de la carpeta pública. |
| Pasarela de pago | **Simulada**, no real. Para cobrar de verdad hay que integrar Wompi, Mercado Pago o PayU, lo que exige llaves de comercio y una URL pública que reciba el webhook de confirmación. |
| Pedidos abandonados | Un pedido `Pendiente` mantiene el stock reservado hasta que alguien lo cancele. No hay caducidad automática; con volumen real haría falta liberar las reservas viejas. |
| Módulo de envíos | No implementado (sin número de guía ni seguimiento). |
| Subresource Integrity | Las páginas cargan Bootstrap y Swiper desde CDN sin atributo `integrity`. Afecta a todo el proyecto, no solo a la pasarela. |
| Descuento de `negocio.php` | La página anuncia "10% OFF" pero el carrito y el total cobran el precio completo. El descuento hoy es solo visual. |
| `agregar_carrito.php` | Funciona por GET y sin token CSRF. No es destructivo, pero permite que un tercero agregue productos al carrito de otra persona. |
| Estilos duplicados | Varias páginas repiten el mismo bloque `<style>` en línea en lugar de usar `style.css`. |
| `guardar_estado.php` | Código sin uso: apunta a una tabla `confirmacion` que no existe. Está cerrado con autenticación, pero probablemente deba borrarse. |

---

## Solución de problemas

**«No se pudo conectar con la base de datos»**
MySQL no está encendido, o el puerto de `config.php` no coincide con el de
`my.ini`. Verifica que ambos digan `3307`.

**«Falta el archivo de configuración»**
No copiaste `config.php.example` como `config.php` (paso 3).

**Las páginas de login y registro se ven sin estilos**
El servidor no tiene la carpeta del proyecto como raíz. Ver el paso 5.

**MySQL de XAMPP no arranca**
Suele ser otro MySQL ocupando el puerto. Ese es justamente el motivo de usar
el 3307.

**«Solicitud no válida o expirada»**
La sesión caducó. Recarga la página y vuelve a enviar el formulario.
