<?php
$topJson     = json_encode(array_values($topRentables));
$paretoJson  = json_encode(array_values($paretoData));
$paretoAcumJ = json_encode(array_values($paretoAcum));
$abcJson     = json_encode(array_values($distABC));
?>

<!-- ── Resumen ABC ─────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $abcInfo = ['A' => ['color'=>'var(--primary)','label'=>'Alto impacto','desc'=>'Generan ~80% de la utilidad'],
              'B' => ['color'=>'var(--warning)','label'=>'Impacto medio','desc'=>'Generan ~15% de la utilidad'],
              'C' => ['color'=>'var(--text-muted)','label'=>'Bajo impacto','desc'=>'Generan ~5% de la utilidad']];
  foreach ($distABC as $d):
    $info = $abcInfo[$d['clasificacion']] ?? $abcInfo['C'];
  ?>
  <div class="col-md-4 animate-in">
    <div class="kpi-card" style="--card-color:<?= $info['color'] ?>;">
      <div class="kpi-icon" style="color:<?= $info['color'] ?>;font-size:28px;font-weight:900;">
        <?= $d['clasificacion'] ?>
      </div>
      <div class="kpi-label"><?= $info['label'] ?></div>
      <div class="kpi-value" data-counter="<?= $d['cantidad'] ?>"><?= $d['cantidad'] ?></div>
      <div class="kpi-change neutral"><?= $info['desc'] ?></div>
      <div class="text-xs text-muted mt-1">Margen prom: <?= round($d['margen_prom'],1) ?>%</div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Gráficos ───────────────────────────────────────── -->
<div class="row g-4 mb-4">

  <!-- Pareto de utilidad -->
  <div class="col-xl-8 animate-in stagger-1">
    <div class="chart-wrapper">
      <div class="chart-title">📊 Gráfico de Pareto — Utilidad por Producto</div>
      <div class="chart-subtitle">Los primeros productos explican el 80% de la utilidad total</div>
      <canvas id="chartPareto" height="120"></canvas>
    </div>
  </div>

  <!-- Distribución ABC -->
  <div class="col-xl-4 animate-in stagger-2">
    <div class="chart-wrapper">
      <div class="chart-title">🔵 Distribución ABC</div>
      <div class="chart-subtitle">Clasificación por volumen de ventas</div>
      <canvas id="chartABC" height="180"></canvas>
    </div>
  </div>

</div>

<!-- ── Top 20 Productos ───────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-12 animate-in">
    <div class="chart-wrapper">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="chart-title">🏆 Top 20 Productos más Rentables</div>
          <div class="chart-subtitle">Ordenados por utilidad generada en el período</div>
        </div>
        <div class="flex gap-2">
          <a href="<?= $config['url'] ?>/reportes/excel?tipo=rentabilidad" class="btn-bi secondary sm">
            <i class="bi bi-file-earmark-excel"></i> Exportar CSV
          </a>
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table class="table-bi">
          <thead>
            <tr>
              <th>#</th>
              <th>Producto</th>
              <th>Categoría</th>
              <th>Clase ABC</th>
              <th style="text-align:right;">Precio</th>
              <th style="text-align:right;">Margen</th>
              <th style="text-align:right;">Unidades</th>
              <th style="text-align:right;">Ingresos</th>
              <th style="text-align:right;">Utilidad</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topRentables as $i => $p):
              $margenClass = $p['margen'] >= 40 ? 'success' : ($p['margen'] >= 25 ? 'warning' : 'danger');
              $abcClass    = match($p['clasificacion']) { 'A' => 'info', 'B' => 'warning', default => 'purple' };
            ?>
            <tr>
              <td style="color:var(--text-muted);font-weight:700;"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:600;"><?= Security::e($p['nombre']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= Security::e($p['codigo']) ?></div>
              </td>
              <td style="color:var(--text-muted);"><?= Security::e($p['categoria'] ?? '—') ?></td>
              <td><span class="badge-bi <?= $abcClass ?>"><?= $p['clasificacion'] ?></span></td>
              <td style="text-align:right;">S/ <?= number_format($p['precio_venta'],2) ?></td>
              <td style="text-align:right;">
                <span class="badge-bi <?= $margenClass ?>"><?= round($p['margen'],1) ?>%</span>
              </td>
              <td style="text-align:right;"><?= number_format($p['unidades_vendidas'],0) ?></td>
              <td style="text-align:right;color:var(--primary);">S/ <?= number_format($p['ingresos'],0) ?></td>
              <td style="text-align:right;font-weight:700;color:var(--success);">
                S/ <?= number_format($p['utilidad'],0) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── Top Crecimiento ────────────────────────────────── -->
