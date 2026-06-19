<?php
$statsByNivel = [];
$statsByEstado= [];
foreach ($stats as $s) {
  $statsByNivel[$s['nivel']]   = ($statsByNivel[$s['nivel']] ?? 0) + (int)$s['cantidad'];
  $statsByEstado[$s['estado']] = ($statsByEstado[$s['estado']] ?? 0) + (int)$s['cantidad'];
}
?>
<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">

<!-- ── Stats ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $niveles = [
    'danger'  => ['label'=>'Críticas', 'icon'=>'bi-exclamation-octagon-fill', 'color'=>'var(--danger)'],
    'warning' => ['label'=>'Advertencias','icon'=>'bi-exclamation-triangle-fill','color'=>'var(--warning)'],
    'info'    => ['label'=>'Informativas','icon'=>'bi-info-circle-fill','color'=>'var(--primary)'],
    'success' => ['label'=>'Positivas','icon'=>'bi-check-circle-fill','color'=>'var(--success)'],
  ];
  foreach ($niveles as $n => $info):
    $cnt = $statsByNivel[$n] ?? 0;
  ?>
  <div class="col-md-3 animate-in stagger-<?= array_search($n, array_keys($niveles))+1 ?>">
    <div class="kpi-card" style="--card-color:<?= $info['color'] ?>;">
      <div class="kpi-icon" style="color:<?= $info['color'] ?>;"><i class="bi <?= $info['icon'] ?>"></i></div>
      <div class="kpi-label"><?= $info['label'] ?></div>
      <div class="kpi-value" data-counter="<?= $cnt ?>"><?= $cnt ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Controles ─────────────────────────────────────── -->
<div class="card-glass p-3 mb-4 animate-in" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
  <span class="text-muted text-xs">Filtrar:</span>
  <a href="?nivel=danger"  class="btn-bi danger  sm">🔴 Críticas</a>
  <a href="?nivel=warning" class="btn-bi secondary sm" style="color:var(--warning);border-color:rgba(245,158,11,0.3);">🟡 Advertencias</a>
  <a href="?estado=nueva"  class="btn-bi primary sm">Nuevas</a>
  <a href="?estado=revisada" class="btn-bi secondary sm">Revisadas</a>
  <a href="?"              class="btn-bi secondary sm">Todas</a>

  <div style="margin-left:auto;display:flex;gap:8px;">
    <button class="btn-bi secondary sm" onclick="generarAlertas()">
      <i class="bi bi-lightning-charge"></i> Generar Automáticas
    </button>
    <div id="genStatus" style="font-size:13px;color:var(--text-muted);align-self:center;"></div>
  </div>
</div>

<!-- ── Lista de Alertas ────────────────────────────── -->
<div class="animate-in">
  <div id="alertasList">
    <?php foreach ($alertas as $a): ?>
    <div class="alert-card <?= Security::e($a['nivel']) ?>" id="alerta-<?= $a['id'] ?>">
      <div class="alert-card-icon" style="padding-top:2px;">
        <?= match($a['nivel']) { 'danger'=>'🔴','warning'=>'🟡','success'=>'🟢',default=>'🔵' } ?>
      </div>
      <div style="flex:1;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--text-primary);"><?= Security::e($a['titulo']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:4px;"><?= Security::e($a['mensaje']) ?></div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
            <span class="badge-bi <?= $a['estado'] === 'nueva' ? 'danger' : ($a['estado'] === 'revisada' ? 'warning' : 'success') ?>">
              <?= ucfirst($a['estado']) ?>
            </span>
            <?php if ($a['estado'] !== 'resuelta'): ?>
            <?php if ($a['estado'] === 'nueva'): ?>
            <button class="btn-bi secondary sm" onclick="revisarAlerta(<?= $a['id'] ?>)">
              <i class="bi bi-eye"></i> Revisar
            </button>
            <?php endif; ?>
            <button class="btn-bi success sm" onclick="resolverAlerta(<?= $a['id'] ?>)"
                    style="background:rgba(16,185,129,0.15);color:var(--success);border-color:rgba(16,185,129,0.3);">
              <i class="bi bi-check2"></i> Resolver
            </button>
            <?php endif; ?>
          </div>
        </div>
        <div style="margin-top:8px;display:flex;gap:12px;font-size:11px;color:var(--text-muted);">
          <span><i class="bi bi-tag"></i> <?= ucfirst(str_replace('_',' ', $a['tipo'])) ?></span>
          <span><i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></span>
          <?php if ($a['resuelto_por']): ?>
          <span><i class="bi bi-person-check"></i> Resuelto por: <?= Security::e($a['resuelto_por']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($alertas)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);">
      <i class="bi bi-check-all" style="font-size:48px;color:var(--success);"></i>
      <h3 class="mt-3">Todo en orden</h3>
      <p>No hay alertas con los filtros seleccionados.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const BASE = '<?= $config['url'] ?>';
const CSRF = getCSRF();

async function resolverAlerta(id) {
  if (!confirm('¿Marcar esta alerta como resuelta?')) return;

  const res  = await fetch(`${BASE}/alertas/${id}/resolver`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ _csrf: CSRF }),
  });
  const data = await res.json();

  if (data.success) {
    const el = document.getElementById(`alerta-${id}`);
    el.style.opacity = '0.4';
    el.style.transition = 'opacity 0.5s';
    setTimeout(() => el.remove(), 500);
  }
}

async function revisarAlerta(id) {
  await fetch(`${BASE}/alertas/${id}/revisar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ _csrf: CSRF }),
  });
  location.reload();
}

async function generarAlertas() {
  const status = document.getElementById('genStatus');
  status.textContent = 'Analizando datos...';

  const res  = await fetch(`${BASE}/alertas/generar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ _csrf: CSRF }),
  });
  const data = await res.json();

  if (data.success) {
    status.textContent = `✅ ${data.creadas} nuevas alertas generadas.`;
    if (data.creadas > 0) setTimeout(() => location.reload(), 1500);
  }
}
</script>
