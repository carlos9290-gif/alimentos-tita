# Guía de instalación — Alimentos Tita

Guía paso a paso para poner el proyecto a funcionar en un computador nuevo.
Si algo falla, ve al final: **Solución de problemas**.

Tiempo estimado: 15–20 minutos.

---

## Paso 0 — Descomprimir

Descomprime el archivo `.zip` en una ruta **sin espacios ni tildes**.

✅ Bien: `C:\Proyectos\AlimentosTita`
❌ Mal: `C:\Users\Juan Pérez\Escritorio\Mi Proyecto\`

Dentro deberías ver `index.php`, `README.md`, la carpeta `admin\` y la carpeta
`imagenes\`.

---

## Paso 1 — Instalar XAMPP

Descarga XAMPP con **PHP 8.1 o superior** desde
<https://www.apachefriends.org/es/download.html>.

> **Importante:** el proyecto **no funciona con PHP 8.0 ni anterior**. Desde PHP
> 8.1 la extensión `mysqli` avisa de los errores lanzando excepciones, y todo el
> manejo de errores del proyecto (pedidos, correos duplicados, control de stock)
> depende de eso. Con una versión anterior los fallos ocurrirían en silencio.

Para verificar tu versión, abre el panel de XAMPP → botón **Shell** y escribe:

```
php -v
```

Debe decir `PHP 8.1.x` o superior.

---

## Paso 2 — Cambiar el puerto de MySQL a 3307

El proyecto usa el puerto **3307** en lugar del 3306 habitual. Esto evita el
choque con otras instalaciones de MySQL que suelen quedar en el computador
(MySQL Workbench, SQL Server, instalaciones viejas…), que es la causa más común
de que "MySQL no arranca" en XAMPP.

1. Abre el panel de XAMPP.
2. En la fila de **MySQL**, haz clic en **Config** → **my.ini**.
3. Se abre el Bloc de notas. Busca con `Ctrl + B` la palabra `port`.
4. Hay **dos** líneas `port=3306`, una bajo `[client]` y otra bajo `[mysqld]`.
   Cambia **ambas** a `3307`:

```ini
[client]
port=3307

[mysqld]
port=3307
```

5. Guarda con `Ctrl + G` y cierra.
6. En el panel de XAMPP, pulsa **Start** en la fila de MySQL.
   Debe quedar en verde.

> **¿Prefieres usar el 3306?** Puedes hacerlo, pero entonces en el Paso 3 debes
> poner `'puerto' => 3306` en tu `config.php`. Ambos números tienen que coincidir.

---

## Paso 3 — Crear tu archivo de configuración

El proyecto **no incluye** las credenciales de la base de datos: cada persona
crea las suyas.

1. En la carpeta del proyecto, busca el archivo **`config.php.example`**.
2. Haz una copia en la misma carpeta.
3. Renombra la copia como **`config.php`** (sin el `.example`).

Ábrelo con el Bloc de notas. Debe quedar así para una instalación normal de XAMPP:

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

Si le pusiste contraseña a tu MySQL, escríbela entre las comillas de `'password'`.

> **Nota:** si Windows te oculta las extensiones, al renombrar podrías crear
> `config.php.txt` por error. Para evitarlo: en el Explorador, pestaña **Vista**
> → marca **Extensiones de nombre de archivo**.

---

## Paso 4 — Crear la base de datos

> ### No tienes que crear la base de datos a mano
>
> Es la confusión más común. **No** entres a phpMyAdmin a pulsar «Nueva» ni a
> escribir `alimentos_tita` en ninguna parte: el script lo hace solo. Sus
> primeras instrucciones son:
>
> ```sql
> CREATE SCHEMA IF NOT EXISTS alimentos_tita ...   -- crea la base
> USE alimentos_tita;                              -- se posiciona en ella
> ```
>
> Tampoco importa qué base tengas seleccionada en phpMyAdmin al importar: el
> `USE` del propio script redirige todo al lugar correcto.

Elige **una** de las tres formas siguientes. La A es la recomendada.

### Opción A — Importar el archivo en phpMyAdmin (recomendada)

Aquí **no se copia ni se pega nada**: se sube el archivo.

1. En el panel de XAMPP, arranca también **Apache** (phpMyAdmin lo necesita).
2. Abre <http://localhost/phpmyadmin>.
3. Pestaña **Importar**, en la barra de arriba.
4. **Seleccionar archivo** → busca `Script_BD_AlimentosTita.sql` en la carpeta
   del proyecto.
5. Baja hasta el final y pulsa **Continuar** / **Importar**.

El archivo pesa unos 15 KB y el límite de subida de XAMPP es de 40 MB, así que
no habrá problemas de tamaño.

### Opción B — Por consola (una sola línea)

Panel de XAMPP → botón **Shell**, y desde la carpeta del proyecto:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 < Script_BD_AlimentosTita.sql
```

El `<` le pasa el archivo a MySQL. Si no aparece ningún mensaje, funcionó:
MySQL solo responde cuando hay errores.

### Opción C — Copiar y pegar (funciona, pero es la más frágil)

1. phpMyAdmin → pestaña **SQL**.
2. Abre el `.sql` con el **Bloc de notas**, `Ctrl + A`, `Ctrl + C`.
3. Pégalo en el recuadro y pulsa **Continuar**.

Funciona porque el script no lleva tildes ni caracteres especiales. Pero basta
con seleccionar mal el texto para que la base quede a medias.

> Si usas esta vía, abre el archivo con el **Bloc de notas**, nunca con Word:
> Word reemplaza las comillas rectas por comillas tipográficas y rompe el SQL.

