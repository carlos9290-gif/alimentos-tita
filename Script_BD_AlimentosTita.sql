-- =====================================================================
--  Alimentos Tita - Script de base de datos
--  Proyecto: E-commerce de empanadas congeladas
--
--  Motor  : MySQL / MariaDB (XAMPP, puerto 3307)
--  Charset: utf8mb4 / utf8mb4_unicode_ci
--
--  USO:
--    mysql -u root -P 3307 < Script_BD_AlimentosTita.sql
--    o importarlo desde phpMyAdmin.
--
--  NOTA: este script refleja el esquema que realmente consulta el
--  codigo PHP (tablas en minuscula y singular). El script anterior
--  describia un modelo distinto (Usuario / Productos / Orden_de_Pedido
--  / Pago / Envio) que ninguna pagina utilizaba.
-- =====================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE,
    SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


-- ---------------------------------------------------------------------
-- Esquema
-- ---------------------------------------------------------------------

CREATE SCHEMA IF NOT EXISTS alimentos_tita
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE alimentos_tita;


-- ---------------------------------------------------------------------
-- Reinstalacion limpia (descomentar SOLO si se quiere borrar todo)
-- ---------------------------------------------------------------------
-- DROP TABLE IF EXISTS detalle_pedido, carrito, pedido, contacto, producto, cliente, admin;


