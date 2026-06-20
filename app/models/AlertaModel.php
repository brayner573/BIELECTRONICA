<?php
/**
 * FAXEL BI — Modelo de Alertas SaaS Multiempresa
 */
class AlertaModel extends Model
{
    protected string $table = 'alertas';

    public function getActivas(int $empresaId, string $nivel = ''): array
    {
        $params = [$empresaId];
        $where  = "WHERE a.empresa_id = ? AND a.estado != 'resuelta'";
        if ($nivel) { 
            $where .= " AND a.nivel = ?"; 
            $params[] = $nivel; 
        }

        return $this->query("
            SELECT a.*, u.nombre AS resuelto_por
            FROM alertas a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY
                FIELD(a.nivel, 'danger', 'warning', 'info', 'success'),
                a.created_at DESC
        ", $params);
    }

    public function contarPorEstado(int $empresaId): array
    {
        return $this->query("
            SELECT estado, nivel, COUNT(*) AS cantidad
            FROM alertas
            WHERE empresa_id = ?
            GROUP BY estado, nivel
        ", [$empresaId]);
    }

    public function marcarResuelta(int $id, int $empresaId, int $usuarioId): bool
    {
        return $this->execute("
            UPDATE alertas
            SET estado = 'resuelta', usuario_id = ?, resuelta_at = NOW()
            WHERE id = ? AND empresa_id = ?
        ", [$usuarioId, $id, $empresaId]);
    }

    public function marcarRevisada(int $id, int $empresaId): bool
    {
        return $this->execute("
            UPDATE alertas SET estado = 'revisada' WHERE id = ? AND empresa_id = ? AND estado = 'nueva'
        ", [$id, $empresaId]);
    }

    public function crearAlerta(int $empresaId, string $tipo, string $nivel, string $titulo, string $mensaje, string $entidadTipo = '', int $entidadId = 0): int
    {
        return $this->insert([
            'empresa_id'  => $empresaId,
            'tipo'        => $tipo,
            'nivel'       => $nivel,
            'titulo'      => $titulo,
            'mensaje'     => $mensaje,
            'entidad_tipo'=> $entidadTipo ?: null,
            'entidad_id'  => $entidadId ?: null,
        ]);
    }

    /**
     * Motor automático de alertas — debe correr periódicamente por empresa
     */
    public function generarAlertasAutomaticas(int $empresaId): int
    {
        $db      = Database::getInstance();
        $creadas = 0;

        // 1) Caída de ventas > 20% respecto a la semana anterior
        $stmt = $db->prepare("
            SELECT
                SUM(CASE WHEN fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN total ELSE 0 END)  AS semana_actual,
                SUM(CASE WHEN fecha_venta BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                         AND DATE_SUB(CURDATE(), INTERVAL 8 DAY) THEN total ELSE 0 END) AS semana_ant
            FROM ventas WHERE estado = 'completada' AND empresa_id = ?
        ");
        $stmt->execute([$empresaId]);
        $row = $stmt->fetch();
        if ($row && $row['semana_ant'] > 0) {
            $cambio = (($row['semana_actual'] - $row['semana_ant']) / $row['semana_ant']) * 100;
            if ($cambio < -20) {
                $check = $db->prepare("
                    SELECT id FROM alertas
                    WHERE tipo = 'caida_ventas' AND empresa_id = ? AND estado != 'resuelta'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $check->execute([$empresaId]);
                if (!$check->fetch()) {
                    $this->crearAlerta($empresaId, 'caida_ventas', 'danger',
                        'Caída de Ventas Semanal',
                        "Ventas cayeron " . abs(round($cambio, 1)) . "% respecto a la semana anterior."
                    );
                    $creadas++;
                }
            }
        }

        // 2) Clientes inactivos > 30 días con alto valor
        $stmt = $db->prepare("
            SELECT id, razon_social FROM clientes
            WHERE activo = 1 AND empresa_id = ? AND ultima_compra < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              AND monto_acumulado > 20000 AND churn_riesgo = 'alto'
        ");
        $stmt->execute([$empresaId]);
        $inactivos = $stmt->fetchAll();
        foreach ($inactivos as $c) {
            $check = $db->prepare("
                SELECT id FROM alertas
                WHERE tipo = 'cliente_inactivo' AND empresa_id = ? AND entidad_id = ? AND estado != 'resuelta'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
            ");
            $check->execute([$empresaId, $c['id']]);
            if (!$check->fetch()) {
                $this->crearAlerta($empresaId, 'cliente_inactivo', 'warning',
                    "Cliente Inactivo: {$c['razon_social']}",
                    "No ha comprado en más de 30 días. Alto riesgo de abandono.",
                    'cliente', (int)$c['id']
                );
                $creadas++;
            }
        }

        // 3) Stock crítico: productos con stock <= 10
        $stmt = $db->prepare("
            SELECT id, nombre, stock FROM productos
            WHERE activo = 1 AND empresa_id = ? AND stock <= 10
        ");
        $stmt->execute([$empresaId]);
        $criticos = $stmt->fetchAll();
        foreach ($criticos as $prod) {
            $check = $db->prepare("
                SELECT id FROM alertas
                WHERE tipo = 'stock_bajo' AND empresa_id = ? AND entidad_id = ? AND estado != 'resuelta'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ");
            $check->execute([$empresaId, $prod['id']]);
            if (!$check->fetch()) {
                $this->crearAlerta($empresaId, 'stock_bajo', 'warning',
                    "Stock Crítico: {$prod['nombre']}",
                    "El stock de este producto es de {$prod['stock']} unidades. Se recomienda reabastecer.",
                    'producto', (int)$prod['id']
                );
                $creadas++;
            }
        }

        return $creadas;
    }
}
