<?php
/**
 * FAXEL BI — Controlador de Facturación Electrónica SaaS Multiempresa
 */
class FacturacionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $db = Database::getInstance();
        
        // Obtener historial de facturas con aislamiento
        $stmt = $db->prepare("
            SELECT f.*, c.razon_social AS cliente, u.nombre AS vendedor
            FROM facturas f
            INNER JOIN clientes c ON c.id = f.cliente_id
            INNER JOIN usuarios u ON u.id = f.usuario_id
            WHERE f.empresa_id = ?
            ORDER BY f.fecha_emision DESC, f.id DESC
            LIMIT 100
        ");
        $stmt->execute([$empresaId]);
        $facturas = $stmt->fetchAll();

        $this->view('facturas/index', [
            'title'    => 'Listado de Comprobantes Electrónicos',
            'facturas' => $facturas,
        ]);
    }

    public function crear(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('facturas.create');
        $empresaId = $this->getEmpresaId();

        $db = Database::getInstance();
        
        // Clientes y productos de la empresa
        $stmt = $db->prepare("SELECT id, razon_social, ruc_dni FROM clientes WHERE activo = 1 AND empresa_id = ? ORDER BY razon_social");
        $stmt->execute([$empresaId]);
        $clientes = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT id, codigo, nombre, precio_venta, stock FROM productos WHERE activo = 1 AND empresa_id = ? ORDER BY nombre");
        $stmt->execute([$empresaId]);
        $productos = $stmt->fetchAll();

        // Determinar siguiente correlativo automático
        $stmt = $db->prepare("SELECT COALESCE(MAX(CAST(correlativo AS UNSIGNED)), 0) + 1 AS sig FROM facturas WHERE empresa_id = ? AND serie = 'F001'");
        $stmt->execute([$empresaId]);
        $sigCorrelativo = $stmt->fetchColumn() ?: 1;

        $this->view('facturas/crear', [
            'title'     => 'Emitir Comprobante Electrónico (CPE)',
            'clientes'  => $clientes,
            'productos' => $productos,
            'serie'     => 'F001',
            'correlativo' => $sigCorrelativo,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('facturas.create');
        $this->verifyCSRF();
        $empresaId = $this->getEmpresaId();
        $user      = Session::get('user');

        $clienteId = (int)$this->post('cliente_id', 0);
        $tipoCpe   = $this->post('tipo_cpe', '01'); // 01 = Factura, 03 = Boleta
        $serie     = $tipoCpe === '01' ? 'F001' : 'B001';
        
        $itemsProd = $_POST['items'] ?? []; // Array de ['producto_id' => X, 'cantidad' => Y]
        $errors    = [];

        if ($clienteId <= 0) {
            $errors[] = 'Debe seleccionar un cliente válido.';
        }
        if (empty($itemsProd)) {
            $errors[] = 'Debe agregar al menos un producto al comprobante.';
        }

        $db = Database::getInstance();

        // Obtener datos del cliente
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stmt->execute([$clienteId, $empresaId]);
        $cliente = $stmt->fetch();
        if (!$cliente) {
            $errors[] = 'Cliente no encontrado o no pertenece a la empresa.';
        }

        // Obtener datos de la empresa emisor
        $stmt = $db->prepare("SELECT * FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([$empresaId]);
        $empresa = $stmt->fetch();

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        try {
            $db->beginTransaction();

            // Preparar items del CPE
            $cpeItems = [];
            $totalCosto = 0;
            
            foreach ($itemsProd as $item) {
                $pId  = (int)$item['producto_id'];
                $cant = (float)$item['cantidad'];
                
                $stmt = $db->prepare("SELECT * FROM productos WHERE id = ? AND empresa_id = ? LIMIT 1");
                $stmt->execute([$pId, $empresaId]);
                $prod = $stmt->fetch();
                
                if ($prod) {
                    $subTotal = round($cant * $prod['precio_venta'], 2);
                    $totalCosto += ($cant * $prod['precio_costo']);
                    
                    $cpeItems[] = [
                        'producto_id'     => $prod['id'],
                        'codigo'          => $prod['codigo'],
                        'nombre'          => $prod['nombre'],
                        'cantidad'        => $cant,
                        'precio_unitario' => (float)$prod['precio_venta'],
                        'precio_costo'    => (float)$prod['precio_costo'],
                        'subtotal'        => $subTotal
                    ];
                    
                    // Restar stock del producto
                    $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$cant, $pId]);
                }
            }

            // Calcular siguiente correlativo
            $stmt = $db->prepare("SELECT COALESCE(MAX(CAST(correlativo AS UNSIGNED)), 0) + 1 AS sig FROM facturas WHERE empresa_id = ? AND serie = ?");
            $stmt->execute([$empresaId, $serie]);
            $correlativoNum = $stmt->fetchColumn() ?: 1;
            $correlativoStr = str_pad($correlativoNum, 8, '0', STR_PAD_LEFT);

            // Emitir CPE mediante Servicio de Facturación
            $resultadoFact = FacturacionService::emitir([
                'emisor_ruc'    => $empresa['ruc'],
                'emisor_razon'  => $empresa['razon_social'],
                'tipo_cpe'      => $tipoCpe,
                'serie'         => $serie,
                'correlativo'   => $correlativoNum,
                'cliente_ruc'   => $cliente['ruc_dni'],
                'cliente_razon' => $cliente['razon_social'],
                'cliente_tipo_doc' => $cliente['tipo_doc'] === 'RUC' ? '6' : '1',
                'items'         => $cpeItems
            ]);

            // 1. Insertar en tabla facturas
            $stmt = $db->prepare("
                INSERT INTO facturas (empresa_id, sucursal_id, cliente_id, usuario_id, serie, correlativo, numero_completo, tipo_comp, fecha_emision, subtotal, igv, total, estado, xml_path, cdr_path, hash_cpe)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, 'pagada', ?, ?, ?)
            ");
            $stmt->execute([
                $empresaId,
                $user['sucursal_id'] ?: 1,
                $clienteId,
                $user['id'],
                $serie,
                $correlativoStr,
                $resultadoFact['numero_completo'],
                $tipoCpe,
                $resultadoFact['subtotal'],
                $resultadoFact['igv'],
                $resultadoFact['total'],
                $resultadoFact['xml_path'],
                $resultadoFact['cdr_path'],
                $resultadoFact['hash_cpe']
            ]);
            $facturaId = (int)$db->lastInsertId();

            // 2. Insertar en tabla ventas
            $stmt = $db->prepare("
                INSERT INTO ventas (empresa_id, factura_id, sucursal_id, cliente_id, usuario_id, fecha_venta, subtotal, igv, total, costo_total, estado)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'completada')
            ");
            $stmt->execute([
                $empresaId,
                $facturaId,
                $user['sucursal_id'] ?: 1,
                $clienteId,
                $user['id'],
                $resultadoFact['subtotal'],
                $resultadoFact['igv'],
                $resultadoFact['total'],
                $totalCosto
            ]);
            $ventaId = (int)$db->lastInsertId();

            // 3. Insertar detalle_venta
            $stmtDetalle = $db->prepare("
                INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, costo_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cpeItems as $ci) {
                $stmtDetalle->execute([
                    $ventaId,
                    $ci['producto_id'],
                    $ci['cantidad'],
                    $ci['precio_unitario'],
                    $ci['precio_costo'],
                    $ci['subtotal']
                ]);
            }

            // 4. Actualizar totales del cliente usando procedimiento almacenado
            $db->prepare("CALL sp_actualizar_cliente(?, ?)")->execute([$empresaId, $clienteId]);

            // 5. Recalcular score churn del cliente
            $db->prepare("CALL sp_calcular_churn(?, ?)")->execute([$empresaId, $clienteId]);

            // Auditar la acción
            $db->prepare("INSERT INTO audit_log (empresa_id, usuario_id, accion, tabla, registro_id, datos_desp) VALUES (?, ?, 'EMISION_CPE', 'facturas', ?, ?)")
               ->execute([$empresaId, $user['id'], $facturaId, json_encode(['numero' => $resultadoFact['numero_completo'], 'total' => $resultadoFact['total']])]);

            $db->commit();

            $this->json([
                'success' => true,
                'message' => 'Comprobante Electrónico emitido y aceptado por SUNAT.',
                'numero'  => $resultadoFact['numero_completo'],
                'id'      => $facturaId
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error("Error emitiendo CPE: " . $e->getMessage());
            $this->json(['success' => false, 'errors' => ['Error en transacción: ' . $e->getMessage()]], 500);
        }
    }

    public function pdf(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();
        $id = (int)($params['id'] ?? 0);

        $db = Database::getInstance();
        
        // Obtener factura y detalles
        $stmt = $db->prepare("
            SELECT f.*, c.razon_social AS cliente_razon, c.ruc_dni AS cliente_ruc, c.direccion AS cliente_dir,
                   e.razon_social AS emisor_razon, e.ruc AS emisor_ruc, e.direccion AS emisor_dir, e.logo_path
            FROM facturas f
            INNER JOIN clientes c ON c.id = f.cliente_id
            INNER JOIN empresas e ON e.id = f.empresa_id
            WHERE f.id = ? AND f.empresa_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $empresaId]);
        $factura = $stmt->fetch();

        if (!$factura) {
            http_response_code(404);
            die("Factura no encontrada.");
        }

        // Detalles
        $stmt = $db->prepare("
            SELECT dv.*, p.nombre, p.codigo, p.unidad
            FROM detalle_venta dv
            INNER JOIN ventas v ON v.id = dv.venta_id
            INNER JOIN productos p ON p.id = dv.producto_id
            WHERE v.factura_id = ? AND v.empresa_id = ?
        ");
        $stmt->execute([$id, $empresaId]);
        $detalles = $stmt->fetchAll();

        // Renderizar vista del PDF/impresión HTML premium
        header('Content-Type: text/html; charset=utf-8');
        include dirname(__DIR__) . '/views/facturas/pdf.php';
        exit;
    }

    public function anular(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('facturas.void');
        $this->verifyCSRF();
        $empresaId = $this->getEmpresaId();
        $user      = Session::get('user');

        $id = (int)($params['id'] ?? 0);
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // Verificar propiedad de la factura
            $stmt = $db->prepare("SELECT * FROM facturas WHERE id = ? AND empresa_id = ? LIMIT 1");
            $stmt->execute([$id, $empresaId]);
            $factura = $stmt->fetch();

            if (!$factura || $factura['estado'] === 'anulada') {
                throw new Exception('La factura no existe o ya está anulada.');
            }

            // Anular factura
            $db->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?")->execute([$id]);

            // Anular venta asociada
            $db->prepare("UPDATE ventas SET estado = 'anulada' WHERE factura_id = ?")->execute([$id]);

            // Devolver stock de productos
            $stmtDetalle = $db->prepare("
                SELECT dv.producto_id, dv.cantidad
                FROM detalle_venta dv
                INNER JOIN ventas v ON v.id = dv.venta_id
                WHERE v.factura_id = ?
            ");
            $stmtDetalle->execute([$id]);
            $detalles = $stmtDetalle->fetchAll();

            foreach ($detalles as $det) {
                $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")->execute([$det['cantidad'], $det['producto_id']]);
            }

            // Recalcular totales del cliente
            $db->prepare("CALL sp_actualizar_cliente(?, ?)")->execute([$empresaId, $factura['cliente_id']]);

            // Auditar la acción
            $db->prepare("INSERT INTO audit_log (empresa_id, usuario_id, accion, tabla, registro_id) VALUES (?, ?, 'ANULACION_CPE', 'facturas', ?)")
               ->execute([$empresaId, $user['id'], $id]);

            $db->commit();
            $this->json(['success' => true, 'message' => 'Comprobante anulado y stock actualizado.']);

        } catch (Exception $e) {
            $db->rollBack();
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