-- ---------------------------------------------------------------------
-- cliente
--   Usada por: crear_cuenta/save_cliente, inicio_sesion/autenticar_cliente,
--              olvido_contrasena, recuperar_contrasena,
--              admin/gestion_cliente, admin/editar_cliente
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cliente (
  id_cliente          INT          NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(80)  NOT NULL,
  apellido            VARCHAR(80)  NOT NULL,
  correo              VARCHAR(120) NOT NULL,
  direccion           VARCHAR(150) NULL,
  telefono            VARCHAR(20)  NULL,

  -- Hoy guarda texto plano. VARCHAR(255) deja espacio para el hash de
  -- password_hash() que se introduce en la Fase 2, sin migrar la tabla.
  contrasena          VARCHAR(255) NOT NULL,

  -- Recuperacion de contrasena: bin2hex(random_bytes(16)) = 32 chars
  token_recuperacion  VARCHAR(64)  NULL DEFAULT NULL,
  token_expira        DATETIME     NULL DEFAULT NULL,

  fecha_registro      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id_cliente),

  -- El login hace WHERE correo = ? y exige num_rows == 1: sin esta
  -- restriccion, un correo duplicado rompe el inicio de sesion.
  UNIQUE KEY uq_cliente_correo (correo),

  KEY idx_cliente_token (token_recuperacion)

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- admin
--   Usada por: admin/auth_admin, admin/crear_admin
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS admin (
  id_admin    INT          NOT NULL AUTO_INCREMENT,
  usuario     VARCHAR(50)  NOT NULL,
  correo      VARCHAR(120) NOT NULL,
  contrasena  VARCHAR(255) NOT NULL,

  PRIMARY KEY (id_admin),
  UNIQUE KEY uq_admin_usuario (usuario)

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- producto
--   Usada por: casa, negocio, evento, carrito, pedido, detalle_pedido,
--              admin/gestion_producto, admin/crear_producto,
--              admin/editar_producto, admin/eliminar_producto
--
--   estado_producto: el codigo escribe 'activo'/'inactivo'
--   (admin/crear_producto.php) y consulta 'Activo'/'Inactivo'
--   (casa.php, negocio.php, admin/gestion_producto.php). La collation
--   utf8mb4_unicode_ci es case-insensitive, asi que ambas formas
--   funcionan contra el mismo ENUM.
--
--   imagen_empanada: ruta relativa a la raiz del proyecto, siempre con
--   prefijo 'imagenes/' (validado en admin/crear_producto.php).
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS producto (
  id_producto      INT            NOT NULL AUTO_INCREMENT,
  nombre           VARCHAR(100)   NOT NULL,
  descripcion      TEXT           NULL,
  precio           DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  stock            INT            NOT NULL DEFAULT 0,
  estado_producto  ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  imagen_empanada  VARCHAR(255)   NULL,

  PRIMARY KEY (id_producto),
  KEY idx_producto_estado (estado_producto),
  KEY idx_producto_nombre (nombre)

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- carrito
--   Usada por: agregar_carrito, carrito, eliminar_carrito,
--              vaciar_carrito, pedido, guardar_pedido,
--              admin/eliminar_item_carrito
--
--   agregar_carrito.php busca la fila (id_cliente, id_producto) y
--   actualiza o inserta: la clave unica garantiza esa premisa.
--   CASCADE en ambas FK porque el carrito es informacion efimera y
--   admin/eliminar_producto.php hace DELETE fisico del producto.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS carrito (
  id_carrito   INT NOT NULL AUTO_INCREMENT,
  id_cliente   INT NOT NULL,
  id_producto  INT NOT NULL,
  cantidad     INT NOT NULL DEFAULT 1,

  PRIMARY KEY (id_carrito),
  UNIQUE KEY uq_carrito_cliente_producto (id_cliente, id_producto),
  KEY idx_carrito_producto (id_producto),

  CONSTRAINT fk_carrito_cliente
    FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_carrito_producto
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
    ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- pedido
--   Usada por: guardar_pedido, detalle_pedido, mis_pedidos,
--              admin/pedido_admin, admin/detalle_pedido_admin,
--              admin/save_pedido_admin
--
--   estado: los valores del selector de admin/pedido_admin.php.
--   RESTRICT sobre cliente: el historial de pedidos no debe
--   desaparecer al borrar un cliente (ver NOTAS al final).
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS pedido (
  id_pedido        INT           NOT NULL AUTO_INCREMENT,
  id_cliente       INT           NOT NULL,
  direccion_envio  VARCHAR(150)  NOT NULL,
  total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado           ENUM('Pendiente','Pagado','Enviado','Entregado','Cancelado')
                                 NOT NULL DEFAULT 'Pendiente',
  fecha_pedido     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id_pedido),
  KEY idx_pedido_cliente (id_cliente),
  KEY idx_pedido_estado (estado),
  KEY idx_pedido_fecha (fecha_pedido),

  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- detalle_pedido
--   Usada por: guardar_pedido, detalle_pedido, calculadora_total,
--              admin/detalle_pedido_admin
--
--   precio_unitario no lo escribe el codigo todavia (Fase 3): se deja
--   con DEFAULT 0.00 para que los INSERT actuales sigan funcionando.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS detalle_pedido (
  id_detalle       INT           NOT NULL AUTO_INCREMENT,
  id_pedido        INT           NOT NULL,
  id_producto      INT           NOT NULL,
  cantidad         INT           NOT NULL,
  precio_unitario  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  subtotal         DECIMAL(10,2) NOT NULL,

  PRIMARY KEY (id_detalle),
  KEY idx_detalle_pedido (id_pedido),
  KEY idx_detalle_producto (id_producto),

  CONSTRAINT fk_detalle_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido (id_pedido)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- contacto
--   Usada por: contacto.php -> save_contactenos.php
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS contacto (
  id_contacto        INT          NOT NULL AUTO_INCREMENT,
  nombre_contacto    VARCHAR(80)  NOT NULL,
  apellido_contacto  VARCHAR(80)  NOT NULL,
  correo_contacto    VARCHAR(120) NOT NULL,
  comentario         TEXT         NOT NULL,
  fecha_envio        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id_contacto),
  KEY idx_contacto_fecha (fecha_envio)

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- =====================================================================
--  DATOS DE PRUEBA
--  INSERT IGNORE + id explicito: el script se puede re-ejecutar sin
--  duplicar filas.
-- =====================================================================

-- Los nombres que contienen 'coctel' quedan fuera de casa.php por
-- diseno (WHERE nombre NOT LIKE '%coctel%'): son el producto de eventos.

INSERT IGNORE INTO producto
  (id_producto, nombre, descripcion, precio, stock, estado_producto, imagen_empanada)
VALUES
  (1, 'Empanadas de Papa y Carne x12',
      'Paquete de 12 empanadas congeladas de maiz rellenas de papa y carne.',
      18000, 120, 'activo', 'imagenes/papa-carne.jpeg'),

  (2, 'Empanadas de Carne x12',
      'Paquete de 12 empanadas congeladas de maiz rellenas de carne desmechada.',
      19000, 100, 'activo', 'imagenes/Carne.jpeg'),

  (3, 'Empanadas de Pollo x12',
      'Paquete de 12 empanadas congeladas de maiz rellenas de pollo.',
      19000,  90, 'activo', 'imagenes/pollo.jpeg'),

  (4, 'Empanadas de Queso x12',
      'Paquete de 12 empanadas congeladas de maiz rellenas de queso.',
      17000, 110, 'activo', 'imagenes/Queso.jpeg'),

  (5, 'Empanadas de Pollo x25 (Institucional)',
      'Presentacion institucional para negocios: 25 empanadas de pollo.',
      36000,  60, 'activo', 'imagenes/empanada-pollo.jpeg'),

  (6, 'Empanada Coctelera x50',
      'Empanada pequena tipo coctel, ideal para eventos y pasabocas.',
      45000,  40, 'activo', 'imagenes/papa-carne.jpeg'),

  (7, 'Empanada Coctelera de Queso x50',
      'Empanada coctelera rellena de queso para eventos.',
      47000,  35, 'activo', 'imagenes/Queso.jpeg');



-- ---------------------------------------------------------------------
-- categoria / producto_categoria
--
--   Los canales de venta (casa / negocio / evento) estaban "modelados"
--   con un WHERE nombre NOT LIKE '%coctel%' en casa.php y negocio.php,
--   y evento.php no filtraba nada. Cualquier producto nuevo rompia esa
--   clasificacion.
--
--   Ahora la relacion es explicita y muchos-a-muchos: un mismo producto
--   puede ofrecerse en varios canales (por ejemplo un paquete x12 sirve
--   para la casa y para un negocio pequeno).
--
--   Usada por: casa.php, negocio.php, evento.php,
--              admin/crear_producto.php, admin/editar_producto.php,
--              admin/gestion_producto.php
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categoria (
  id_categoria INT          NOT NULL AUTO_INCREMENT,
  slug         VARCHAR(30)  NOT NULL,   -- el que usan las paginas
  nombre       VARCHAR(60)  NOT NULL,
  descripcion  VARCHAR(255) NULL,

  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categoria_slug (slug)

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS producto_categoria (
  id_producto  INT NOT NULL,
  id_categoria INT NOT NULL,

  PRIMARY KEY (id_producto, id_categoria),
  KEY idx_pc_categoria (id_categoria),

  -- Si se borra un producto, su clasificacion se va con el; las
  -- categorias en cambio no se borran si tienen productos.
  CONSTRAINT fk_pc_producto
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_pc_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


INSERT IGNORE INTO categoria (id_categoria, slug, nombre, descripcion) VALUES
  (1, 'casa',    'Para tu casa',    'Paquetes familiares de empanadas congeladas.'),
  (2, 'negocio', 'Para tu negocio', 'Presentaciones para tiendas, restaurantes y cafeterias.'),
  (3, 'evento',  'Para tu evento',  'Empanada coctelera y presentaciones para pasabocas.');


-- Clasificacion inicial de los productos semilla.
INSERT IGNORE INTO producto_categoria (id_producto, id_categoria) VALUES
  (1, 1), (1, 2),   -- Papa y Carne x12        -> casa y negocio
  (2, 1), (2, 2),   -- Carne x12               -> casa y negocio
  (3, 1), (3, 2),   -- Pollo x12               -> casa y negocio
  (4, 1), (4, 2),   -- Queso x12               -> casa y negocio
  (5, 2), (5, 3),   -- Pollo x25 institucional -> negocio y evento
  (6, 3),           -- Coctelera x50           -> evento
  (7, 3);           -- Coctelera queso x50     -> evento


-- ---------------------------------------------------------------------
-- Administrador
-- ---------------------------------------------------------------------
-- No se siembra ninguna cuenta de administrador: hoy las contrasenas se
-- guardan en texto plano y dejarla aqui equivaldria a versionar una
-- credencial en el repositorio.
--
-- Crear el primer admin entrando UNA vez a:
--     http://localhost/<proyecto>/admin/crear_admin.php
--
-- IMPORTANTE: esa pagina no pide autenticacion (cualquiera puede crear
-- un administrador). Cerrarla es el punto 6 de la Fase 2.


SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE = @OLD_SQL_MODE;


-- =====================================================================
--  NOTAS SOBRE DECISIONES DE INTEGRIDAD
--
--  1. cliente.correo y admin.usuario son UNIQUE. Hoy save_cliente.php
--     no maneja el error de duplicado: si alguien se registra con un
--     correo existente, el INSERT falla en silencio y no se le
--     redirige. Debe mostrarse un mensaje (Fase 2, junto con
--     password_hash()).
--
--  2. pedido.id_cliente es RESTRICT: admin/eliminar_cliente.php fallara
--     al borrar un cliente que ya tenga pedidos. Es intencional -- el
--     historial de ventas no debe borrarse en cascada. Ese modulo debe
--     pasar a baja logica o avisar al administrador.
--
--  3. detalle_pedido.id_producto es RESTRICT: no se puede borrar un
--     producto que ya fue vendido. Para retirarlo del catalogo hay que
--     ponerlo en estado_producto = 'inactivo' (que es justamente para
--     lo que existe ese campo), no usar admin/eliminar_producto.php.
--
--  4. Las tablas Pago y Envio del modelo original no se crean porque
--     ninguna pagina las consulta: no hay pasarela de pago ni modulo de
--     envios implementado. Se agregaran cuando exista ese codigo.
-- =====================================================================
