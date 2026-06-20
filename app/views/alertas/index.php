<?php
$statsByNivel = [];
$statsByEstado= [];
foreach ($stats as $s) {
  $statsByNivel[$s['nivel']]   = ($statsByNivel[$s['nivel']] ?? 0) + (int)$s['cantidad'];
  $statsByEstado[$s['estado']] = ($statsByEstado[$s['estado']] ?? 0) + (int)$s['cantidad'];
}
?>

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
<div class="card-glass p-3 mb-4 animate-in" style="display:flex;gap:15px;flex-wrap:wrap;align-items:center;">
  <div style="display:flex;gap:8px;align-items:center;min-width:220px;flex:1;">
    <span class="text-muted text-xs">Buscar:</span>
    <input type="text" id="buscarTexto" placeholder="Buscar por título o descripción..." class="input-bi" style="padding: 6px 12px; font-size:13px; margin:0; flex:1;">
  </div>
  
  <div style="display:flex;gap:8px;align-items:center;">
    <span class="text-muted text-xs">Nivel:</span>
    <select id="selectNivel" class="input-bi" style="padding: 6px 12px; font-size:13px; margin:0; width:160px;">
      <option value="">Todos los niveles</option>
      <option value="danger" <?= $nivel === 'danger' ? 'selected' : '' ?>>🔴 Críticas (Danger)</option>
      <option value="warning" <?= $nivel === 'warning' ? 'selected' : '' ?>>🟡 Advertencias (Warning)</option>
      <option value="info" <?= $nivel === 'info' ? 'selected' : '' ?>>🔵 Informativas (Info)</option>
      <option value="success" <?= $nivel === 'success' ? 'selected' : '' ?>>🟢 Positivas (Success)</option>
    </select>
  </div>

  <div style="display:flex;gap:8px;align-items:center;">
    <span class="text-muted text-xs">Estado:</span>
    <select id="selectEstado" class="input-bi" style="padding: 6px 12px; font-size:13px; margin:0; width:130px;">
      <option value="">Todos los estados</option>
      <option value="nueva" <?= $estado === 'nueva' ? 'selected' : '' ?>>Nueva</option>
      <option value="revisada" <?= $estado === 'revisada' ? 'selected' : '' ?>>Revisada</option>
      <option value="resuelta" <?= $estado === 'resuelta' ? 'selected' : '' ?>>Resuelta</option>
    </select>
  </div>

  <div style="margin-left:auto;display:flex;gap:8px;">
    <button class="btn-bi secondary sm" onclick="generarAlertas()" style="height:36px;">
      <i class="bi bi-lightning-charge"></i> Generar Automáticas
    </button>
    <div id="genStatus" style="font-size:13px;color:var(--text-muted);align-self:center;"></div>
  </div>
</div>

<!-- ── Lista de Alertas ────────────────────────────── -->
<div class="animate-in">
  <div id="alertasList">
    <?php foreach ($alertas as $a): ?>
    <div class="alert-card <?= Security::e($a['nivel']) ?>" id="alerta-<?= $a['id'] ?>"
         data-nivel="<?= Security::e($a['nivel']) ?>"
         data-estado="<?= Security::e($a['estado']) ?>"
         data-search="<?= Security::e(mb_strtolower($a['titulo'] . ' ' . $a['mensaje'])) ?>">
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
    <div id="emptyMessage" style="text-align:center;padding:60px;color:var(--text-muted);">
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
    setTimeout(() => {
      el.remove();
      aplicarFiltros();
    }, 500);
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

function aplicarFiltros() {
  const query = document.getElementById('buscarTexto').value.toLowerCase().trim();
  const nivel = document.getElementById('selectNivel').value;
  const estado = document.getElementById('selectEstado').value;
  
  const cards = document.querySelectorAll('.alert-card');
  let mostrados = 0;
  
  cards.forEach(card => {
    const cardNivel = card.dataset.nivel;
    const cardEstado = card.dataset.estado;
    const cardSearch = card.dataset.search;
    
    const matchesSearch = !query || cardSearch.includes(query);
    const matchesNivel = !nivel || cardNivel === nivel;
    const matchesEstado = !estado || cardEstado === estado;
    
    if (matchesSearch && matchesNivel && matchesEstado) {
      card.style.display = 'flex';
      mostrados++;
    } else {
      card.style.display = 'none';
    }
  });
  
  // Mostrar mensaje de no hay resultados si mostrados === 0
  const existingNoResults = document.getElementById('noResultsMessage');
  const emptyMessage = document.getElementById('emptyMessage');
  
  if (mostrados === 0) {
    if (emptyMessage) emptyMessage.style.display = 'none';
    if (!existingNoResults) {
      const msg = document.createElement('div');
      msg.id = 'noResultsMessage';
      msg.style.textAlign = 'center';
      msg.style.padding = '60px';
      msg.style.color = 'var(--text-muted)';
      msg.innerHTML = `
        <i class="bi bi-search" style="font-size:40px;display:block;margin-bottom:10px;color:var(--text-muted);"></i>
        <h4 style="font-size:16px;font-weight:700;">Sin resultados</h4>
        <p style="font-size:13px;margin:0;">No se encontraron alertas con los filtros seleccionados.</p>
      `;
      document.getElementById('alertasList').appendChild(msg);
    }
  } else {
    if (emptyMessage) emptyMessage.style.display = 'block';
    if (existingNoResults) existingNoResults.remove();
  }
}

document.getElementById('buscarTexto').addEventListener('input', aplicarFiltros);
document.getElementById('selectNivel').addEventListener('change', aplicarFiltros);
document.getElementById('selectEstado').addEventListener('change', aplicarFiltros);

// Aplicar filtros iniciales (de los selectores)
aplicarFiltros();
</script>
