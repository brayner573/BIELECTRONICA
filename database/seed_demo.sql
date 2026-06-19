-- ============================================================
--  FAXEL BI — Datos Demo (Dataset de Ejemplo SaaS Multiempresa)
--  ~500+ registros para pruebas inmediatas en Empresa ID = 1
-- ============================================================

USE faxel_bi;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- EMPRESAS
-- ============================================================
INSERT INTO empresas (id, razon_social, ruc, email, telefono, direccion, sector, empleados_count) VALUES
(1, 'Empresa Demo SAC', '20100000001', 'demo@empresa.com', '01-9999999', 'Av. La República 456, San Isidro, Lima', 'Tecnología', 15);

-- ============================================================
-- SUCURSALES (empresa_id = 1)
-- ============================================================
INSERT INTO sucursales (id, empresa_id, nombre, ciudad, direccion, telefono, email) VALUES
(1, 1, 'Casa Central Lima',   'Lima',       'Av. Javier Prado 1234, San Isidro',      '01-2345678', 'lima@faxel.pe'),
(2, 1, 'Sucursal Miraflores', 'Lima',       'Av. Larco 456, Miraflores',               '01-3456789', 'miraflores@faxel.pe'),
(3, 1, 'Sucursal Arequipa',   'Arequipa',   'Calle Mercaderes 789, Centro',            '054-234567', 'arequipa@faxel.pe'),
(4, 1, 'Sucursal Trujillo',   'Trujillo',   'Jr. Gamarra 321, Centro',                 '044-345678', 'trujillo@faxel.pe');

