<?php
// ── Preparar datos para Charts ──────────────────────────────
$ventasDiarias    = json_encode(array_values($charts['ventas_diarias']));
$porSucursal      = json_encode(array_values($charts['por_sucursal']));
$porCategoria     = json_encode(array_values($charts['por_categoria']));
$comparativa      = json_encode(array_values($charts['comparativa']));
$rankingClientes  = json_encode(array_values($charts['ranking_clientes']));
$heatmapData      = json_encode(array_values($charts['heatmap_horario']));

$crecimiento   = $kpis['crecimiento'];
$crec_dir      = $crecimiento >= 0 ? 'up' : 'down';
$crec_icon     = $crecimiento >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
$meta_color    = $kpis['meta_pct'] >= 80 ? 'var(--success)' : ($kpis['meta_pct'] >= 60 ? 'var(--warning)' : 'var(--danger)');

// Contar alertas por nivel
$alertasByNivel = [];
foreach ($alertaStats as $s) {
  $alertasByNivel[$s['nivel']] = ($alertasByNivel[$s['nivel']] ?? 0) + (int)$s['cantidad'];
}
?>

<!-- ══ META CSRF ══════════════════════════════════════════ -->

<!-- ══ FILTROS ════════════════════════════════════════════ -->
<div class="card-glass p-4 mb-4 animate-in" style="border-color: var(--border-accent);">
  <div class="flex items-center gap-4 flex-wrap">
    <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
      <i class="bi bi-calendar3" style="color:var(--primary);"></i>
      <label class="form-label-bi mb-0">Desde</label>
      <input type="date" id="f_desde" class="input-bi" value="<?= Security::e($desde) ?>" style="max-width:160px;">
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
      <label class="form-label-bi mb-0">Hasta</label>
      <input type="date" id="f_hasta" class="input-bi" value="<?= Security::e($hasta) ?>" style="max-width:160px;">
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <select id="f_sucursal" class="input-bi" style="max-width:180px;">
        <option value="">Todas las sucursales</option>
        <?php foreach ($sucursales as $s): ?>
        <option value="<?= $s['id'] ?>"><?= Security::e($s['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn-bi primary" onclick="aplicarFiltros()">
      <i class="bi bi-funnel"></i> Aplicar Filtros
    </button>
    <button class="btn-bi secondary" onclick="resetFiltros()">
      <i class="bi bi-x-circle"></i> Limpiar
    </button>
  </div>
</div>

<!-- ══ KPI CARDS (10 indicadores) ════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Ventas Hoy -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-1">
    <div class="kpi-card" style="--card-color: var(--primary);">
      <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
      <div class="kpi-label">Ventas Hoy</div>
      <div class="kpi-value"
           data-counter="<?= $kpis['ventas_hoy'] ?>"
           data-prefix="S/ "
           data-decimals="0">S/ 0</div>
      <div class="kpi-change neutral">
        <i class="bi bi-clock"></i> Tiempo real
      </div>
    </div>
  </div>

  <!-- Ventas del Mes -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-2">
    <div class="kpi-card" style="--card-color: var(--secondary);">
      <div class="kpi-icon" style="color:var(--secondary);"><i class="bi bi-calendar-month"></i></div>
      <div class="kpi-label">Ventas del Mes</div>
      <div class="kpi-value"
           data-counter="<?= $kpis['ventas_mes'] ?>"
           data-prefix="S/ "
           data-decimals="0">S/ 0</div>
      <div class="kpi-change <?= $crec_dir ?>">
        <i class="bi <?= $crec_icon ?>"></i>
        <?= abs($crecimiento) ?>% vs mes anterior
      </div>
    </div>
  </div>

  <!-- Crecimiento -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-3">
    <div class="kpi-card" style="--card-color: <?= $crecimiento >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
      <div class="kpi-icon" style="color:<?= $crecimiento >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"><i class="bi bi-trending-up"></i></div>
      <div class="kpi-label">Crecimiento MoM</div>
      <div class="kpi-value <?= $crecimiento >= 0 ? 'text-success' : 'text-danger' ?>"
           data-counter="<?= abs($crecimiento) ?>"
           data-suffix="<?= $crecimiento >= 0 ? '+' : '-' ?>%"
           data-decimals="1">0%</div>
      <div class="kpi-change <?= $crec_dir ?>">
        Vs. mes anterior
      </div>
    </div>
  </div>

  <!-- Ticket Promedio -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-4">
    <div class="kpi-card" style="--card-color: var(--accent);">
      <div class="kpi-icon" style="color:var(--accent);"><i class="bi bi-receipt"></i></div>
      <div class="kpi-label">Ticket Promedio</div>
      <div class="kpi-value"
           data-counter="<?= $kpis['ticket_promedio'] ?>"
           data-prefix="S/ "
           data-decimals="0">S/ 0</div>
      <div class="kpi-change neutral">Por transacción</div>
    </div>
  </div>

  <!-- Clientes Activos -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-5">
    <div class="kpi-card" style="--card-color: #F59E0B;">
      <div class="kpi-icon" style="color:#F59E0B;"><i class="bi bi-people"></i></div>
      <div class="kpi-label">Clientes Activos</div>
      <div class="kpi-value"
           data-counter="<?= $kpis['clientes_activos'] ?>"
           data-decimals="0">0</div>
      <div class="kpi-change neutral">Últimos 30 días</div>
    </div>
  </div>

  <!-- Facturas -->
  <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 animate-in stagger-6">
    <div class="kpi-card" style="--card-color: #10B981;">
      <div class="kpi-icon" style="color:#10B981;"><i class="bi bi-file-earmark-check"></i></div>
      <div class="kpi-label">Facturas del Mes</div>
      <div class="kpi-value"
           data-counter="<?= $kpis['facturas_mes'] ?>"
           data-decimals="0">0</div>
      <div class="kpi-change up">Comprobantes emitidos</div>
    </div>
  </div>

</div>

<!-- Segunda fila de KPIs -->
<div class="row g-3 mb-4">
  <!-- Rentabilidad -->
  <div class="col-xl-3 col-md-6 animate-in stagger-1">
    <div class="kpi-card" style="--card-color: #8B5CF6;">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="kpi-label">Rentabilidad</div>
          <div class="kpi-value text-gradient"
               data-counter="<?= $kpis['rentabilidad'] ?>"
               data-suffix="%"
               data-decimals="1">0%</div>
        </div>
        <div class="kpi-icon" style="color:#8B5CF6;width:60px;height:60px;font-size:28px;">
          <i class="bi bi-gem"></i>
        </div>
      </div>
      <div class="text-muted text-xs">Margen promedio del período</div>
    </div>
  </div>

  <!-- Meta vs Resultado -->
  <div class="col-xl-5 col-md-6 animate-in stagger-2">
    <div class="kpi-card" style="--card-color: <?= $meta_color ?>;">
      <div class="flex items-center justify-between mb-2">
        <div style="flex:1;">
          <div class="kpi-label" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding-right:10px;">
            <span>Meta vs Resultado</span>
            <button class="btn-bi secondary sm" onclick="abrirModalMetas()" style="padding: 2px 8px; font-size:11px; background:rgba(0,0,0,0.05); border-color:var(--border);">
              <i class="bi bi-gear"></i> Metas
            </button>
          </div>
          <div class="kpi-value" style="color:<?= $meta_color ?>;margin-top:6px;" data-counter="<?= $kpis['meta_pct'] ?>" data-suffix="%" data-decimals="1">0%</div>
        </div>
        <div>
          <div class="text-xs text-muted">Vendido</div>
          <div style="font-size:15px;font-weight:700;color:var(--text-primary);">S/ <?= number_format($kpis['ventas_mes'],0) ?></div>
          <div class="text-xs text-muted">de S/ <?= number_format($kpis['meta_mes'],0) ?></div>
        </div>
      </div>
      <div class="progress-bi">
        <div class="progress-fill" style="width:<?= min($kpis['meta_pct'],100) ?>%;background:linear-gradient(90deg,<?= $meta_color ?>,<?= $meta_color ?>99);"></div>
      </div>
      <div class="flex justify-between mt-1 text-xs text-muted">
        <span>0%</span>
        <span>Meta: 100%</span>
      </div>
    </div>
  </div>

  <!-- Alertas rápido -->
  <div class="col-xl-4 animate-in stagger-3">
    <div class="kpi-card" style="--card-color: var(--danger);">
      <div class="kpi-label mb-3">Estado de Alertas</div>
      <div class="row g-2">
        <div class="col-6">
          <div style="background:rgba(239,68,68,0.1);border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:var(--danger);"><?= $alertasByNivel['danger'] ?? 0 ?></div>
            <div class="text-xs text-muted">Críticas</div>
          </div>
        </div>
        <div class="col-6">
          <div style="background:rgba(245,158,11,0.1);border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:var(--warning);"><?= $alertasByNivel['warning'] ?? 0 ?></div>
            <div class="text-xs text-muted">Advertencias</div>
          </div>
        </div>
      </div>
      <a href="<?= $config['url'] ?>/alertas" class="btn-bi danger sm w-full mt-3" style="justify-content:center;">
        <i class="bi bi-bell"></i> Ver todas las alertas
      </a>
    </div>
  </div>
</div>

<!-- ══ GRÁFICOS PRINCIPALES ══════════════════════════════ -->
<div class="row g-4 mb-4">

  <!-- Línea de ventas (30 días) -->
  <div class="col-xl-8 animate-in stagger-1">
    <div class="chart-wrapper">
      <div class="flex items-center justify-between mb-2">
        <div>
          <div class="chart-title">📈 Evolución de Ventas</div>
          <div class="chart-subtitle">Últimos 30 días — Total diario y tendencia</div>
        </div>
        <div class="flex gap-2">
          <button class="btn-bi secondary sm" onclick="changeChartPeriod(30)">30d</button>
          <button class="btn-bi secondary sm" onclick="changeChartPeriod(7)">7d</button>
        </div>
      </div>
      <canvas id="chartVentasDiarias" height="100"></canvas>
    </div>
  </div>

  <!-- Pie por categoría -->
  <div class="col-xl-4 animate-in stagger-2">
    <div class="chart-wrapper">
      <div class="chart-title">🏷️ Ventas por Categoría</div>
      <div class="chart-subtitle">Distribución porcentual del período</div>
      <canvas id="chartCategoria" height="160"></canvas>
    </div>
  </div>

</div>

<div class="row g-4 mb-4">

  <!-- Barras por sucursal -->
  <div class="col-xl-5 animate-in stagger-1">
    <div class="chart-wrapper">
      <div class="chart-title">🏢 Rendimiento por Sucursal</div>
      <div class="chart-subtitle">Total de ventas y margen por sede</div>
      <canvas id="chartSucursal" height="140"></canvas>
    </div>
  </div>

  <!-- Comparativa mensual -->
  <div class="col-xl-7 animate-in stagger-2">
    <div class="chart-wrapper">
      <div class="chart-title">📊 Comparativa Mensual</div>
      <div class="chart-subtitle">Ventas vs Utilidad — últimos 6 meses</div>
      <canvas id="chartComparativa" height="140"></canvas>
    </div>
  </div>

</div>

<!-- ══ TABLAS: Rankings ═══════════════════════════════════ -->
<div class="row g-4 mb-4">

  <!-- Ranking de clientes -->
  <div class="col-xl-7 animate-in">
    <div class="chart-wrapper">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="chart-title">🏆 Top 10 Clientes</div>
          <div class="chart-subtitle">Por volumen de compras en el período</div>
        </div>
        <a href="<?= $config['url'] ?>/prediccion/churn" class="btn-bi secondary sm">
          <i class="bi bi-person-lines-fill"></i> Ver churn
        </a>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-bi">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Categoría</th>
              <th style="text-align:right;">Total</th>
              <th style="text-align:right;">Compras</th>
              <th style="text-align:right;">Ticket</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($charts['ranking_clientes'] as $i => $c): ?>
            <tr>
              <td style="color:var(--text-muted);font-weight:700;"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:600;"><?= Security::e($c['razon_social']) ?></div>
              </td>
              <td>
                <?php
                  $catColor = match($c['categoria'] ?? 'regular') {
                    'VIP'      => 'purple',
                    'frecuente'=> 'info',
                    'nuevo'    => 'success',
                    'inactivo' => 'danger',
                    default    => 'warning',
                  };
                ?>
                <span class="badge-bi <?= $catColor ?>"><?= ucfirst($c['categoria'] ?? 'regular') ?></span>
              </td>
              <td style="text-align:right;font-weight:700;color:var(--primary);">
                S/ <?= number_format($c['total_comprado'],0) ?>
              </td>
              <td style="text-align:right;"><?= $c['num_compras'] ?></td>
              <td style="text-align:right;color:var(--text-muted);">
                S/ <?= number_format($c['ticket_prom'],0) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Alertas recientes -->
  <div class="col-xl-5 animate-in">
    <div class="chart-wrapper">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="chart-title">🔔 Alertas Recientes</div>
          <div class="chart-subtitle">Últimas notificaciones del sistema</div>
        </div>
        <a href="<?= $config['url'] ?>/alertas" class="btn-bi secondary sm">Ver todas</a>
      </div>
      <div style="max-height:300px;overflow-y:auto;">
        <?php foreach (array_slice($alertas, 0, 6) as $alerta): ?>
        <div class="alert-card <?= Security::e($alerta['nivel']) ?>">
          <div class="alert-card-icon">
            <?php
            echo match($alerta['nivel']) {
              'danger'  => '🔴',
              'warning' => '🟡',
              'success' => '🟢',
              default   => 'ℹ️',
            };
            ?>
          </div>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= Security::e($alerta['titulo']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;"><?= Security::e(mb_substr($alerta['mensaje'],0,80)) ?>...</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
              <?= date('d/m H:i', strtotime($alerta['created_at'])) ?>
              <span class="badge-bi <?= $alerta['estado'] === 'nueva' ? 'danger' : 'info' ?>" style="margin-left:8px;"><?= ucfirst($alerta['estado']) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($alertas)): ?>
          <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="bi bi-check-circle" style="font-size:32px;color:var(--success);"></i>
            <p class="mt-2">Sin alertas pendientes</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ══ HEATMAP HORARIO ════════════════════════════════════ -->
<div class="row g-4 mb-4">
  <div class="col-12 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title">🕐 Heatmap Horario de Ventas</div>
      <div class="chart-subtitle">Distribución de actividad por día y hora — últimos 90 días</div>
      <div id="heatmapContainer" style="padding-top:16px;"></div>
    </div>
  </div>
</div>

<!-- ══ SCRIPTS DE GRÁFICOS ════════════════════════════════ -->
<script>
const COLORS = {
  primary:  '#E53E3E',
  secondary:'#9B2C2C',
  accent:   '#C53030',
  success:  '#38A169',
  warning:  '#DD6B20',
  danger:   '#E53E3E',
  pink:     '#EC4899',
  teal:     '#14B8A6',
};

const PALETTE = Object.values(COLORS);

/* ── Datos desde PHP ───────────────────────────────────── */
const rawDiarias   = <?= $ventasDiarias ?>;
const rawSucursal  = <?= $porSucursal ?>;
const rawCategoria = <?= $porCategoria ?>;
const rawCompar    = <?= $comparativa ?>;
const rawRanking   = <?= $rankingClientes ?>;
const rawHeatmap   = <?= $heatmapData ?>;

/* ── Gráfico 1: Ventas Diarias (Línea) ─────────────────── */
const ctxLine = document.getElementById('chartVentasDiarias').getContext('2d');
const gradLine = ctxLine.createLinearGradient(0,0,0,300);
gradLine.addColorStop(0, 'rgba(229,62,62,0.3)');
gradLine.addColorStop(1, 'rgba(229,62,62,0)');

const chartVentas = new Chart(ctxLine, {
  type: 'line',
  data: {
    labels: rawDiarias.map(d => {
      const f = new Date(d.fecha + 'T00:00:00');
      return f.toLocaleDateString('es-PE', {day:'2-digit',month:'short'});
    }),
    datasets: [{
      label: 'Ventas S/',
      data: rawDiarias.map(d => parseFloat(d.total)),
      borderColor: COLORS.primary,
      backgroundColor: gradLine,
      borderWidth: 2.5,
      pointRadius: 4,
      pointBackgroundColor: COLORS.primary,
      pointBorderColor: '#0A0F1E',
      pointBorderWidth: 2,
      fill: true,
      tension: 0.4,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ' S/ ' + ctx.raw.toLocaleString('es-PE', {minimumFractionDigits:2})
        }
      }
    },
    scales: {
      x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
      y: {
        grid: { color: 'rgba(255,255,255,0.04)' },
        ticks: { callback: v => 'S/ ' + (v/1000).toFixed(0) + 'K' }
      }
    }
  }
});

function changeChartPeriod(dias) {
  fetch(`<?= $config['url'] ?>/dashboard/charts?tipo=ventas_diarias`)
    .then(r => r.json())
    .then(res => {
      const data = res.data.slice(-dias);
      chartVentas.data.labels   = data.map(d => new Date(d.fecha+'T00:00:00').toLocaleDateString('es-PE',{day:'2-digit',month:'short'}));
      chartVentas.data.datasets[0].data = data.map(d => parseFloat(d.total));
      chartVentas.update();
    });
}

/* ── Gráfico 2: Categorías (Doughnut) ──────────────────── */
new Chart(document.getElementById('chartCategoria'), {
  type: 'doughnut',
  data: {
    labels: rawCategoria.map(d => d.categoria),
    datasets: [{
      data: rawCategoria.map(d => parseFloat(d.total)),
      backgroundColor: PALETTE.map(c => c + 'CC'),
      borderColor: PALETTE,
      borderWidth: 2,
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true,
    cutout: '68%',
    plugins: {
      legend: { position: 'bottom', labels: { padding:16, font:{size:11} } },
      tooltip: {
        callbacks: {
          label: ctx => ` S/ ${ctx.raw.toLocaleString('es-PE', {minimumFractionDigits:0})}`
        }
      }
    }
  }
});

/* ── Gráfico 3: Sucursales (Barras Horizontales) ────────── */
new Chart(document.getElementById('chartSucursal'), {
  type: 'bar',
  data: {
    labels: rawSucursal.map(d => d.sucursal),
    datasets: [
      {
        label: 'Ventas',
        data: rawSucursal.map(d => parseFloat(d.total)),
        backgroundColor: 'rgba(229,62,62,0.8)',
        borderColor: COLORS.primary,
        borderWidth: 1,
        borderRadius: 6,
      },
      {
        label: 'Margen %',
        data: rawSucursal.map(d => parseFloat(d.margen)),
        backgroundColor: 'rgba(155,44,44,0.6)',
        borderColor: COLORS.secondary,
        borderWidth: 1,
        borderRadius: 6,
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { callback: v => 'S/ '+(v/1000).toFixed(0)+'K' } },
      y: { grid: { display: false } },
      y1: { position: 'right', grid: { display: false }, ticks: { callback: v => v+'%' } }
    }
  }
});

/* ── Gráfico 4: Comparativa Mensual ────────────────────── */
const ctxBar = document.getElementById('chartComparativa').getContext('2d');
const gradBar = ctxBar.createLinearGradient(0,0,0,200);
gradBar.addColorStop(0, 'rgba(229,62,62,0.9)');
gradBar.addColorStop(1, 'rgba(229,62,62,0.4)');

new Chart(ctxBar, {
  type: 'bar',
  data: {
    labels: rawCompar.map(d => {
      const [y,m] = d.mes.split('-');
      return new Date(y,m-1).toLocaleDateString('es-PE',{month:'short',year:'2-digit'});
    }),
    datasets: [
      {
        label: 'Ventas S/',
        data: rawCompar.map(d => parseFloat(d.ventas)),
        backgroundColor: gradBar,
        borderRadius: 6, borderSkipped: false,
      },
      {
        type: 'line',
        label: 'Utilidad',
        data: rawCompar.map(d => parseFloat(d.utilidad)),
        borderColor: COLORS.success,
        backgroundColor: 'transparent',
        borderWidth: 2.5,
        pointRadius: 5,
        pointBackgroundColor: COLORS.success,
        tension: 0.4,
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color:'rgba(255,255,255,0.04)' }, ticks: { callback: v => 'S/ '+(v/1000).toFixed(0)+'K' } }
    }
  }
});

/* ── Heatmap Horario ────────────────────────────────────── */
(function buildHeatmap() {
  const dias   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
  const horas  = Array.from({length:24}, (_,i) => String(i).padStart(2,'0') + 'h');

  const container = document.getElementById('heatmapContainer');

  const grid = {};
  rawHeatmap.forEach(r => {
    const key = `${r.dia_semana}-${r.hora}`;
    grid[key] = parseFloat(r.total);
  });
  const maxVal = Math.max(...Object.values(grid), 1);

  let html = '<div style="display:grid;grid-template-columns:40px repeat(24,1fr);gap:3px;font-size:10px;">';
  html += '<div></div>';
  horas.forEach(h => {
    html += `<div style="text-align:center;color:var(--text-muted);padding-bottom:4px;">${h.replace('h','')}</div>`;
  });

  dias.forEach((dia, d) => {
    html += `<div style="color:var(--text-muted);display:flex;align-items:center;padding-right:6px;">${dia}</div>`;
    for (let h=0; h<24; h++) {
      const val = grid[`${d}-${h}`] || 0;
      const intensity = Math.round((val / maxVal) * 255);
      const alpha = Math.max(0.05, val/maxVal);
      html += `<div title="${dia} ${h}:00 — S/ ${val.toLocaleString('es-PE')}"
                    style="height:24px;border-radius:3px;background:rgba(229,62,62,${alpha});cursor:default;transition:all 0.2s;"
                    onmouseenter="this.style.opacity='0.7'"
                    onmouseleave="this.style.opacity='1'"
               ></div>`;
    }
  });

  html += '</div>';
  html += '<div style="margin-top:12px;display:flex;align-items:center;gap:8px;justify-content:flex-end;">';
  html += '<span style="font-size:11px;color:var(--text-muted);">Menor actividad</span>';
  for (let i=1;i<=8;i++) {
    const a = (i/8).toFixed(2);
    html += `<div style="width:24px;height:16px;border-radius:3px;background:rgba(229,62,62,${a});"></div>`;
  }
  html += '<span style="font-size:11px;color:var(--text-muted);">Mayor actividad</span>';
  html += '</div>';

  container.innerHTML = html;
})();

/* ── Filtros AJAX ─────────────────────────────────────────*/
function aplicarFiltros() {
  const desde    = document.getElementById('f_desde').value;
  const hasta    = document.getElementById('f_hasta').value;
  const sucursal = document.getElementById('f_sucursal').value;
  window.location.href = `<?= $config['url'] ?>/dashboard?desde=${desde}&hasta=${hasta}&sucursal_id=${sucursal}`;
}

function resetFiltros() {
  window.location.href = '<?= $config['url'] ?>/dashboard';
}

/* ── Modal Metas Dinámicas ────────────────────────────────*/
function abrirModalMetas() {
  document.getElementById('modalMetas').style.display = 'flex';
}

function cerrarModalMetas() {
  document.getElementById('modalMetas').style.display = 'none';
  document.getElementById('metasStatus').textContent = '';
}

async function guardarMetas(e) {
  e.preventDefault();
  const form = document.getElementById('formMetas');
  const btn = document.getElementById('btnGuardarMetas');
  const status = document.getElementById('metasStatus');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
  status.textContent = 'Actualizando metas...';
  
  const formData = new FormData(form);
  formData.append('_csrf', getCSRF());
  
  try {
    const res = await fetch('<?= $config['url'] ?>/dashboard/metas', {
      method: 'POST',
      body: new URLSearchParams(formData)
    });
    const data = await res.json();
    
    if (data.success) {
      status.style.color = '#38A169';
      status.textContent = '✅ ' + data.message;
      setTimeout(() => location.reload(), 1200);
    } else {
      status.style.color = '#E53E3E';
      status.textContent = '❌ Error: ' + (data.error || 'No se pudo guardar.');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-save"></i> Guardar Cambios';
    }
  } catch(err) {
    status.style.color = '#E53E3E';
    status.textContent = '❌ Error de conexión con el servidor.';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save"></i> Guardar Cambios';
  }
}
</script>

<!-- Modal de Metas por Sucursal (HTML) -->
<div id="modalMetas" class="modal-bi" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:2000; align-items:center; justify-content:center;">
  <div class="card-glass p-4 animate-in" style="width:100%; max-width:480px; background:var(--bg-card); border-color:var(--primary); box-shadow:var(--shadow-hover);">
    <div class="flex justify-between items-center mb-3" style="border-bottom:1px solid var(--border); padding-bottom:10px;">
      <h3 style="font-size:16px; font-weight:800; color:var(--text-primary); margin:0;"><i class="bi bi-gear" style="color:var(--primary);"></i> Definir Metas por Sucursal</h3>
      <button onclick="cerrarModalMetas()" style="padding:4px 8px; border:none; background:none; font-size:18px; color:var(--text-muted); cursor:pointer;"><i class="bi bi-x"></i></button>
    </div>
    <form id="formMetas" onsubmit="guardarMetas(event)">
      <div style="max-height:280px; overflow-y:auto; padding-right:5px; margin-bottom:20px;">
        <?php foreach ($sucursales as $s): ?>
        <div class="mb-3">
          <label class="form-label-bi" style="font-size:11px;"><?= Security::e($s['nombre']) ?></label>
          <div style="position:relative;">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; color:var(--text-muted); font-weight:700;">S/</span>
            <input type="number" step="0.01" name="metas[<?= $s['id'] ?>]" class="input-bi" value="<?= $s['meta_monto'] ?>" style="padding-left:32px;" required>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="flex justify-end gap-2" style="border-top:1px solid var(--border); padding-top:15px; justify-content: flex-end;">
        <button type="button" class="btn-bi secondary sm" onclick="cerrarModalMetas()">Cancelar</button>
        <button type="submit" class="btn-bi primary sm" id="btnGuardarMetas"><i class="bi bi-save"></i> Guardar Cambios</button>
      </div>
    </form>
    <div id="metasStatus" style="margin-top:10px; font-size:12px; font-weight:600; text-align:center;"></div>
  </div>
</div>
