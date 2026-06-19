<?php
$historicJson = json_encode(array_values($historico));
$preds7Json   = json_encode(array_values($predicciones7d));
?>
<div class="row mb-4 animate-in">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);margin:0;">Predicciones con IA</h2>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Modelos de Prophet y XGBoost entrenados para proyectar el volumen de ventas.</p>
    </div>
    <a href="<?= $config['url'] ?>/prediccion/entrenamiento" class="btn-bi primary">
      <i class="bi bi-cpu"></i> Panel de Entrenamiento
    </a>
  </div>
</div>

<!-- ── Acciones y métricas del modelo ────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-xl-8 animate-in">
    <div class="card-glass p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="chart-title">🤖 Motor de Predicción — Prophet + XGBoost</div>
          <div class="chart-subtitle">Modelos de series temporales con validación cruzada</div>
        </div>
        <button class="btn-bi primary" id="btnPredecir" onclick="ejecutarPrediccion()">
          <i class="bi bi-play-circle"></i> Ejecutar Predicción
        </button>
      </div>

      <?php if ($ultimaPrediccion): ?>
      <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <?php
        $metricas = [
          ['label' => 'R² Score', 'val' => round($ultimaPrediccion['exactitud'] ?? 0, 1).'%', 'icon' => 'bi-bullseye', 'color' => 'var(--success)'],
          ['label' => 'MAE',      'val' => number_format($ultimaPrediccion['mae']  ?? 0, 2),  'icon' => 'bi-bar-chart', 'color' => 'var(--warning)'],
          ['label' => 'RMSE',     'val' => number_format($ultimaPrediccion['rmse'] ?? 0, 2),  'icon' => 'bi-graph-up', 'color' => 'var(--primary)'],
          ['label' => 'Modelo',   'val' => ucfirst($ultimaPrediccion['modelo'] ?? 'prophet'), 'icon' => 'bi-cpu', 'color' => 'var(--secondary)'],
        ];
        foreach ($metricas as $m):
        ?>
        <div style="background:var(--bg-elevated);border-radius:8px;padding:12px 16px;min-width:110px;text-align:center;">
          <i class="bi <?= $m['icon'] ?>" style="font-size:20px;color:<?= $m['color'] ?>;"></i>
          <div style="font-size:16px;font-weight:800;color:var(--text-heading);margin-top:6px;"><?= Security::e($m['val']) ?></div>
          <div style="font-size:11px;color:var(--text-muted);"><?= $m['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:24px;color:var(--text-muted);">
        <i class="bi bi-cpu" style="font-size:36px;color:var(--primary);"></i>
        <p class="mt-2">No hay predicciones generadas aún. Ejecuta el modelo para comenzar.</p>
      </div>
      <?php endif; ?>

      <div id="predStatus" style="margin-top:12px;font-size:13px;color:var(--text-muted);"></div>
    </div>
  </div>

  <!-- Predicción 30 días -->
  <div class="col-xl-4 animate-in stagger-1">
    <div class="kpi-card" style="--card-color:var(--secondary);">
      <div class="kpi-icon" style="color:var(--secondary);"><i class="bi bi-calendar3"></i></div>
      <div class="kpi-label">Predicción 30 Días</div>
      <?php
        $db = Database::getInstance();
        $pred30 = $db->query("SELECT valor_predicho FROM predicciones WHERE tipo='ventas_30d' ORDER BY created_at DESC LIMIT 1")->fetchColumn();
      ?>
      <div class="kpi-value" data-counter="<?= $pred30 ?: 0 ?>" data-prefix="S/ " data-decimals="0">S/ 0</div>
      <div class="kpi-change neutral">Total acumulado estimado</div>
    </div>
  </div>
</div>

<!-- ── Gráfico Principal: Histórico + Predicción ─────── -->
<div class="row g-4 mb-4">
  <div class="col-12 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title">📈 Histórico de Ventas + Predicción a 7 Días</div>
      <div class="chart-subtitle">Línea sólida = datos reales | Línea punteada = predicción | Área sombreada = intervalo de confianza 80%</div>
      <canvas id="chartPrediccion" height="90"></canvas>
    </div>
  </div>
</div>

<!-- ── Tabla de predicciones 7 días ──────────────────── -->
<div class="row g-4">
  <div class="col-xl-6 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title mb-3">📅 Predicciones Diarias — Próximos 7 Días</div>
      <table class="table-bi">
        <thead>
          <tr>
            <th>Fecha</th>
            <th style="text-align:right;">Predicción</th>
            <th style="text-align:right;">Mínimo</th>
            <th style="text-align:right;">Máximo</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
          foreach ($predicciones7d as $p):
            $fecha   = new DateTime($p['fecha_prediccion']);
            $diaNom  = $dias[$fecha->format('w')];
          ?>
          <tr>
            <td>
              <span class="badge-bi info" style="margin-right:6px;"><?= $diaNom ?></span>
              <?= $fecha->format('d/m/Y') ?>
            </td>
            <td style="text-align:right;font-weight:700;color:var(--primary);">
              S/ <?= number_format($p['valor_predicho'],0) ?>
            </td>
            <td style="text-align:right;color:var(--text-muted);">
              S/ <?= number_format($p['limite_inf'],0) ?>
            </td>
            <td style="text-align:right;color:var(--text-muted);">
              S/ <?= number_format($p['limite_sup'],0) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($predicciones7d)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:24px;">Sin predicciones. Ejecuta el modelo.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-xl-6 animate-in stagger-1">
    <div class="chart-wrapper">
      <div class="chart-title mb-3">📊 Intervalos de Confianza 7 Días</div>
      <canvas id="chartIntervalos" height="200"></canvas>
    </div>
  </div>
</div>

<script>
const rawHistorico = <?= $historicJson ?>;
const rawPreds7    = <?= $preds7Json ?>;

/* Preparar datos */
const histLabels = rawHistorico.map(d => {
  const f = new Date(d.ds + 'T00:00:00');
  return f.toLocaleDateString('es-PE', {day:'2-digit',month:'short'});
});
const histValues = rawHistorico.map(d => parseFloat(d.y));

const predLabels = rawPreds7.map(d => {
  const f = new Date(d.fecha_prediccion + 'T00:00:00');
  return f.toLocaleDateString('es-PE', {day:'2-digit',month:'short'});
});
const predValues = rawPreds7.map(d => parseFloat(d.valor_predicho));
const predMin    = rawPreds7.map(d => parseFloat(d.limite_inf));
const predMax    = rawPreds7.map(d => parseFloat(d.limite_sup));

/* Gráfico principal */
const ctxPred = document.getElementById('chartPrediccion').getContext('2d');
const gradPred = ctxPred.createLinearGradient(0,0,0,300);
gradPred.addColorStop(0,'rgba(229,62,62,0.25)');
gradPred.addColorStop(1,'rgba(229,62,62,0)');

const allLabels = [...histLabels.slice(-30), ...predLabels];

new Chart(ctxPred, {
  data: {
    labels: allLabels,
    datasets: [
      {
        type: 'line',
        label: 'Ventas Reales',
        data: [...histValues.slice(-30), ...Array(predLabels.length).fill(null)],
        borderColor: '#E53E3E',
        backgroundColor: gradPred,
        borderWidth: 2.5,
        pointRadius: 2,
        fill: true, tension: 0.4,
      },
      {
        type: 'line',
        label: 'Predicción',
        data: [...Array(Math.max(histLabels.slice(-30).length-1,0)).fill(null),
               histValues.length ? histValues[histValues.length-1] : null,
               ...predValues],
        borderColor: '#F59E0B',
        borderDash: [8,4],
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#F59E0B',
        fill: false, tension: 0.4,
      },
      {
        type: 'line',
        label: 'Intervalo sup.',
        data: [...Array(histLabels.slice(-30).length).fill(null), ...predMax],
        borderColor: 'rgba(245,158,11,0.2)',
        backgroundColor: 'rgba(245,158,11,0.08)',
        borderWidth: 1, pointRadius: 0,
        fill: '+1', tension: 0.4,
      },
      {
        type: 'line',
        label: 'Intervalo inf.',
        data: [...Array(histLabels.slice(-30).length).fill(null), ...predMin],
        borderColor: 'rgba(245,158,11,0.2)',
        backgroundColor: 'rgba(245,158,11,0.08)',
        borderWidth: 1, pointRadius: 0,
        fill: false, tension: 0.4,
      },
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { labels: { filter: l => !l.text.includes('Intervalo') || l.datasetIndex === 2 } },
      tooltip: { callbacks: { label: ctx => ` S/ ${(ctx.raw||0).toLocaleString('es-PE',{minimumFractionDigits:0})}` } }
    },
    scales: {
      x: { grid: { display:false }, ticks: { maxTicksLimit:15 } },
      y: { grid: { color:'rgba(255,255,255,0.04)' }, ticks: { callback: v => 'S/ '+(v/1000).toFixed(0)+'K' } }
    }
  }
});

/* Gráfico intervalos */
if (rawPreds7.length) {
  new Chart(document.getElementById('chartIntervalos'), {
    type: 'bar',
    data: {
      labels: predLabels,
      datasets: [
        { label: 'Predicción', data: predValues, backgroundColor: 'rgba(229,62,62,0.8)', borderRadius: 4 },
        { label: 'Intervalo',  data: predMax.map((max,i) => max - predMin[i]),
          backgroundColor: 'rgba(245,158,11,0.3)', borderRadius: 4,
          base: predMin.map(v => v),
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
}

/* Ejecutar predicción */
async function ejecutarPrediccion() {
  const btn    = document.getElementById('btnPredecir');
  const status = document.getElementById('predStatus');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ejecutando modelo IA...';
  status.textContent = 'Enviando datos al microservicio Prophet...';

  try {
    const res = await fetch('<?= $config['url'] ?>/prediccion/ejecutar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ _csrf: getCSRF() }),
    });
    const data = await res.json();

    if (data.success) {
      status.textContent = '✅ Predicción completada. Recargando página...';
      setTimeout(() => location.reload(), 1800);
    } else if (data.fallback) {
      status.textContent = '⚠️ ' + data.message + ' Usando datos previos.';
    } else {
      status.textContent = '❌ Error: ' + (data.message || 'Desconocido');
    }
  } catch(e) {
    status.textContent = '❌ Error de conexión con el servidor IA. ¿Está Flask corriendo?';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-play-circle"></i> Ejecutar Predicción';
  }
}
</script>
