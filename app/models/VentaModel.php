<?php
/**
 * FAXEL BI — Modelo de Ventas (Analytics) SaaS Multiempresa
 */
class VentaModel extends Model
{
    protected string $table = 'ventas';

    /* ── KPIs Principales ─────────────────────────────────── */

    public function ventasHoy(int $empresaId): float
    {
        return (float) $this->scalar("
            SELECT COALESCE(SUM(total), 0)
            FROM ventas
            WHERE empresa_id = ? AND DATE(fecha_venta) = CURDATE() AND estado = 'completada'
        ", [$empresaId]);
    }

    public function ventasMes(int $empresaId, int $anio = 0, int $mes = 0): float
    {
        $anio = $anio ?: (int)date('Y');
        $mes  = $mes  ?: (int)date('n');
        return (float) $this->scalar("
            SELECT COALESCE(SUM(total), 0)
            FROM ventas
            WHERE empresa_id = ? AND YEAR(fecha_venta) = ? AND MONTH(fecha_venta) = ? AND estado = 'completada'
        ", [$empresaId, $anio, $mes]);
    }

    public function crecimientoMensual(int $empresaId): float
    {
        $mesActual  = $this->ventasMes($empresaId);
        $mesAnterior = $this->ventasMes($empresaId, (int)date('Y', strtotime('-1 month')), (int)date('n', strtotime('-1 month')));

        if ($mesAnterior == 0) return 0;
        return round((($mesActual - $mesAnterior) / $mesAnterior) * 100, 2);
    }

    public function ticketPromedio(int $empresaId, string $desde = '', string $hasta = ''): float
    {
        $params = [$empresaId];
        $where  = "WHERE empresa_id = ? AND estado = 'completada'";
        if ($desde) { $where .= " AND DATE(fecha_venta) >= ?"; $params[] = $desde; }
        if ($hasta) { $where .= " AND DATE(fecha_venta) <= ?"; $params[] = $hasta; }

        return (float) $this->scalar("SELECT COALESCE(AVG(total), 0) FROM ventas $where", $params);
    }

    public function clientesActivos(int $empresaId, int $dias = 30): int
    {
        return (int) $this->scalar("
            SELECT COUNT(DISTINCT cliente_id)
            FROM ventas
            WHERE empresa_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND estado = 'completada'
        ", [$empresaId, $dias]);
    }

    public function facturasMes(int $empresaId): int
    {
        return (int) $this->scalar("
            SELECT COUNT(*) FROM facturas
            WHERE empresa_id = ? AND YEAR(fecha_emision) = YEAR(CURDATE())
              AND MONTH(fecha_emision) = MONTH(CURDATE())
        ", [$empresaId]);
    }

    public function rentabilidadMes(int $empresaId): float
    {
        return (float) $this->scalar("
            SELECT COALESCE(AVG(margen_pct), 0)
            FROM ventas
            WHERE empresa_id = ? AND YEAR(fecha_venta) = YEAR(CURDATE())
              AND MONTH(fecha_venta) = MONTH(CURDATE())
              AND estado = 'completada'
        ", [$empresaId]);
    }

    /* ── Datos para gráficos ──────────────────────────────── */

    public function ventasPorDia(int $empresaId, int $dias = 30, ?int $sucursalId = null): array
    {
        $params = [$empresaId, $dias];
        $where  = "";
        if ($sucursalId) { $where = "AND sucursal_id = ?"; $params[] = $sucursalId; }

        return $this->query("
            SELECT
                DATE(fecha_venta)       AS fecha,
                COALESCE(SUM(total),0)  AS total,
                COUNT(*)                AS num_ventas
            FROM ventas
            WHERE empresa_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND estado = 'completada' $where
            GROUP BY DATE(fecha_venta)
            ORDER BY fecha
        ", $params);
    }

    public function ventasPorSucursal(int $empresaId, string $desde = '', string $hasta = ''): array
    {
        $params = [$empresaId];
        $where  = "WHERE v.empresa_id = ? AND v.estado = 'completada'";
        if ($desde) { $where .= " AND DATE(v.fecha_venta) >= ?"; $params[] = $desde; }
        if ($hasta) { $where .= " AND DATE(v.fecha_venta) <= ?"; $params[] = $hasta; }

        return $this->query("
            SELECT
                s.nombre                AS sucursal,
                COALESCE(SUM(v.total),0) AS total,
                COUNT(v.id)             AS num_ventas,
                COALESCE(AVG(v.margen_pct),0) AS margen
            FROM ventas v
            INNER JOIN sucursales s ON s.id = v.sucursal_id
            $where
            GROUP BY v.sucursal_id
            ORDER BY total DESC
        ", $params);
    }

    public function heatmapHorario(int $empresaId): array
    {
        return $this->query("
            SELECT
                DAYOFWEEK(fecha_venta) - 1 AS dia_semana,
                HOUR(fecha_venta)           AS hora,
                COUNT(*)                    AS num_ventas,
                SUM(total)                  AS total
            FROM ventas
            WHERE empresa_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
              AND estado = 'completada'
            GROUP BY dia_semana, hora
            ORDER BY dia_semana, hora
        ", [$empresaId]);
    }

    public function ventasPorCategoria(int $empresaId, string $desde = '', string $hasta = ''): array
    {
        $params = [$empresaId];
        $where  = "WHERE v.empresa_id = ? AND v.estado = 'completada'";
        if ($desde) { $where .= " AND DATE(v.fecha_venta) >= ?"; $params[] = $desde; }
        if ($hasta) { $where .= " AND DATE(v.fecha_venta) <= ?"; $params[] = $hasta; }

        return $this->query("
            SELECT
                cp.nombre                AS categoria,
                COALESCE(SUM(dv.subtotal),0) AS total
            FROM detalle_venta dv
            INNER JOIN ventas v ON v.id = dv.venta_id
            INNER JOIN productos p ON p.id = dv.producto_id
            INNER JOIN categorias_producto cp ON cp.id = p.categoria_id
            $where
            GROUP BY cp.id
            ORDER BY total DESC
        ", $params);
    }

    public function comparativaMensual(int $empresaId, int $meses = 12): array
    {
        return $this->query("
            SELECT
                DATE_FORMAT(fecha_venta, '%Y-%m') AS mes,
                COALESCE(SUM(total),0)             AS ventas,
                COALESCE(SUM(utilidad),0)          AS utilidad,
                COUNT(*)                            AS transacciones
            FROM ventas
            WHERE empresa_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              AND estado = 'completada'
            GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
            ORDER BY mes
        ", [$empresaId, $meses]);
    }

    public function rankingClientes(int $empresaId, int $limit = 10, string $desde = '', string $hasta = ''): array
    {
        $params = [$empresaId];
        $where  = "WHERE v.empresa_id = ? AND v.estado = 'completada'";
        if ($desde) { $where .= " AND DATE(v.fecha_venta) >= ?"; $params[] = $desde; }
        if ($hasta) { $where .= " AND DATE(v.fecha_venta) <= ?"; $params[] = $hasta; }
        $params[] = $limit;

        return $this->query("
            SELECT
                c.razon_social,
                c.categoria,
                SUM(v.total)   AS total_comprado,
                COUNT(v.id)    AS num_compras,
                AVG(v.total)   AS ticket_prom
            FROM ventas v
            INNER JOIN clientes c ON c.id = v.cliente_id
            $where
            GROUP BY v.cliente_id
            ORDER BY total_comprado DESC
            LIMIT ?
        ", $params);
    }

    /* ── Datos para series temporales (Python Prophet) ────── */

    public function serieTemporalVentas(int $empresaId, int $dias = 365): array
    {
        return $this->query("
            SELECT
                DATE(fecha_venta) AS ds,
                SUM(total)        AS y
            FROM ventas
            WHERE empresa_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND estado = 'completada'
            GROUP BY DATE(fecha_venta)
            ORDER BY ds
        ", [$empresaId, $dias]);
    }

    /* ── Filtros combinados ───────────────────────────────── */

    public function filtrar(int $empresaId, array $filtros): array
    {
        $params = [$empresaId];
        $where  = ["v.empresa_id = ? AND v.estado = 'completada'"];

        if (!empty($filtros['desde']))      { $where[] = "DATE(v.fecha_venta) >= ?"; $params[] = $filtros['desde']; }
        if (!empty($filtros['hasta']))      { $where[] = "DATE(v.fecha_venta) <= ?"; $params[] = $filtros['hasta']; }
        if (!empty($filtros['cliente_id'])) { $where[] = "v.cliente_id = ?";         $params[] = $filtros['cliente_id']; }
        if (!empty($filtros['sucursal_id'])){ $where[] = "v.sucursal_id = ?";        $params[] = $filtros['sucursal_id']; }
        if (!empty($filtros['usuario_id'])) { $where[] = "v.usuario_id = ?";         $params[] = $filtros['usuario_id']; }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $limit    = (int)($filtros['limit'] ?? 50);
        $offset   = (int)($filtros['offset'] ?? 0);

        return $this->query("
            SELECT
                v.id, v.fecha_venta, v.total, v.utilidad, v.margen_pct,
                c.razon_social AS cliente, s.nombre AS sucursal,
                u.nombre AS vendedor
            FROM ventas v
            INNER JOIN clientes   c ON c.id = v.cliente_id
            INNER JOIN sucursales s ON s.id = v.sucursal_id
            INNER JOIN usuarios   u ON u.id = v.usuario_id
            $whereStr
            ORDER BY v.fecha_venta DESC
            LIMIT $limit OFFSET $offset
        ", $params);
    }
}
