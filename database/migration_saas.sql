-- ============================================================
-- FAXEL BI — Script de Migración SaaS Multiempresa
-- ============================================================

USE faxel_bi;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Crear tabla de empresas
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

-- Insertar empresa por defecto (para datos demo existentes)
INSERT INTO empresas (id, razon_social, ruc, email, telefono, direccion, sector, empleados_count)
VALUES (1, 'Empresa Demo SAC', '20100000001', 'demo@empresa.com', '01-9999999', 'Av. La República 456, San Isidro, Lima', 'Tecnología', 15)
ON DUPLICATE KEY UPDATE id=id;

-- 2. Modificar tablas existentes para incorporar el aislamiento multiempresa
-- Agregar columna empresa_id y su clave foránea

-- SUCURSALES
ALTER TABLE sucursales ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE sucursales ADD CONSTRAINT fk_sucursales_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_sucursales_empresa ON sucursales(empresa_id);

-- USUARIOS
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_usuarios_empresa ON usuarios(empresa_id);
-- Modificar enum de roles para admitir nuevos roles
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('superadmin', 'empresa', 'gerente', 'analista', 'vendedor', 'operador') NOT NULL DEFAULT 'operador';

-- CLIENTES
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE clientes ADD CONSTRAINT fk_clientes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_clientes_empresa ON clientes(empresa_id);

-- PRODUCTOS
ALTER TABLE productos ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE productos ADD CONSTRAINT fk_productos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_productos_empresa ON productos(empresa_id);

-- FACTURAS
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE facturas ADD CONSTRAINT fk_facturas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_facturas_empresa ON facturas(empresa_id);

-- VENTAS
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_ventas_empresa ON ventas(empresa_id);

-- PREDICCIONES
ALTER TABLE predicciones ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE predicciones ADD CONSTRAINT fk_predicciones_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_predicciones_empresa ON predicciones(empresa_id);

-- ALERTAS
ALTER TABLE alertas ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE alertas ADD CONSTRAINT fk_alertas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_alertas_empresa ON alertas(empresa_id);

-- CHAT LOGS
ALTER TABLE chat_logs ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE chat_logs ADD CONSTRAINT fk_chat_logs_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_chat_logs_empresa ON chat_logs(empresa_id);

-- METRICAS
ALTER TABLE metricas ADD COLUMN IF NOT EXISTS empresa_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE metricas ADD CONSTRAINT fk_metricas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS idx_metricas_empresa ON metricas(empresa_id);

-- 3. Crear tabla modelos_ia para registrar entrenamiento versionado
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

-- 4. Modificar Vistas para incluir empresa_id

-- Vista: v_ventas_diarias
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

-- Vista: v_productos_rentables
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

-- Vista: v_clientes_churn
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

-- Vista: v_kpis_mensuales
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

-- 5. Modificar Procedimientos Almacenados para Aislamiento

DELIMITER $$

-- SP: sp_calcular_churn con empresa_id
DROP PROCEDURE IF EXISTS sp_calcular_churn$$
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

-- SP: sp_recalcular_todos_churn con empresa_id
DROP PROCEDURE IF EXISTS sp_recalcular_todos_churn$$
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

-- SP: sp_actualizar_cliente con empresa_id
DROP PROCEDURE IF EXISTS sp_actualizar_cliente$$
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

-- SP: sp_generar_metricas_mes con empresa_id
DROP PROCEDURE IF EXISTS sp_generar_metricas_mes$$
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

SET FOREIGN_KEY_CHECKS = 1;
