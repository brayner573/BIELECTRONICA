<?php
/**
 * FAXEL BI — Modelo de Clientes + Churn Analysis SaaS Multiempresa
 */
class ClienteModel extends Model
{
    protected string $table = 'clientes';

    public function churnRanking(int $empresaId, string $riesgo = '', int $limit = 50): array
    {
        $params = [$empresaId];
        $where  = "WHERE c.empresa_id = ? AND c.activo = 1";
        if ($riesgo) { 
            $where .= " AND c.churn_riesgo = ?"; 
            $params[] = $riesgo; 
        }
        $params[] = $limit;

        return $this->query("
            SELECT
                c.id, c.codigo, c.razon_social, c.email, c.telefono,
                c.churn_score, c.churn_riesgo, c.ultima_compra,
                c.total_compras, c.monto_acumulado, c.ticket_promedio,
                DATEDIFF(CURDATE(), c.ultima_compra) AS dias_sin_compra,
                s.nombre AS sucursal
            FROM clientes c
            LEFT JOIN sucursales s ON s.id = c.sucursal_id
            $where
            ORDER BY c.churn_score DESC
            LIMIT ?
        ", $params);
    }

    public function resumenChurn(int $empresaId): array
    {
        return $this->query("
            SELECT
                churn_riesgo,
                COUNT(*) AS cantidad,
                AVG(churn_score) AS score_prom,
                SUM(monto_acumulado) AS valor_en_riesgo
            FROM clientes
            WHERE activo = 1 AND empresa_id = ?
            GROUP BY churn_riesgo
        ", [$empresaId]);
    }

    public function clientesInactivos(int $empresaId, int $diasSinCompra = 30): array
    {
        return $this->query("
            SELECT
                c.id, c.razon_social, c.email, c.telefono,
                c.ultima_compra, c.churn_score,
                DATEDIFF(CURDATE(), c.ultima_compra) AS dias_inactivo
            FROM clientes c
            WHERE c.activo = 1 AND c.empresa_id = ?
              AND c.ultima_compra < DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY dias_inactivo DESC
            LIMIT 50
        ", [$empresaId, $diasSinCompra]);
    }

    public function getConVentas(int $id, int $empresaId): ?array
    {
        return $this->queryOne("
            SELECT
                c.*,
                s.nombre AS sucursal,
                COUNT(v.id)    AS num_ventas,
                SUM(v.total)   AS total_historico,
                MAX(v.fecha_venta) AS ultima_venta,
                AVG(v.total)   AS ticket_prom_real
            FROM clientes c
            LEFT JOIN sucursales s ON s.id = c.sucursal_id
            LEFT JOIN ventas v     ON v.cliente_id = c.id AND v.estado = 'completada' AND v.empresa_id = ?
            WHERE c.id = ? AND c.empresa_id = ?
            GROUP BY c.id
        ", [$empresaId, $id, $empresaId]);
    }

    public function historialCompras(int $clienteId, int $empresaId, int $meses = 12): array
    {
        return $this->query("
            SELECT
                DATE_FORMAT(fecha_venta, '%Y-%m') AS mes,
                COUNT(*) AS compras,
                SUM(total) AS total
            FROM ventas
            WHERE cliente_id = ? AND empresa_id = ? AND estado = 'completada'
              AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
            ORDER BY mes
        ", [$clienteId, $empresaId, $meses]);
    }

    public function todos(int $empresaId, string $search = ''): array
    {
        $params = [$empresaId];
        $where  = "WHERE c.empresa_id = ? AND c.activo = 1";
        if ($search) {
            $where .= " AND (c.razon_social LIKE ? OR c.ruc_dni LIKE ? OR c.codigo LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s]);
        }

        return $this->query("
            SELECT c.id, c.codigo, c.razon_social, c.ruc_dni, c.email,
                   c.categoria, c.churn_riesgo, c.ultima_compra, s.nombre AS sucursal
            FROM clientes c
            LEFT JOIN sucursales s ON s.id = c.sucursal_id
            $where
            ORDER BY c.razon_social
        ", $params);
    }
}
