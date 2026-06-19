<?php
// Contar por riesgo
$countAlto  = count(array_filter($clientes, fn($c) => $c['churn_riesgo'] === 'alto'));
$countMedio = count(array_filter($clientes, fn($c) => $c['churn_riesgo'] === 'medio'));
$countBajo  = count(array_filter($clientes, fn($c) => $c['churn_riesgo'] === 'bajo'));
?>
<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">

<div class="row mb-4 animate-in">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);margin:0;">Riesgo de Abandono (Churn)</h2>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Modelos de Inteligencia Artificial para detectar clientes en riesgo de inactividad.</p>
    </div>
    <a href="<?= $config['url'] ?>/prediccion/entrenamiento" class="btn-bi primary">
      <i class="bi bi-cpu"></i> Panel de Entrenamiento
    </a>
  </div>
</div>

<!-- ── Resumen de riesgo ───────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-md-4 animate-in stagger-1">
    <div class="kpi-card" style="--card-color:var(--danger);">
      <div class="kpi-icon" style="color:var(--danger);"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div class="kpi-label">Alto Riesgo</div>
      <div class="kpi-value" data-counter="<?= $countAlto ?>"><?= $countAlto ?></div>
      <div class="kpi-change down"><i class="bi bi-arrow-down-right"></i> Acción inmediata requerida</div>
    </div>
  </div>
  <div class="col-md-4 animate-in stagger-2">
    <div class="kpi-card" style="--card-color:var(--warning);">
      <div class="kpi-icon" style="color:var(--warning);"><i class="bi bi-exclamation-circle"></i></div>
      <div class="kpi-label">Riesgo Medio</div>
      <div class="kpi-value" data-counter="<?= $countMedio ?>"><?= $countMedio ?></div>
      <div class="kpi-change neutral">Seguimiento recomendado</div>
    </div>
  </div>
  <div class="col-md-4 animate-in stagger-3">
    <div class="kpi-card" style="--card-color:var(--success);">
      <div class="kpi-icon" style="color:var(--success);"><i class="bi bi-check-circle-fill"></i></div>
      <div class="kpi-label">Cliente Estable</div>
      <div class="kpi-value" data-counter="<?= $countBajo ?>"><?= $countBajo ?></div>
      <div class="kpi-change up">Relación sólida</div>
    </div>
  </div>
</div>

<!-- ── Acciones ───────────────────────────────────────── -->
<div class="card-glass p-3 mb-4 animate-in" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
  <span style="font-size:13px;color:var(--text-muted);">Motor IA:</span>
  <button class="btn-bi primary" id="btnRecalcular" onclick="recalcularChurn()">
    <i class="bi bi-cpu"></i> Recalcular Scores IA
  </button>
  <div id="recalcStatus" style="font-size:13px;color:var(--text-muted);"></div>

  <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
    <span class="text-muted text-xs">Filtrar por riesgo:</span>
    <a href="?riesgo=alto"  class="btn-bi danger  sm">🔴 Alto</a>
    <a href="?riesgo=medio" class="btn-bi secondary sm" style="color:var(--warning);border-color:rgba(245,158,11,0.3);">🟡 Medio</a>
    <a href="?riesgo=bajo"  class="btn-bi secondary sm" style="color:var(--success);">🟢 Bajo</a>
    <a href="?"             class="btn-bi secondary sm">Todos</a>
  </div>
</div>

<!-- ── Tabla de clientes ──────────────────────────────── -->
<div class="chart-wrapper animate-in">
  <div class="flex items-center justify-between mb-3">
    <div>
      <div class="chart-title">👥 Análisis de Riesgo de Abandono</div>
      <div class="chart-subtitle">Score 0–100 (mayor = mayor riesgo). Actualizado con IA.</div>
    </div>
    <a href="<?= $config['url'] ?>/reportes/excel?tipo=churn" class="btn-bi secondary sm">
      <i class="bi bi-file-earmark-excel"></i> Exportar
    </a>
  </div>

  <div style="overflow-x:auto;">
    <table class="table-bi">
      <thead>
        <tr>
          <th>Cliente</th>
          <th style="text-align:center;">Score</th>
          <th style="text-align:center;">Riesgo</th>
          <th>Última Compra</th>
          <th style="text-align:right;">Días Inactivo</th>
          <th style="text-align:right;">Total Compras</th>
          <th style="text-align:right;">Acum. S/</th>
          <th>Acción Recomendada</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c):
          $riesgoClass = match($c['churn_riesgo']) {
            'alto'  => 'danger',
            'medio' => 'warning',
            default => 'success',
          };
          $diasColor = (int)$c['dias_sin_compra'] > 90 ? 'var(--danger)' : ((int)$c['dias_sin_compra'] > 60 ? 'var(--warning)' : 'var(--success)');
        ?>
        <tr>
          <td>
            <div style="font-weight:600;"><?= Security::e($c['razon_social']) ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= Security::e($c['email'] ?? '—') ?></div>
          </td>
          <td style="text-align:center;">
            <!-- Score ring visual -->
            <div style="position:relative;width:48px;height:48px;margin:0 auto;">
              <svg width="48" height="48" style="transform:rotate(-90deg)">
                <circle cx="24" cy="24" r="20" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="4"/>
                <circle cx="24" cy="24" r="20" fill="none"
                        stroke="<?= $c['churn_riesgo'] === 'alto' ? '#EF4444' : ($c['churn_riesgo'] === 'medio' ? '#F59E0B' : '#10B981') ?>"
                        stroke-width="4"
                        stroke-dasharray="<?= round($c['churn_score'] * 1.257, 1) ?> 125.7"
                        stroke-linecap="round"/>
              </svg>
              <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text-primary);">
                <?= $c['churn_score'] ?>
              </div>
            </div>
          </td>
          <td style="text-align:center;">
            <span class="badge-bi <?= $riesgoClass ?>">
              <?= ucfirst($c['churn_riesgo']) ?>
            </span>
          </td>
          <td>
            <?= $c['ultima_compra'] ? date('d/m/Y', strtotime($c['ultima_compra'])) : '—' ?>
          </td>
          <td style="text-align:right;font-weight:700;color:<?= $diasColor ?>;">
            <?= $c['dias_sin_compra'] ?? '—' ?> días
          </td>
          <td style="text-align:right;"><?= $c['total_compras'] ?></td>
          <td style="text-align:right;color:var(--primary);">
            S/ <?= number_format($c['monto_acumulado'],0) ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted);">
            <?php
            $score = (int)$c['churn_score'];
            if ($score >= 80)      echo '🔴 Contacto inmediato';
            elseif ($score >= 60)  echo '🟡 Campaña de reactivación';
            elseif ($score >= 40)  echo '🟡 Seguimiento mensual';
            else                   echo '🟢 Mantenimiento estándar';
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function recalcularChurn() {
  const btn    = document.getElementById('btnRecalcular');
  const status = document.getElementById('recalcStatus');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculando...';
  status.textContent = 'Ejecutando modelo IA...';

  try {
    const res = await fetch('<?= $config['url'] ?>/prediccion/ejecutar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ tipo: 'churn', _csrf: getCSRF() }),
    });
    const data = await res.json();

    if (data.success || data.fallback) {
      status.textContent = '✅ Scores actualizados. Recargando...';
      setTimeout(() => location.reload(), 1500);
    } else {
      status.textContent = '⚠️ ' + (data.message || 'Error en cálculo.');
    }
  } catch(e) {
    status.textContent = '❌ Error de conexión con el servidor IA.';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cpu"></i> Recalcular Scores IA';
  }
}
</script>