---

### Cómo saber si quedó bien

Con cualquiera de las tres, en la columna izquierda de phpMyAdmin debe aparecer
la base **`alimentos_tita`** con **9 tablas**:

```
admin · carrito · categoria · cliente · contacto
detalle_pedido · pedido · producto · producto_categoria
```

Y al entrar a la tabla `producto` deben verse **7 filas** con las empanadas de
ejemplo, ya clasificadas por canal de venta, para que el catálogo no salga vacío.

> **¿Algo salió mal?** Puedes volver a ejecutar el script las veces que quieras:
> usa `IF NOT EXISTS` e `INSERT IGNORE`, así que no duplica datos ni borra lo
> que ya exista.

## Paso 5 — Levantar el sitio

El proyecto debe servirse con **su propia carpeta como raíz del servidor**.
Varias páginas (iniciar sesión, crear cuenta, recuperar contraseña) enlazan sus
estilos con rutas absolutas tipo `/assets/...`, así que si quedan en una
subcarpeta se ven sin diseño.

### Opción A — Servidor de PHP (la más simple, recomendada)

1. Panel de XAMPP → botón **Shell**.
2. Entra a la carpeta del proyecto y arráncalo:

```bash
cd C:\Proyectos\AlimentosTita
php -S localhost:8000
```

3. Deja esa ventana abierta y entra a <http://localhost:8000>.

Para apagarlo: `Ctrl + C` en esa ventana, o simplemente ciérrala.

### Opción B — Apache

Si quieres usar Apache, **no copies el proyecto a una subcarpeta** de `htdocs`
(como `htdocs\tita\`), porque esas cuatro páginas perderían los estilos.

Configura un VirtualHost cuyo `DocumentRoot` apunte a la carpeta del proyecto,
o bien copia el **contenido** del proyecto directamente dentro de `htdocs\`.

---

## Paso 6 — Crear el primer administrador

Entra **una vez** a:

```
http://localhost:8000/admin/crear_admin.php
```

Llena usuario, correo y contraseña (mínimo 8 caracteres).

> Esa página solo está abierta mientras **no exista ningún administrador**. En
> cuanto creas el primero queda cerrada: para crear más hay que entrar al panel
> con una cuenta de administrador.

---

## Paso 7 — Probar que todo funciona

| Acción | Dónde | Resultado esperado |
|---|---|---|
| Crear cuenta de cliente | `/crear_cuenta.php` | Te redirige al login |
| Iniciar sesión | `/inicio_sesion.php` | Vuelves a la portada con tu nombre arriba |
| Ver catálogo | `/casa.php` | 4 productos con foto y precio |
| Agregar al carrito | Botón del producto | El carrito muestra el total |
| Confirmar pedido | `/carrito.php` → Finalizar compra | Te lleva al detalle del pedido |
| Panel | `/admin/login_admin.php` | Entras a la gestión |

Si los 6 funcionan, la instalación está completa.

---

## Solución de problemas

### «Falta el archivo de configuración»
No creaste `config.php` (Paso 3), o quedó como `config.php.txt`.
Activa las extensiones de archivo en el Explorador y revisa el nombre.

### «No se pudo conectar con la base de datos. Revisa que MySQL esté encendido»
Tres causas posibles:
1. MySQL no está en verde en el panel de XAMPP.
2. El puerto de `my.ini` y el de `config.php` no coinciden (los dos deben ser 3307).
3. Tu MySQL tiene contraseña y no la pusiste en `config.php`.

### MySQL no arranca en XAMPP (se pone rojo)
Casi siempre es otro programa ocupando el puerto. Para ver quién es, abre
PowerShell y ejecuta:

```powershell
Get-NetTCPConnection -State Listen | Where-Object { $_.LocalPort -in 3306,3307 }
```

Si el 3307 aparece ocupado, elige otro puerto libre (3308, por ejemplo) y
cámbialo **tanto en `my.ini` como en `config.php`**.

### Las páginas de login y registro se ven sin estilos
El servidor no tiene la carpeta del proyecto como raíz. Vuelve al Paso 5 y usa
la Opción A.

### «Table 'alimentos_tita.producto' doesn't exist»
No se importó el script, o se importó en otra base. Repite el Paso 4 y confirma
que aparecen las 9 tablas.

### El catálogo sale vacío
Las páginas filtran por **canal de venta**. Si creaste productos desde el panel
sin marcar ningún canal, no aparecen en ninguna página. Entra a
**Panel → Productos → Editar** y marca al menos uno.

### «Solicitud no válida o expirada»
La sesión caducó (protección contra CSRF). Recarga la página y reenvía el
formulario.

### «Parse error» o errores de sintaxis al abrir cualquier página
Tu PHP es anterior al 8.1. Revisa el Paso 1.

---

## Para quien comparte el proyecto

El ZIP **no incluye** `config.php` (credenciales locales) ni la carpeta `.git`
(historial, ~26 MB). Quien lo recibe crea su propio `config.php` en el Paso 3.

La base de datos viaja como **script**, no como copia: el `.sql` crea las tablas
y carga los 7 productos de ejemplo, sin clientes ni pedidos.

Si necesitas enviar también los datos reales (clientes, pedidos registrados),
expórtalos aparte:

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root -P 3307 alimentos_tita > respaldo_datos.sql
```

Quien lo reciba lo importa **después** del Paso 4, por phpMyAdmin o con:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 alimentos_tita < respaldo_datos.sql
```

> Ten en cuenta que ese respaldo contiene datos personales de clientes
> (nombres, correos, direcciones y teléfonos). Compártelo solo con quien deba
> tenerlos.
