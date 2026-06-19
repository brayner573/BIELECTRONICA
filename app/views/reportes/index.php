<?php
$kpisAv = $kpisAvanzados;
$appUrl = $config['url'];
?>
<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">

<!-- ── KPIs Grid ──────────────────────────────────────── -->
<div class="row g-3 mb-4">

  <!-- KPIs estándar -->
  <?php
  $kpiCards = [
    ['label'=>'Ventas del Mes',     'val'=>'S/ '.number_format($kpis['ventas_mes'],0),    'icon'=>'bi-currency-dollar','color'=>'var(--primary)'],
    ['label'=>'Crecimiento MoM',    'val'=>($kpis['crecimiento']>=0?'+':'').round($kpis['crecimiento'],1).'%', 'icon'=>'bi-trending-up','color'=>$kpis['crecimiento']>=0?'var(--success)':'var(--danger)'],
    ['label'=>'Ticket Promedio',    'val'=>'S/ '.number_format($kpis['ticket_prom'],0),   'icon'=>'bi-receipt','color'=>'var(--accent)'],
    ['label'=>'Clientes Activos',   'val'=>$kpis['clientes_activos'],                     'icon'=>'bi-people','color'=>'var(--warning)'],
    ['label'=>'Rentabilidad',       'val'=>round($kpis['rentabilidad'],1).'%',             'icon'=>'bi-gem','color'=>'var(--secondary)'],
    ['label'=>'CAC',                'val'=>'S/ '.number_format($kpisAv['cac']??0,2),      'icon'=>'bi-person-add','color'=>'var(--danger)'],
    ['label'=>'LTV Promedio',       'val'=>'S/ '.number_format($kpisAv['ltv']??0,0),      'icon'=>'bi-graph-up-arrow','color'=>'var(--success)'],
    ['label'=>'LTV/CAC Ratio',      'val'=>number_format($kpisAv['ltv_cac']??0,1).'x',    'icon'=>'bi-diagram-3','color'=>'var(--primary)'],
    ['label'=>'Churn Rate',         'val'=>round($kpisAv['churn_rate']??0,1).'%',          'icon'=>'bi-person-dash','color'=>($kpisAv['churn_rate']??0)>20?'var(--danger)':'var(--success)'],
  ];
  foreach ($kpiCards as $i => $k):
  ?>
  <div class="col-xl-3 col-md-4 col-sm-6 animate-in stagger-<?= ($i%6)+1 ?>">
    <div class="kpi-card" style="--card-color:<?= $k['color'] ?>;">
      <div class="kpi-icon" style="color:<?= $k['color'] ?>;"><i class="bi <?= $k['icon'] ?>"></i></div>
      <div class="kpi-label"><?= $k['label'] ?></div>
      <div class="kpi-value" style="font-size:22px;"><?= Security::e($k['val']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Acciones de Exportación ────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-12 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title mb-4">📤 Exportación de Reportes</div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;">

        <div class="export-card" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;padding:24px;flex:1;min-width:220px;text-align:center;transition:var(--transition);cursor:pointer;"
             onmouseenter="this.style.borderColor='rgba(79,142,247,0.4)'"
             onmouseleave="this.style.borderColor='rgba(255,255,255,0.08)'"
             onclick="window.location.href='<?= $appUrl ?>/reportes/excel'">
          <div style="font-size:40px;margin-bottom:12px;">📊</div>
          <div style="font-weight:700;margin-bottom:4px;">Exportar CSV/Excel</div>
          <div style="font-size:12px;color:var(--text-muted);">Ventas mensuales (12 meses)</div>
          <button class="btn-bi primary mt-3 sm" style="width:100%;justify-content:center;">
            <i class="bi bi-file-earmark-spreadsheet"></i> Descargar CSV
          </button>
        </div>

        <div class="export-card" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;padding:24px;flex:1;min-width:220px;text-align:center;transition:var(--transition);"
             onmouseenter="this.style.borderColor='rgba(239,68,68,0.4)'"
             onmouseleave="this.style.borderColor='rgba(255,255,255,0.08)'">
          <div style="font-size:40px;margin-bottom:12px;">📄</div>
          <div style="font-weight:700;margin-bottom:4px;">Reporte PDF</div>
          <div style="font-size:12px;color:var(--text-muted);">Dashboard ejecutivo completo</div>
          <button class="btn-bi secondary mt-3 sm" style="width:100%;justify-content:center;" onclick="window.print()">
            <i class="bi bi-file-earmark-pdf"></i> Imprimir / PDF
          </button>
        </div>

        <div class="export-card" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;padding:24px;flex:1;min-width:220px;text-align:center;transition:var(--transition);"
             onmouseenter="this.style.borderColor='rgba(16,185,129,0.4)'"
             onmouseleave="this.style.borderColor='rgba(255,255,255,0.08)'">
          <div style="font-size:40px;margin-bottom:12px;">🤖</div>
          <div style="font-weight:700;margin-bottom:4px;">KPIs Avanzados IA</div>
          <div style="font-size:12px;color:var(--text-muted);">CAC, LTV, Churn — cálculo en tiempo real</div>
          <button class="btn-bi secondary mt-3 sm" style="width:100%;justify-content:center;color:var(--success);" onclick="refreshKpis()">
            <i class="bi bi-cpu"></i> Recalcular KPIs
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ── Explicación KPIs ────────────────────────────────── -->
<div class="row g-3">
  <div class="col-12 animate-in">
    <div class="chart-wrapper">
      <div class="chart-title mb-3">📚 Glosario de KPIs</div>
      <div class="row g-3">
        <?php
        $glosario = [
          ['kpi'=>'CAC','nombre'=>'Costo de Adquisición de Cliente','formula'=>'Gasto marketing / Clientes nuevos','color'=>'var(--danger)','desc'=>'Cuánto cuesta traer un cliente nuevo al negocio.'],
          ['kpi'=>'LTV','nombre'=>'Lifetime Value','formula'=>'Ticket prom × Frecuencia × Vida del cliente','color'=>'var(--success)','desc'=>'Valor total que un cliente genera durante toda su relación.'],
          ['kpi'=>'Churn','nombre'=>'Tasa de Abandono','formula'=>'Clientes perdidos / Total × 100','color'=>'var(--warning)','desc'=>'Porcentaje de clientes que dejaron de comprar.'],
          ['kpi'=>'MoM','nombre'=>'Month over Month','formula'=>'(Mes actual - Mes ant.) / Mes ant. × 100','color'=>'var(--primary)','desc'=>'Crecimiento respecto al mes anterior.'],
        ];
        foreach ($glosario as $g):
        ?>
        <div class="col-xl-3 col-md-6">
          <div style="background:var(--bg-elevated);border-radius:10px;padding:16px;border-left:4px solid <?= $g['color'] ?>;">
            <div style="font-size:20px;font-weight:900;color:<?= $g['color'] ?>;margin-bottom:4px;"><?= $g['kpi'] ?></div>
            <div style="font-weight:700;font-size:13px;margin-bottom:6px;"><?= $g['nombre'] ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;"><?= $g['desc'] ?></div>
            <div style="font-size:11px;background:rgba(255,255,255,0.05);border-radius:4px;padding:6px;font-family:monospace;color:var(--accent);"><?= $g['formula'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
async function refreshKpis() {
  const res  = await fetch('<?= $appUrl ?>/reportes/kpis');
  const data = await res.json();
  if (data.success) {
    alert('KPIs actualizados. Recargando...');
    location.reload();
  }
}
</script>