-- ============================================================
-- USUARIOS (empresa_id = 1)
-- Contraseña para todos: password → hash bcrypt
-- ============================================================
INSERT INTO usuarios (id, empresa_id, sucursal_id, nombre, apellido, email, password_hash, rol) VALUES
(1, 1, 1, 'Carlos',   'Mendoza',    'admin@faxel.pe',    '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'empresa'),
(2, 1, 1, 'Lucía',    'García',     'gerente@faxel.pe',  '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'gerente'),
(3, 1, 1, 'Roberto',  'Torres',     'analista@faxel.pe', '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'analista'),
(4, 1, 2, 'Ana',      'Flores',     'operador@faxel.pe', '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'operador'),
(5, 1, 3, 'Miguel',   'Quispe',     'mquispe@faxel.pe',  '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'operador'),
(6, 1, 4, 'Sandra',   'Vega',       'svega@faxel.pe',    '$2y$12$1D.eX3TwRRL0ZZyXqwxslO1sA01mm0UKA400P7Y6/n7LAPZH/l4B.', 'operador');

-- ============================================================
-- CATEGORÍAS DE PRODUCTO
-- ============================================================
INSERT INTO categorias_producto (id, nombre) VALUES
(1, 'Tecnología'),
(2, 'Electrodomésticos'),
(3, 'Muebles y Hogar'),
(4, 'Ropa y Calzado'),
(5, 'Alimentos'),
(6, 'Servicios'),
(7, 'Materiales');

-- ============================================================
-- PRODUCTOS (50 productos, empresa_id = 1)
-- ============================================================
INSERT INTO productos (id, empresa_id, categoria_id, codigo, nombre, precio_venta, precio_costo, stock, clasificacion) VALUES
(1,  1, 1, 'TECH-001', 'Laptop HP 15s Core i5',          2800.00, 1950.00, 45, 'A'),
(2,  1, 1, 'TECH-002', 'Monitor Samsung 24" Full HD',      650.00,  420.00, 32, 'A'),
(3,  1, 1, 'TECH-003', 'Teclado Mecánico Logitech',        280.00,  160.00, 78, 'B'),
(4,  1, 1, 'TECH-004', 'Mouse Inalámbrico Logitech',        95.00,   52.00, 120,'B'),
(5,  1, 1, 'TECH-005', 'Webcam Full HD 1080p',             180.00,   98.00, 55, 'B'),
(6,  1, 1, 'TECH-006', 'Auriculares Sony WH-1000XM5',     750.00,  480.00, 25, 'A'),
(7,  1, 1, 'TECH-007', 'SSD Kingston 1TB NVMe',           320.00,  195.00, 60, 'A'),
(8,  1, 1, 'TECH-008', 'Router WiFi 6 TP-Link AX3000',    380.00,  230.00, 40, 'B'),
(9,  1, 1, 'TECH-009', 'Impresora Epson EcoTank L3250',   750.00,  490.00, 20, 'B'),
(10, 1, 1, 'TECH-010', 'UPS APC 1000VA',                  450.00,  290.00, 30, 'B'),
(11, 1, 2, 'ELEC-001', 'Refrigeradora LG 340L Inverter', 2200.00, 1550.00, 15, 'A'),
(12, 1, 2, 'ELEC-002', 'Lavadora Whirlpool 12Kg',         1800.00, 1200.00, 12, 'A'),
(13, 1, 2, 'ELEC-003', 'Microondas Electrolux 30L',        580.00,  360.00, 28, 'B'),
(14, 1, 2, 'ELEC-004', 'Licuadora Oster 600W',             220.00,  130.00, 45, 'B'),
(15, 1, 2, 'ELEC-005', 'Aspiradora Electrolux 1600W',      480.00,  300.00, 22, 'B'),
(16, 1, 3, 'MUEB-001', 'Silla de Oficina Ergonómica',      650.00,  380.00, 35, 'B'),
(17, 1, 3, 'MUEB-002', 'Escritorio L 160cm',               890.00,  520.00, 18, 'B'),
(18, 1, 3, 'MUEB-003', 'Estantería Metálica 5 Niveles',    350.00,  195.00, 40, 'C'),
(19, 1, 3, 'MUEB-004', 'Mesa de Reuniones 8 personas',    2500.00, 1600.00,  8, 'A'),
(20, 1, 3, 'MUEB-005', 'Archivador Metálico 4 Cajones',    680.00,  410.00, 25, 'B'),
(21, 1, 4, 'ROPA-001', 'Camiseta Polo Corporativa',         45.00,   22.00, 200,'C'),
(22, 1, 4, 'ROPA-002', 'Mameluco Industrial',               85.00,   48.00, 150,'C'),
(23, 1, 4, 'ROPA-003', 'Zapatos de Seguridad Punta Acero',  180.00,  105.00, 80, 'B'),
(24, 1, 4, 'ROPA-004', 'Chaleco Reflectivo',                 35.00,   18.00, 300,'C'),
(25, 1, 4, 'ROPA-005', 'Guantes de Nitrilo (caja x100)',     28.00,   14.00, 500,'C'),
(26, 1, 5, 'ALIM-001', 'Café Premium 500g',                  38.00,   20.00, 200,'C'),
(27, 1, 5, 'ALIM-002', 'Kit Snacks Corporativo',             65.00,   38.00, 100,'C'),
(28, 1, 6, 'SERV-001', 'Soporte Técnico Mensual',           800.00,  200.00, 999,'A'),
(29, 1, 6, 'SERV-002', 'Capacitación Grupal (4h)',         1200.00,  300.00, 999,'A'),
(30, 1, 6, 'SERV-003', 'Instalación y Configuración',       350.00,   80.00, 999,'B'),
(31, 1, 7, 'MATER-001','Papel A4 (Caja 10 resmas)',          95.00,   58.00, 150,'C'),
(32, 1, 7, 'MATER-002','Tóner HP LaserJet Pro',             180.00,  105.00, 60, 'B'),
(33, 1, 7, 'MATER-003','Cable UTP Cat6 (rollo 100m)',        85.00,   48.00, 40, 'C'),
(34, 1, 7, 'MATER-004','Cinta Adhesiva (pack x12)',          18.00,    8.00, 300,'C'),
(35, 1, 7, 'MATER-005','Folder Manila (paquete x50)',        22.00,   11.00, 250,'C'),
(36, 1, 1, 'TECH-011', 'Tablet Samsung Galaxy A8',          850.00,  560.00, 30, 'A'),
(37, 1, 1, 'TECH-012', 'Disco Duro Externo 2TB',            280.00,  165.00, 50, 'B'),
(38, 1, 1, 'TECH-013', 'Hub USB-C 7 en 1',                  120.00,   65.00, 80, 'B'),
(39, 1, 1, 'TECH-014', 'Cable HDMI 3m 4K',                   28.00,   13.00, 200,'C'),
(40, 1, 1, 'TECH-015', 'Batería Portátil 20000mAh',          85.00,   48.00, 90, 'C');

-- ============================================================
-- CLIENTES (40 clientes, empresa_id = 1)
-- ============================================================
INSERT INTO clientes (id, empresa_id, sucursal_id, codigo, razon_social, ruc_dni, tipo_doc, email, telefono, ciudad, categoria, churn_score, churn_riesgo, ultima_compra, total_compras, monto_acumulado, ticket_promedio) VALUES
(1,  1, 1, 'CLI-0001', 'Corporación Nexus SAC',         '20123456789', 'RUC', 'compras@nexus.pe',      '01-2345670', 'Lima',      'VIP',       12, 'bajo',  '2026-06-10', 45, 185000.00, 4111.11),
(2,  1, 1, 'CLI-0002', 'Inversiones Alfa EIRL',          '20234567890', 'RUC', 'admin@alfa.pe',         '01-3456780', 'Lima',      'VIP',       18, 'bajo',  '2026-06-08', 38, 142000.00, 3736.84),
(3,  1, 1, 'CLI-0003', 'Tech Solutions Perú SAC',        '20345678901', 'RUC', 'ventas@techsol.pe',     '01-4567890', 'Lima',      'frecuente', 25, 'bajo',  '2026-05-28', 30, 98000.00,  3266.67),
(4,  1, 2, 'CLI-0004', 'Distribuidora Condor SA',        '20456789012', 'RUC', 'logistica@condor.pe',   '01-5678901', 'Lima',      'frecuente', 35, 'bajo',  '2026-05-20', 22, 75000.00,  3409.09),
(5,  1, 2, 'CLI-0005', 'Grupo Empresarial Sur EIRL',     '20567890123', 'RUC', 'compras@gesur.pe',      '054-234560', 'Arequipa',  'frecuente', 42, 'medio', '2026-05-05', 18, 58000.00,  3222.22),
(6,  1, 1, 'CLI-0006', 'Minera Andina SRL',              '20678901234', 'RUC', 'admin@minandina.pe',    '01-6789012', 'Lima',      'VIP',       8,  'bajo',  '2026-06-15', 52, 220000.00, 4230.77),
(7,  1, 3, 'CLI-0007', 'Constructora Inca SAC',          '20789012345', 'RUC', 'compras@incacon.pe',    '054-345670', 'Arequipa',  'frecuente', 55, 'medio', '2026-04-20', 15, 42000.00,  2800.00),
(8,  1, 4, 'CLI-0008', 'Agroindustrias Norte SA',        '20890123456', 'RUC', 'logistica@agronorte.pe','044-456780', 'Trujillo',  'regular',   62, 'medio', '2026-04-01', 12, 31000.00,  2583.33),
(9,  1, 1, 'CLI-0009', 'Servicios Globales EIRL',        '20901234567', 'RUC', 'admin@servglobal.pe',   '01-7890123', 'Lima',      'regular',   70, 'alto',  '2026-03-15', 8,  18500.00,  2312.50),
(10, 1, 2, 'CLI-0010', 'Comercial Pacífico SAC',         '21012345678', 'RUC', 'ventas@cpac.pe',        '01-8901234', 'Lima',      'regular',   78, 'alto',  '2026-02-28', 6,  12000.00,  2000.00),
(11, 1, 1, 'CLI-0011', 'Logística Express Perú',         '21123456789', 'RUC', 'compras@logexpress.pe', '01-9012345', 'Lima',      'VIP',       15, 'bajo',  '2026-06-12', 40, 165000.00, 4125.00),
(12, 1, 3, 'CLI-0012', 'Hospital Regional Arequipa',     '20123456780', 'RUC', 'adm@hospar.pe',         '054-567890', 'Arequipa',  'frecuente', 20, 'bajo',  '2026-06-05', 28, 85000.00,  3035.71),
(13, 1, 1, 'CLI-0013', 'Universidad Privada Lima',       '20234567891', 'RUC', 'compras@upl.edu.pe',    '01-2345671', 'Lima',      'frecuente', 30, 'bajo',  '2026-05-25', 25, 72000.00,  2880.00),
(14, 1, 4, 'CLI-0014', 'Pesquera del Norte SAC',         '20345678902', 'RUC', 'admin@pescanorte.pe',   '044-678901', 'Trujillo',  'regular',   45, 'medio', '2026-05-10', 14, 38000.00,  2714.29),
(15, 1, 2, 'CLI-0015', 'Inmobiliaria Horizonte SAC',     '20456789013', 'RUC', 'ventas@horizonte.pe',   '01-3456781', 'Lima',      'regular',   52, 'medio', '2026-04-25', 11, 29500.00,  2681.82),
(16, 1, 1, 'CLI-0016', 'Farmacéutica Andina EIRL',      '20567890124', 'RUC', 'compras@farmand.pe',    '01-4567891', 'Lima',      'VIP',       10, 'bajo',  '2026-06-14', 48, 198000.00, 4125.00),
(17, 1, 3, 'CLI-0017', 'Centro Educativo San Marcos',    '20678901235', 'RUC', 'admin@cesanmarcos.pe',  '054-789012', 'Arequipa',  'frecuente', 38, 'bajo',  '2026-05-18', 20, 55000.00,  2750.00),
(18, 1, 4, 'CLI-0018', 'Textilería Libertad SA',         '20789012346', 'RUC', 'compras@textlib.pe',    '044-890123', 'Trujillo',  'regular',   65, 'medio', '2026-03-28', 10, 26000.00,  2600.00),
(19, 1, 1, 'CLI-0019', 'Seguros Continental SAC',        '20890123457', 'RUC', 'logistica@segcont.pe',  '01-5678902', 'Lima',      'regular',   72, 'alto',  '2026-03-10', 7,  15500.00,  2214.29),
(20, 1, 2, 'CLI-0020', 'Retail Market Perú SA',          '20901234568', 'RUC', 'ventas@retailmkt.pe',   '01-6789013', 'Lima',      'inactivo',  85, 'alto',  '2026-01-20', 5,  9500.00,   1900.00),
(21, 1, 1, 'CLI-0021', 'BancoFinanciero Lima SAC',       '20012345670', 'RUC', 'compras@bfl.pe',        '01-7890124', 'Lima',      'VIP',       5,  'bajo',  '2026-06-16', 55, 245000.00, 4454.55),
(22, 1, 1, 'CLI-0022', 'Supermercados RegionalSA',       '20123456782', 'RUC', 'logistica@superreg.pe', '01-8901235', 'Lima',      'VIP',       7,  'bajo',  '2026-06-13', 50, 210000.00, 4200.00),
(23, 1, 3, 'CLI-0023', 'Metalúrgica Sur EIRL',           '20234567893', 'RUC', 'compras@metasur.pe',    '054-901234', 'Arequipa',  'frecuente', 28, 'bajo',  '2026-05-30', 22, 68000.00,  3090.91),
(24, 1, 4, 'CLI-0024', 'Transporte Veloz SA',            '20345678904', 'RUC', 'admin@transveloz.pe',   '044-012345', 'Trujillo',  'frecuente', 40, 'medio', '2026-05-08', 16, 44000.00,  2750.00),
(25, 1, 2, 'CLI-0025', 'Editorial Cultura Perú',         '20456789015', 'RUC', 'compras@editcult.pe',   '01-2345672', 'Lima',      'regular',   55, 'medio', '2026-04-18', 13, 33500.00,  2576.92),
(26, 1, 1, 'CLI-0026', 'Telefónica Empresas SAC',        '20567890126', 'RUC', 'ventas@telef.pe',       '01-3456782', 'Lima',      'VIP',       11, 'bajo',  '2026-06-11', 42, 175000.00, 4166.67),
(27, 1, 3, 'CLI-0027', 'Clínica Internacional Arequipa', '20678901237', 'RUC', 'admin@clininter.pe',    '054-123456', 'Arequipa',  'frecuente', 22, 'bajo',  '2026-06-03', 26, 80000.00,  3076.92),
(28, 1, 4, 'CLI-0028', 'Cementos Norte SAC',             '20789012348', 'RUC', 'compras@cemnorte.pe',   '044-234567', 'Trujillo',  'regular',   48, 'medio', '2026-04-30', 12, 30500.00,  2541.67),
(29, 1, 1, 'CLI-0029', 'Laboratorio Farmex EIRL',        '20890123459', 'RUC', 'logistica@farmex.pe',   '01-4567892', 'Lima',      'regular',   68, 'medio', '2026-03-20', 9,  20500.00,  2277.78),
(30, 1, 2, 'CLI-0030', 'Importadora Asia Pacífico',      '20901234570', 'RUC', 'compras@asiapac.pe',    '01-5678903', 'Lima',      'inactivo',  88, 'alto',  '2025-12-15', 4,  7800.00,   1950.00),
(31, 1, 1, 'CLI-0031', 'Electro Hogar SAC',              '21234567890', 'RUC', 'ventas@electrohog.pe',  '01-6789014', 'Lima',      'VIP',       9,  'bajo',  '2026-06-14', 44, 182000.00, 4136.36),
(32, 1, 3, 'CLI-0032', 'Mineroductos Sur SA',            '21345678901', 'RUC', 'admin@minersur.pe',     '054-234568', 'Arequipa',  'frecuente', 32, 'bajo',  '2026-05-22', 21, 64000.00,  3047.62),
(33, 1, 4, 'CLI-0033', 'Corporación Delta Trujillo',     '21456789012', 'RUC', 'compras@delta.pe',      '044-345678', 'Trujillo',  'regular',   58, 'medio', '2026-04-10', 11, 28500.00,  2590.91),
(34, 1, 1, 'CLI-0034', 'Grupo Empresarial Lima',         '21567890123', 'RUC', 'logistica@grupelima.pe','01-7890125', 'Lima',      'VIP',       6,  'bajo',  '2026-06-15', 48, 200000.00, 4166.67),
(35, 1, 2, 'CLI-0035', 'Consultora Estratégica SAC',     '21678901234', 'RUC', 'admin@conestrat.pe',    '01-8901236', 'Lima',      'frecuente', 35, 'bajo',  '2026-05-15', 19, 52000.00,  2736.84),
(36, 1, 1, 'CLI-0036', 'Municipalidad Miraflores',       '20123456785', 'RUC', 'compras@munimira.gob.pe','01-2345673','Lima',      'frecuente', 25, 'bajo',  '2026-05-28', 24, 70000.00,  2916.67),
(37, 1, 3, 'CLI-0037', 'Universidad Nacional Arequipa',  '20234567896', 'RUC', 'logistica@unaret.edu.pe','054-456789','Arequipa',  'frecuente', 28, 'bajo',  '2026-05-25', 22, 65000.00,  2954.55),
(38, 1, 4, 'CLI-0038', 'Hacienda Agrícola Norteña',      '20345678907', 'RUC', 'admin@hacnorte.pe',     '044-567890', 'Trujillo',  'regular',   60, 'medio', '2026-04-05', 10, 25000.00,  2500.00),
(39, 1, 1, 'CLI-0039', 'Servicios Ambientales Lima',     '20456789018', 'RUC', 'compras@servamb.pe',    '01-3456783', 'Lima',      'inactivo',  82, 'alto',  '2025-11-30', 5,  10500.00,  2100.00),
(40, 1, 2, 'CLI-0040', 'Centro Comercial Larcomar',      '20567890129', 'RUC', 'ventas@larcomar.pe',    '01-4567893', 'Lima',      'VIP',       4,  'bajo',  '2026-06-16', 60, 280000.00, 4666.67);

-- ============================================================
-- FACTURAS Y VENTAS (script generador masivo para empresa_id = 1)
-- ============================================================

DELIMITER $$
CREATE PROCEDURE gen_demo_data()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE v_fecha DATETIME;
    DECLARE v_cliente INT;
    DECLARE v_sucursal INT;
    DECLARE v_usuario INT;
    DECLARE v_producto1 INT;
    DECLARE v_producto2 INT;
    DECLARE v_cant1 DECIMAL(10,3);
    DECLARE v_cant2 DECIMAL(10,3);
    DECLARE v_precio1 DECIMAL(12,2);
    DECLARE v_precio2 DECIMAL(12,2);
    DECLARE v_costo1 DECIMAL(12,2);
    DECLARE v_costo2 DECIMAL(12,2);
    DECLARE v_subtotal1 DECIMAL(14,2);
    DECLARE v_subtotal2 DECIMAL(14,2);
    DECLARE v_subtotal DECIMAL(14,2);
    DECLARE v_igv DECIMAL(14,2);
    DECLARE v_total DECIMAL(14,2);
    DECLARE v_costo_total DECIMAL(14,2);
    DECLARE v_serie VARCHAR(10);
    DECLARE v_corr VARCHAR(10);
    DECLARE v_factura_id INT;

    WHILE i <= 350 DO
        SET v_fecha = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 395) DAY);
        SET v_fecha = DATE_ADD(v_fecha, INTERVAL FLOOR(RAND() * 8) HOUR);
        SET v_fecha = DATE_ADD(v_fecha, INTERVAL FLOOR(RAND() * 60) MINUTE);

        SET v_cliente   = FLOOR(1 + RAND() * 40);
        SET v_sucursal  = FLOOR(1 + RAND() * 4);
        SET v_usuario   = CASE v_sucursal WHEN 1 THEN FLOOR(1 + RAND() * 2) WHEN 2 THEN 4 WHEN 3 THEN 5 ELSE 6 END;

        SET v_producto1 = FLOOR(1 + RAND() * 40);
        SET v_producto2 = FLOOR(1 + RAND() * 40);
        SET v_cant1     = FLOOR(1 + RAND() * 5);
        SET v_cant2     = FLOOR(1 + RAND() * 3);

        SELECT precio_venta, precio_costo INTO v_precio1, v_costo1 FROM productos WHERE id = v_producto1;
        SELECT precio_venta, precio_costo INTO v_precio2, v_costo2 FROM productos WHERE id = v_producto2;

        SET v_subtotal1   = v_cant1 * v_precio1;
        SET v_subtotal2   = v_cant2 * v_precio2;
        SET v_subtotal    = v_subtotal1 + v_subtotal2;
        SET v_igv         = ROUND(v_subtotal * 0.18, 2);
        SET v_total       = v_subtotal + v_igv;
        SET v_costo_total = (v_cant1 * v_costo1) + (v_cant2 * v_costo2);

        SET v_serie = CASE v_sucursal WHEN 1 THEN 'F001' WHEN 2 THEN 'F002' WHEN 3 THEN 'F003' ELSE 'F004' END;
        SET v_corr  = LPAD(i, 8, '0');

        INSERT INTO facturas (empresa_id, sucursal_id, cliente_id, usuario_id, serie, correlativo, numero_completo, tipo_comp, fecha_emision, moneda, subtotal, igv, total, estado)
        VALUES (1, v_sucursal, v_cliente, v_usuario, v_serie, v_corr, CONCAT(v_serie, '-', v_corr), '01', DATE(v_fecha), 'PEN', v_subtotal, v_igv, v_total, 'pagada');

        SET v_factura_id = LAST_INSERT_ID();

        INSERT INTO ventas (empresa_id, factura_id, sucursal_id, cliente_id, usuario_id, fecha_venta, subtotal, igv, total, costo_total, estado)
        VALUES (1, v_factura_id, v_sucursal, v_cliente, v_usuario, v_fecha, v_subtotal, v_igv, v_total, v_costo_total, 'completada');

        SET @v_venta_id = LAST_INSERT_ID();

        INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, costo_unitario, subtotal)
        VALUES (@v_venta_id, v_producto1, v_cant1, v_precio1, v_costo1, v_subtotal1);

        INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, costo_unitario, subtotal)
        VALUES (@v_venta_id, v_producto2, v_cant2, v_precio2, v_costo2, v_subtotal2);

        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL gen_demo_data();
DROP PROCEDURE gen_demo_data;

-- Actualizar totales de clientes
DELIMITER $$
CREATE PROCEDURE update_all_clients()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT UNSIGNED;
    DECLARE cur CURSOR FOR SELECT id FROM clientes WHERE empresa_id = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_id;
        IF done THEN LEAVE read_loop; END IF;
        CALL sp_actualizar_cliente(1, v_id);
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL update_all_clients();
DROP PROCEDURE update_all_clients;

-- Recalcular scores churn
CALL sp_recalcular_todos_churn(1);

-- ============================================================
-- ALERTAS DE EJEMPLO (empresa_id = 1)
-- ============================================================
INSERT INTO alertas (empresa_id, tipo, nivel, titulo, mensaje, entidad_tipo, entidad_id, estado) VALUES
(1, 'caida_ventas',      'danger',  'Caída de Ventas Detectada',    'Las ventas de la sucursal Trujillo cayeron 23% respecto al mes anterior.',         'sucursal', 4, 'nueva'),
(1, 'cliente_inactivo',  'warning', 'Cliente sin Actividad',        'Retail Market Perú SA no ha comprado en más de 140 días.',                          'cliente',  20,'nueva'),
(1, 'cliente_inactivo',  'warning', 'Cliente en Riesgo Alto',       'Importadora Asia Pacífico tiene score de abandono: 88/100.',                        'cliente',  30,'nueva'),
(1, 'margen_bajo',       'warning', 'Margen Reducido en Producto',  'El margen del producto Camiseta Polo Corporativa bajó por debajo del 45%.',          'producto', 21,'revisada'),
(1, 'prediccion_negativa','danger', 'Predicción Negativa IA',       'El modelo Prophet proyeta caída de 18% en ventas para los próximos 7 días.',        NULL, NULL, 'nueva'),
(1, 'meta_peligro',      'warning', 'Meta del Mes en Riesgo',       'Se ha alcanzado solo el 67% de la meta mensual con 8 días hábiles restantes.',       NULL, NULL, 'nueva'),
(1, 'cliente_inactivo',  'danger',  'Múltiples Clientes en Riesgo', 'Se detectaron 5 clientes VIP con score churn > 70. Requiere acción inmediata.',      NULL, NULL, 'nueva'),
(1, 'stock_bajo',        'warning', 'Stock Crítico',                'Laptop HP 15s tiene solo 45 unidades, se estima agotamiento en 2 semanas.',          'producto', 1, 'nueva');

-- ============================================================
-- PREDICCIONES DE EJEMPLO (empresa_id = 1)
-- ============================================================
INSERT INTO predicciones (empresa_id, tipo, modelo, fecha_prediccion, valor_predicho, limite_inf, limite_sup, exactitud, mae, rmse) VALUES
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 1 DAY),  28500.00, 24000.00, 33000.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 2 DAY),  29200.00, 24500.00, 33900.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 3 DAY),  31000.00, 26000.00, 36000.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 4 DAY),  27800.00, 23000.00, 32600.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 5 DAY),  32500.00, 27500.00, 37500.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 6 DAY),  45000.00, 38000.00, 52000.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_7d',  'prophet', DATE_ADD(CURDATE(), INTERVAL 7 DAY),  22000.00, 18000.00, 26000.00, 87.3, 1250.50, 1680.30),
(1, 'ventas_30d', 'prophet', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 850000.00,720000.00,980000.00, 82.1, 4200.00, 5800.00);

SET FOREIGN_KEY_CHECKS = 1;