<div class="row g-4">
  <div class="col-xl-6 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title mb-3">📈 Top Crecimiento (vs mes anterior)</div>
      <table class="table-bi">
        <thead>
          <tr>
            <th>Producto</th>
            <th style="text-align:right;">Mes actual</th>
            <th style="text-align:right;">Mes anterior</th>
            <th style="text-align:right;">Δ%</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topCrecimiento as $p):
            $pct = $p['mes_anterior'] > 0
                 ? round((($p['mes_actual'] - $p['mes_anterior']) / $p['mes_anterior']) * 100, 1)
                 : 0;
          ?>
          <tr>
            <td><?= Security::e($p['nombre']) ?></td>
            <td style="text-align:right;">S/ <?= number_format($p['mes_actual'],0) ?></td>
            <td style="text-align:right;color:var(--text-muted);">S/ <?= number_format($p['mes_anterior'],0) ?></td>
            <td style="text-align:right;">
              <span class="badge-bi <?= $pct >= 0 ? 'success' : 'danger' ?>">
                <?= $pct >= 0 ? '+' : '' ?><?= $pct ?>%
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Barras Top Rentabilidad -->
  <div class="col-xl-6 animate-in stagger-1">
    <div class="chart-wrapper">
      <div class="chart-title">💹 Top 10 por Utilidad</div>
      <div class="chart-subtitle">Gráfico de barras comparativo</div>
      <canvas id="chartTopUtilidad" height="150"></canvas>
    </div>
  </div>
</div>

<script>
const rawPareto  = <?= $paretoJson ?>;
const rawAcum    = <?= $paretoAcumJ ?>;
const rawABC     = <?= $abcJson ?>;
const rawTop     = <?= $topJson ?>;

/* Pareto */
new Chart(document.getElementById('chartPareto'), {
  type: 'bar',
  data: {
    labels: rawPareto.map(p => p.nombre.substring(0,22) + (p.nombre.length > 22 ? '…' : '')),
    datasets: [
      {
        label: 'Utilidad S/',
        data: rawPareto.map(p => parseFloat(p.utilidad)),
        backgroundColor: 'rgba(229,62,62,0.8)',
        borderRadius: 4, yAxisID: 'y',
      },
      {
        type: 'line',
        label: '% Acumulado',
        data: rawAcum,
        borderColor: '#F59E0B',
        backgroundColor: 'transparent',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#F59E0B',
        tension: 0.3,
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
      y:  { grid: { color:'rgba(255,255,255,0.04)' }, ticks: { callback: v => 'S/ '+(v/1000).toFixed(0)+'K' } },
      y1: { position: 'right', min: 0, max: 100, grid: { display: false }, ticks: { callback: v => v+'%' } },
      x:  { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } }
    }
  }
});

/* ABC Doughnut */
new Chart(document.getElementById('chartABC'), {
  type: 'doughnut',
  data: {
    labels: rawABC.map(d => `Clase ${d.clasificacion}`),
    datasets: [{
      data: rawABC.map(d => d.cantidad),
      backgroundColor: ['rgba(229,62,62,0.8)','rgba(245,158,11,0.8)','rgba(100,116,139,0.8)'],
      borderColor:     ['#E53E3E','#F59E0B','#64748B'],
      borderWidth: 2, hoverOffset: 6,
    }]
  },
  options: {
    cutout: '65%',
    plugins: { legend: { position: 'bottom' } }
  }
});

/* Top utilidad barras */
const top10 = rawTop.slice(0,10);
new Chart(document.getElementById('chartTopUtilidad'), {
  type: 'bar',
  data: {
    labels: top10.map(p => p.nombre.substring(0,18) + (p.nombre.length > 18 ? '…' : '')),
    datasets: [{
      label: 'Utilidad S/',
      data: top10.map(p => parseFloat(p.utilidad)),
      backgroundColor: top10.map((_, i) => `hsl(${220 + i*12},80%,${60-i*2}%)`),
      borderRadius: 5,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color:'rgba(255,255,255,0.04)' }, ticks: { callback: v => 'S/ '+(v/1000).toFixed(0)+'K' } },
      y: { grid: { display: false }, ticks: { font: { size: 11 } } }
    }
  }
});
</script>
