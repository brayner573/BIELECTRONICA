<?php
/**
 * FAXEL BI — Modelo de Productos (Rentabilidad + ABC) SaaS Multiempresa
 */
class ProductoModel extends Model
{
    protected string $table = 'productos';

    public function topRentables(int $empresaId, int $limit = 20): array
    {
        return $this->query("
            SELECT
                p.id, p.codigo, p.nombre, p.clasificacion, p.margen,
                cp.nombre AS categoria,
                COALESCE(SUM(dv.cantidad), 0)       AS unidades_vendidas,
                COALESCE(SUM(dv.subtotal), 0)       AS ingresos,
                COALESCE(SUM(dv.utilidad_linea), 0) AS utilidad,
                p.precio_venta, p.precio_costo
            FROM productos p
            LEFT JOIN categorias_producto cp ON cp.id = p.categoria_id
            LEFT JOIN detalle_venta dv       ON dv.producto_id = p.id
            LEFT JOIN ventas v               ON v.id = dv.venta_id AND v.estado = 'completada' AND v.empresa_id = ?
            WHERE p.activo = 1 AND p.empresa_id = ?
            GROUP BY p.id
            ORDER BY utilidad DESC
            LIMIT ?
        ", [$empresaId, $empresaId, $limit]);
    }

    public function topCrecimiento(int $empresaId, int $limit = 10): array
    {
        return $this->query("
            SELECT id, nombre, mes_actual, mes_anterior
            FROM (
                SELECT
                    p.id, p.nombre,
                    SUM(CASE WHEN DATE(v.fecha_venta) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                              THEN dv.subtotal ELSE 0 END) AS mes_actual,
                    SUM(CASE WHEN DATE(v.fecha_venta) BETWEEN
                              DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                              AND DATE_SUB(CURDATE(), INTERVAL 31 DAY)
                              THEN dv.subtotal ELSE 0 END) AS mes_anterior
                FROM productos p
                LEFT JOIN detalle_venta dv ON dv.producto_id = p.id
                LEFT JOIN ventas v         ON v.id = dv.venta_id AND v.estado = 'completada' AND v.empresa_id = ?
                WHERE p.activo = 1 AND p.empresa_id = ?
                GROUP BY p.id
            ) AS t
            WHERE mes_anterior > 0
            ORDER BY ((mes_actual - mes_anterior) / mes_anterior) DESC
            LIMIT ?
        ", [$empresaId, $empresaId, $limit]);
    }

    public function distribucionABC(int $empresaId): array
    {
        return $this->query("
            SELECT
                clasificacion,
                COUNT(*) AS cantidad,
                AVG(margen) AS margen_prom
            FROM productos
            WHERE activo = 1 AND empresa_id = ?
            GROUP BY clasificacion
        ", [$empresaId]);
    }

    public function actualizarClasificacionABC(int $empresaId): void
    {
        // Calcular ventas totales por producto para la empresa
        $productos = $this->query("
            SELECT p.id, COALESCE(SUM(dv.subtotal), 0) AS ventas_total
            FROM productos p
            LEFT JOIN detalle_venta dv ON dv.producto_id = p.id
            LEFT JOIN ventas v         ON v.id = dv.venta_id AND v.estado = 'completada' AND v.empresa_id = ?
            WHERE p.activo = 1 AND p.empresa_id = ?
            GROUP BY p.id
            ORDER BY ventas_total DESC
        ", [$empresaId, $empresaId]);

        $totalVentas = array_sum(array_column($productos, 'ventas_total'));
        $acumulado   = 0;

        foreach ($productos as $prod) {
            $acumulado += $prod['ventas_total'];
            $pct = $totalVentas > 0 ? ($acumulado / $totalVentas) * 100 : 0;
            $clase = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');

            $this->execute("UPDATE productos SET clasificacion = ? WHERE id = ? AND empresa_id = ?", [$clase, $prod['id'], $empresaId]);
        }
    }

    public function paretoData(int $empresaId): array
    {
        return $this->query("
            SELECT
                p.nombre,
                COALESCE(SUM(dv.utilidad_linea), 0) AS utilidad,
                COALESCE(SUM(dv.subtotal), 0)        AS ingresos
            FROM productos p
            LEFT JOIN detalle_venta dv ON dv.producto_id = p.id
            LEFT JOIN ventas v         ON v.id = dv.venta_id AND v.estado = 'completada' AND v.empresa_id = ?
            WHERE p.activo = 1 AND p.empresa_id = ?
            GROUP BY p.id
            ORDER BY utilidad DESC
            LIMIT 20
        ", [$empresaId, $empresaId]);
    }
}
