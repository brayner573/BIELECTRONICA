-- ============================================================
--  FAXEL BI — Schema de Base de Datos Completo
--  Versión 2.0 (SaaS Multiempresa) | Plataforma Business Intelligence + IA
-- ============================================================

DROP DATABASE IF EXISTS faxel_bi;
CREATE DATABASE faxel_bi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE faxel_bi;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA: empresas
-- ============================================================
CREATE TABLE IF NOT EXISTS empresas (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    razon_social   VARCHAR(200) NOT NULL,
    ruc            VARCHAR(20)  NOT NULL,
    email          VARCHAR(180) NOT NULL,
    telefono       VARCHAR(20),
    direccion      VARCHAR(255),
    sector         VARCHAR(100),
    empleados_count INT          DEFAULT 1,
    logo_path      VARCHAR(255),
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empresas_ruc (ruc),
    UNIQUE KEY uk_empresas_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: sucursales
-- ============================================================
CREATE TABLE IF NOT EXISTS sucursales (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id    INT UNSIGNED NOT NULL,
    nombre        VARCHAR(120) NOT NULL,
    ciudad        VARCHAR(80)  NOT NULL,
    direccion     VARCHAR(255),
    telefono      VARCHAR(20),
    email         VARCHAR(120),
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sucursales_activo (activo),
    KEY idx_sucursales_empresa (empresa_id),
    CONSTRAINT fk_sucursales_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: objetivos_sucursales
-- ============================================================
CREATE TABLE IF NOT EXISTS objetivos_sucursales (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id     INT UNSIGNED NOT NULL,
    sucursal_id    INT UNSIGNED NOT NULL,
    anio           INT NOT NULL,
    mes            INT NOT NULL,
    meta_monto     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_objetivos_suc (sucursal_id, anio, mes),
    KEY idx_objetivos_empresa (empresa_id),
    CONSTRAINT fk_objetivos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_objetivos_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id    INT UNSIGNED NOT NULL,
    sucursal_id   INT UNSIGNED,
    nombre        VARCHAR(120) NOT NULL,
    apellido      VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('superadmin','empresa','gerente','analista','vendedor','operador') NOT NULL DEFAULT 'operador',
    avatar        VARCHAR(255),
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_login  TIMESTAMP    NULL,
    token_csrf    VARCHAR(64),
    failed_logins TINYINT      NOT NULL DEFAULT 0,
    locked_until  TIMESTAMP    NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email),
    KEY idx_usuarios_rol (rol),
    KEY idx_usuarios_activo (activo),
    KEY idx_usuarios_empresa (empresa_id),
    CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED NOT NULL,
    sucursal_id     INT UNSIGNED,
    codigo          VARCHAR(30)  NOT NULL,
    razon_social    VARCHAR(200) NOT NULL,
    ruc_dni         VARCHAR(20)  NOT NULL,
    tipo_doc        ENUM('RUC','DNI','CE','PASAPORTE') NOT NULL DEFAULT 'RUC',
    email           VARCHAR(180),
    telefono        VARCHAR(20),
    direccion       VARCHAR(255),
    ciudad          VARCHAR(80),
    categoria       ENUM('VIP','frecuente','regular','nuevo','inactivo') NOT NULL DEFAULT 'nuevo',
    churn_score     DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Score 0-100, mayor = más riesgo abandono',
    churn_riesgo    ENUM('bajo','medio','alto') NOT NULL DEFAULT 'bajo',
    ultima_compra   DATE         NULL,
    total_compras   INT UNSIGNED NOT NULL DEFAULT 0,
    monto_acumulado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    ticket_promedio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_clientes_codigo (codigo),
    UNIQUE KEY uk_clientes_ruc (ruc_dni),
    KEY idx_clientes_churn (churn_score),
    KEY idx_clientes_riesgo (churn_riesgo),
    KEY idx_clientes_ultima (ultima_compra),
    KEY idx_clientes_categoria (categoria),
    KEY idx_clientes_empresa (empresa_id),
    CONSTRAINT fk_clientes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_clientes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: categorias_producto
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias_producto (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos
-- ============================================================
CREATE TABLE IF NOT EXISTS productos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED NOT NULL,
    categoria_id    INT UNSIGNED,
    codigo          VARCHAR(50)  NOT NULL,
    nombre          VARCHAR(200) NOT NULL,
    descripcion     TEXT,
    unidad          VARCHAR(20)  NOT NULL DEFAULT 'UND',
    precio_venta    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    precio_costo    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    margen          DECIMAL(5,2)  GENERATED ALWAYS AS (
                        CASE WHEN precio_venta > 0
                        THEN ((precio_venta - precio_costo) / precio_venta) * 100
                        ELSE 0 END
                    ) STORED,
    stock           INT          NOT NULL DEFAULT 0,
    clasificacion   ENUM('A','B','C') NOT NULL DEFAULT 'C' COMMENT 'Análisis ABC',
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_productos_codigo (codigo),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_margen (margen),
    KEY idx_productos_clasificacion (clasificacion),
    KEY idx_productos_empresa (empresa_id),
    CONSTRAINT fk_productos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_producto(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: facturas
-- ============================================================
CREATE TABLE IF NOT EXISTS facturas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED NOT NULL,
    sucursal_id     INT UNSIGNED NOT NULL,
    cliente_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    serie           VARCHAR(10)  NOT NULL,
    correlativo     VARCHAR(10)  NOT NULL,
    numero_completo VARCHAR(25)  NOT NULL,
    tipo_comp       ENUM('01','03','07','08') NOT NULL DEFAULT '01' COMMENT '01=Factura,03=Boleta',
    fecha_emision   DATE         NOT NULL,
    fecha_venci     DATE,
    moneda          CHAR(3)      NOT NULL DEFAULT 'PEN',
    subtotal        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv             DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    estado          ENUM('emitida','anulada','pagada','vencida') NOT NULL DEFAULT 'emitida',
    xml_path        VARCHAR(255),
    cdr_path        VARCHAR(255),
    hash_cpe        VARCHAR(64),
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_facturas_numero (numero_completo),
    KEY idx_facturas_cliente (cliente_id),
    KEY idx_facturas_fecha (fecha_emision),
    KEY idx_facturas_estado (estado),
    KEY idx_facturas_sucursal (sucursal_id),
    KEY idx_facturas_tipo (tipo_comp),
    KEY idx_facturas_empresa (empresa_id),
    CONSTRAINT fk_facturas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_facturas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_facturas_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes(id),
    CONSTRAINT fk_facturas_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: ventas
-- ============================================================
CREATE TABLE IF NOT EXISTS ventas (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED  NOT NULL,
    factura_id      INT UNSIGNED  NOT NULL,
    sucursal_id     INT UNSIGNED  NOT NULL,
    cliente_id      INT UNSIGNED  NOT NULL,
    usuario_id      INT UNSIGNED  NOT NULL,
    fecha_venta     DATETIME      NOT NULL,
    subtotal        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    descuento       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv             DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    costo_total     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    utilidad        DECIMAL(14,2) GENERATED ALWAYS AS (total - costo_total) STORED,
    margen_pct      DECIMAL(5,2)  GENERATED ALWAYS AS (
                        CASE WHEN total > 0
                        THEN ((total - costo_total) / total) * 100
                        ELSE 0 END
                    ) STORED,
    estado          ENUM('completada','anulada','pendiente') NOT NULL DEFAULT 'completada',
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ventas_fecha (fecha_venta),
    KEY idx_ventas_cliente (cliente_id),
    KEY idx_ventas_sucursal (sucursal_id),
    KEY idx_ventas_usuario (usuario_id),
    KEY idx_ventas_estado (estado),
    KEY idx_ventas_empresa (empresa_id),
    CONSTRAINT fk_ventas_empresa  FOREIGN KEY (empresa_id)  REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_ventas_factura  FOREIGN KEY (factura_id)  REFERENCES facturas(id),
    CONSTRAINT fk_ventas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_ventas_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes(id),
    CONSTRAINT fk_ventas_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: detalle_venta
-- ============================================================
CREATE TABLE IF NOT EXISTS detalle_venta (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    venta_id        INT UNSIGNED  NOT NULL,
    producto_id     INT UNSIGNED  NOT NULL,
    cantidad        DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    precio_unitario DECIMAL(12,2) NOT NULL,
    costo_unitario  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    descuento       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal        DECIMAL(14,2) NOT NULL,
    subtotal_costo  DECIMAL(14,2) GENERATED ALWAYS AS (cantidad * costo_unitario) STORED,
    utilidad_linea  DECIMAL(14,2) GENERATED ALWAYS AS (subtotal - (cantidad * costo_unitario)) STORED,
    PRIMARY KEY (id),
    KEY idx_detalle_venta (venta_id),
    KEY idx_detalle_producto (producto_id),
    CONSTRAINT fk_detalle_venta    FOREIGN KEY (venta_id)   REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: predicciones
-- ============================================================
CREATE TABLE IF NOT EXISTS predicciones (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED  NOT NULL,
    tipo            ENUM('ventas_7d','ventas_30d','churn','xgboost') NOT NULL,
    modelo          VARCHAR(50)   NOT NULL,
    fecha_prediccion DATE         NOT NULL,
    valor_predicho  DECIMAL(14,2),
    limite_inf      DECIMAL(14,2),
    limite_sup      DECIMAL(14,2),
    exactitud       DECIMAL(5,2)  COMMENT 'R² score %',
    mae             DECIMAL(10,4) COMMENT 'Mean Absolute Error',
    rmse            DECIMAL(10,4) COMMENT 'Root Mean Square Error',
    cliente_id      INT UNSIGNED  NULL,
    metadata_json   JSON,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pred_tipo (tipo),
    KEY idx_pred_fecha (fecha_prediccion),
    KEY idx_pred_cliente (cliente_id),
    KEY idx_pred_empresa (empresa_id),
    CONSTRAINT fk_pred_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pred_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: modelos_ia
-- ============================================================
CREATE TABLE IF NOT EXISTS modelos_ia (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id     INT UNSIGNED NOT NULL,
    tipo_modelo    ENUM('ventas', 'churn') NOT NULL,
    algoritmo      VARCHAR(50)  NOT NULL,
    version        VARCHAR(20)  NOT NULL,
    model_path     VARCHAR(255) NOT NULL,
    metricas_json  JSON         NOT NULL,
    precision_drift DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_modelos_empresa (empresa_id),
    KEY idx_modelos_tipo (tipo_modelo),
    CONSTRAINT fk_modelos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: alertas
-- ============================================================
CREATE TABLE IF NOT EXISTS alertas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED NOT NULL,
    tipo            ENUM('caida_ventas','cliente_inactivo','margen_bajo','prediccion_negativa','stock_bajo','meta_peligro') NOT NULL,
    nivel           ENUM('info','warning','danger','success') NOT NULL DEFAULT 'warning',
    titulo          VARCHAR(200) NOT NULL,
    mensaje         TEXT         NOT NULL,
    entidad_tipo    VARCHAR(50)  COMMENT 'cliente, producto, sucursal',
    entidad_id      INT UNSIGNED,
    estado          ENUM('nueva','revisada','resuelta') NOT NULL DEFAULT 'nueva',
    usuario_id      INT UNSIGNED COMMENT 'Quién resolvió',
    resuelta_at     TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alertas_tipo (tipo),
    KEY idx_alertas_nivel (nivel),
    KEY idx_alertas_estado (estado),
    KEY idx_alertas_fecha (created_at),
    KEY idx_alertas_empresa (empresa_id),
    CONSTRAINT fk_alertas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_alertas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: chat_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS chat_logs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    sesion_id       VARCHAR(64)  NOT NULL,
    rol             ENUM('user','assistant') NOT NULL,
    mensaje         TEXT         NOT NULL,
    tokens_usados   INT UNSIGNED,
    modelo_ia       VARCHAR(50)  NOT NULL DEFAULT 'sql_analyzer',
    tiene_grafico   TINYINT(1)   NOT NULL DEFAULT 0,
    grafico_data    JSON,
    sql_generado    TEXT,
    tiempo_resp_ms  INT UNSIGNED,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_usuario (usuario_id),
    KEY idx_chat_sesion (sesion_id),
    KEY idx_chat_fecha (created_at),
    KEY idx_chat_empresa (empresa_id),
    CONSTRAINT fk_chat_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: metricas
-- ============================================================
CREATE TABLE IF NOT EXISTS metricas (
    id              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    empresa_id      INT UNSIGNED   NOT NULL,
    periodo         DATE           NOT NULL COMMENT 'Primer día del período',
    tipo_periodo    ENUM('dia','semana','mes','trimestre','año') NOT NULL DEFAULT 'mes',
    sucursal_id     INT UNSIGNED   NULL,
    ventas_total    DECIMAL(16,2)  NOT NULL DEFAULT 0.00,
    facturas_count  INT UNSIGNED   NOT NULL DEFAULT 0,
    clientes_count  INT UNSIGNED   NOT NULL DEFAULT 0,
    ticket_prom     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    costo_total     DECIMAL(16,2)  NOT NULL DEFAULT 0.00,
    utilidad_total  DECIMAL(16,2)  NOT NULL DEFAULT 0.00,
    margen_prom     DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
    clientes_nuevos INT UNSIGNED   NOT NULL DEFAULT 0,
    clientes_churn  INT UNSIGNED   NOT NULL DEFAULT 0,
    ltv_prom        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    cac_prom        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_metricas_periodo (periodo, tipo_periodo, sucursal_id),
    KEY idx_metricas_periodo (periodo),
    KEY idx_metricas_empresa (empresa_id),
    CONSTRAINT fk_metricas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_metricas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: audit_log
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id  INT UNSIGNED    NOT NULL,
    usuario_id  INT UNSIGNED,
    accion      VARCHAR(100)    NOT NULL,
    tabla       VARCHAR(100),
    registro_id INT UNSIGNED,
    datos_antes JSON,
    datos_desp  JSON,
    ip          VARCHAR(45),
    user_agent  VARCHAR(255),
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_usuario (usuario_id),
    KEY idx_audit_accion (accion),
    KEY idx_audit_fecha (created_at),
    KEY idx_audit_empresa (empresa_id),
    CONSTRAINT fk_audit_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VISTAS ANALÍTICAS
-- ============================================================

-- Vista: resumen ventas por día
CREATE OR REPLACE VIEW v_ventas_diarias AS
SELECT
    v.empresa_id                     AS empresa_id,
    DATE(v.fecha_venta)              AS fecha,
    s.nombre                         AS sucursal,
    COUNT(v.id)                      AS num_ventas,
    SUM(v.total)                     AS total_ventas,
    SUM(v.costo_total)               AS total_costos,
    SUM(v.utilidad)                  AS total_utilidad,
    AVG(v.total)                     AS ticket_promedio,
    COUNT(DISTINCT v.cliente_id)     AS clientes_unicos
FROM ventas v
INNER JOIN sucursales s ON s.id = v.sucursal_id
WHERE v.estado = 'completada'
GROUP BY v.empresa_id, DATE(v.fecha_venta), v.sucursal_id;

-- Vista: ranking productos por utilidad
CREATE OR REPLACE VIEW v_productos_rentables AS
SELECT
    p.empresa_id                       AS empresa_id,
    p.id,
    p.codigo,
    p.nombre,
    p.clasificacion,
    cp.nombre                          AS categoria,
    p.precio_venta,
    p.precio_costo,
    p.margen                           AS margen_pct,
    COALESCE(SUM(dv.cantidad),0)       AS unidades_vendidas,
    COALESCE(SUM(dv.subtotal),0)       AS ingresos_totales,
    COALESCE(SUM(dv.utilidad_linea),0) AS utilidad_total,
    COUNT(DISTINCT dv.venta_id)        AS num_ventas
FROM productos p
LEFT JOIN categorias_producto cp ON cp.id = p.categoria_id
LEFT JOIN detalle_venta dv ON dv.producto_id = p.id
LEFT JOIN ventas v ON v.id = dv.venta_id AND v.estado = 'completada'
WHERE p.activo = 1
GROUP BY p.empresa_id, p.id;

-- Vista: clientes con riesgo churn
CREATE OR REPLACE VIEW v_clientes_churn AS
SELECT
    c.empresa_id                         AS empresa_id,
    c.id,
    c.codigo,
    c.razon_social,
    c.email,
    c.telefono,
    c.churn_score,
    c.churn_riesgo,
    c.ultima_compra,
    c.total_compras,
    c.monto_acumulado,
    c.ticket_promedio,
    DATEDIFF(CURDATE(), c.ultima_compra) AS dias_sin_compra,
    s.nombre                             AS sucursal
FROM clientes c
LEFT JOIN sucursales s ON s.id = c.sucursal_id
WHERE c.activo = 1
ORDER BY c.churn_score DESC;

-- Vista: KPIs mensuales
CREATE OR REPLACE VIEW v_kpis_mensuales AS
SELECT
    v.empresa_id                            AS empresa_id,
    DATE_FORMAT(v.fecha_venta, '%Y-%m-01') AS mes,
    SUM(v.total)                            AS ventas_mes,
    SUM(v.utilidad)                         AS utilidad_mes,
    COUNT(v.id)                             AS num_transacciones,
    COUNT(DISTINCT v.cliente_id)            AS clientes_activos,
    AVG(v.total)                            AS ticket_promedio,
    AVG(v.margen_pct)                       AS margen_promedio
FROM ventas v
WHERE v.estado = 'completada'
GROUP BY v.empresa_id, DATE_FORMAT(v.fecha_venta, '%Y-%m-01')
ORDER BY mes DESC;

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER $$

-- SP: Calcular score churn de un cliente
CREATE PROCEDURE sp_calcular_churn(IN p_empresa_id INT UNSIGNED, IN p_cliente_id INT UNSIGNED)
BEGIN
    DECLARE v_dias_sin_compra INT DEFAULT 999;
    DECLARE v_total_compras   INT DEFAULT 0;
    DECLARE v_ticket_prom     DECIMAL(10,2) DEFAULT 0;
    DECLARE v_score           DECIMAL(5,2) DEFAULT 0;
    DECLARE v_riesgo          VARCHAR(10) DEFAULT 'bajo';

    SELECT
        COALESCE(DATEDIFF(CURDATE(), ultima_compra), 999),
        total_compras,
        ticket_promedio
    INTO v_dias_sin_compra, v_total_compras, v_ticket_prom
    FROM clientes WHERE id = p_cliente_id AND empresa_id = p_empresa_id;

    -- Algoritmo scoring churn
    SET v_score = 0;

    -- Factor tiempo sin compra (max 50 pts)
    IF v_dias_sin_compra <= 30 THEN SET v_score = v_score + 5;
    ELSEIF v_dias_sin_compra <= 60 THEN SET v_score = v_score + 20;
    ELSEIF v_dias_sin_compra <= 90 THEN SET v_score = v_score + 35;
    ELSEIF v_dias_sin_compra <= 180 THEN SET v_score = v_score + 50;
    ELSE SET v_score = v_score + 70;
    END IF;

    -- Factor frecuencia (max 30 pts)
    IF v_total_compras >= 20 THEN SET v_score = v_score + 0;
    ELSEIF v_total_compras >= 10 THEN SET v_score = v_score + 10;
    ELSEIF v_total_compras >= 5 THEN SET v_score = v_score + 20;
    ELSE SET v_score = v_score + 30;
    END IF;

    -- Normalizar a 100
    SET v_score = LEAST(v_score, 100);

    -- Clasificar riesgo
    IF v_score >= 70 THEN SET v_riesgo = 'alto';
    ELSEIF v_score >= 40 THEN SET v_riesgo = 'medio';
    ELSE SET v_riesgo = 'bajo';
    END IF;

    -- Actualizar cliente
    UPDATE clientes
    SET churn_score = v_score, churn_riesgo = v_riesgo
    WHERE id = p_cliente_id AND empresa_id = p_empresa_id;

    SELECT v_score AS score, v_riesgo AS riesgo;
END$$

-- SP: Recalcular todos los churn scores
CREATE PROCEDURE sp_recalcular_todos_churn(IN p_empresa_id INT UNSIGNED)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT UNSIGNED;
    DECLARE cur CURSOR FOR SELECT id FROM clientes WHERE activo = 1 AND empresa_id = p_empresa_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_id;
        IF done THEN LEAVE read_loop; END IF;
        CALL sp_calcular_churn(p_empresa_id, v_id);
    END LOOP;
    CLOSE cur;

    SELECT ROW_COUNT() AS actualizados;
END$$

-- SP: Actualizar totales cliente
CREATE PROCEDURE sp_actualizar_cliente(IN p_empresa_id INT UNSIGNED, IN p_cliente_id INT UNSIGNED)
BEGIN
    UPDATE clientes c
    SET
        total_compras   = (SELECT COUNT(*) FROM ventas WHERE cliente_id = p_cliente_id AND estado = 'completada' AND empresa_id = p_empresa_id),
        monto_acumulado = (SELECT COALESCE(SUM(total), 0) FROM ventas WHERE cliente_id = p_cliente_id AND estado = 'completada' AND empresa_id = p_empresa_id),
        ticket_promedio = (SELECT COALESCE(AVG(total), 0) FROM ventas WHERE cliente_id = p_cliente_id AND estado = 'completada' AND empresa_id = p_empresa_id),
        ultima_compra   = (SELECT MAX(DATE(fecha_venta)) FROM ventas WHERE cliente_id = p_cliente_id AND estado = 'completada' AND empresa_id = p_empresa_id)
    WHERE c.id = p_cliente_id AND c.empresa_id = p_empresa_id;
END$$

-- SP: Generar métricas mensuales
CREATE PROCEDURE sp_generar_metricas_mes(IN p_empresa_id INT UNSIGNED, IN p_anio INT, IN p_mes INT)
BEGIN
    DECLARE v_inicio DATE;
    DECLARE v_fin    DATE;

    SET v_inicio = DATE(CONCAT(p_anio, '-', LPAD(p_mes, 2, '0'), '-01'));
    SET v_fin    = LAST_DAY(v_inicio);

    INSERT INTO metricas (empresa_id, periodo, tipo_periodo, sucursal_id, ventas_total, facturas_count,
        clientes_count, ticket_prom, costo_total, utilidad_total, margen_prom)
    SELECT
        p_empresa_id,
        v_inicio,
        'mes',
        v.sucursal_id,
        SUM(v.total),
        COUNT(v.id),
        COUNT(DISTINCT v.cliente_id),
        AVG(v.total),
        SUM(v.costo_total),
        SUM(v.utilidad),
        AVG(v.margen_pct)
    FROM ventas v
    WHERE DATE(v.fecha_venta) BETWEEN v_inicio AND v_fin
      AND v.estado = 'completada'
      AND v.empresa_id = p_empresa_id
    GROUP BY v.sucursal_id
    ON DUPLICATE KEY UPDATE
        ventas_total   = VALUES(ventas_total),
        facturas_count = VALUES(facturas_count),
        clientes_count = VALUES(clientes_count),
        ticket_prom    = VALUES(ticket_prom),
        costo_total    = VALUES(costo_total),
        utilidad_total = VALUES(utilidad_total),
        margen_prom    = VALUES(margen_prom),
        updated_at     = CURRENT_TIMESTAMP;
END$$

DELIMITER ;

-- ============================================================
-- ÍNDICES ADICIONALES DE PERFORMANCE
-- ============================================================
CREATE INDEX idx_ventas_fecha_total ON ventas (fecha_venta, total, estado);
CREATE INDEX idx_detalle_venta_prod ON detalle_venta (producto_id, venta_id, subtotal);
CREATE INDEX idx_facturas_fecha_cli ON facturas (fecha_emision, cliente_id, estado);
