<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Security::e($title ?? 'FAXEL BI') ?></title>
  <meta name="description" content="FAXEL BI — Plataforma de Business Intelligence con IA Predictiva">
  
  <!-- PWA Meta tags -->
  <meta name="theme-color" content="#E53E3E">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="FAXEL BI">
  <link rel="apple-touch-icon" href="<?= $config['url'] ?>/assets/img/logo-192.png">
  <link rel="manifest" href="<?= $config['url'] ?>/manifest.json">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- FAXEL CSS -->
  <link href="<?= $config['url'] ?>/assets/css/main.css?v=2.0.4" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">

  <!-- ══ SIDEBAR ════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">

    <a href="<?= $config['url'] ?>/dashboard" class="sidebar-brand" style="border-bottom: 1px solid rgba(0,0,0,0.1); padding: 20px 20px;">
      <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
        <div style="width: 36px; height: 36px; background: #000000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 20px; color: #FFFFFF; font-family: 'Inter', sans-serif; min-width: 36px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">F</div>
        <div style="display: flex; flex-direction: column; line-height: 1.1; overflow: hidden;">
          <span style="font-weight: 800; font-size: 18px; color: #FFFFFF; letter-spacing: 0.5px;">FAXEL</span>
          <span style="font-size: 8px; color: rgba(255,255,255,0.85); font-weight: 600; text-transform: uppercase; letter-spacing: 0.1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Facturación Electrónica</span>
        </div>
      </div>
    </a>

    <nav class="sidebar-nav">

      <div class="nav-section">Principal</div>

      <a href="<?= $config['url'] ?>/dashboard" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], '/dashboard') || $_SERVER['REQUEST_URI'] === '/') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
        <span>Dashboard Ejecutivo</span>
      </a>

      <div class="nav-section mt-2">Inteligencia IA</div>

      <a href="<?= $config['url'] ?>/prediccion" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/prediccion') && !str_contains($_SERVER['REQUEST_URI'], 'churn') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
        <span>Predicción de Ventas</span>
      </a>

      <a href="<?= $config['url'] ?>/prediccion/churn" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], 'churn') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-person-dash"></i></span>
        <span>Riesgo Abandono</span>
        <?php
          $db = Database::getInstance();
          $altRisk = $db->query("SELECT COUNT(*) FROM clientes WHERE churn_riesgo='alto' AND activo=1")->fetchColumn();
          if ($altRisk > 0):
        ?>
        <span class="nav-badge"><?= $altRisk ?></span>
        <?php endif; ?>
      </a>

      <a href="<?= $config['url'] ?>/chat" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/chat') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-chat-dots"></i></span>
        <span>Asistente IA</span>
      </a>

      <div class="nav-section mt-2">Análisis</div>

      <a href="<?= $config['url'] ?>/productos/rentabilidad" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/productos') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-box-seam"></i></span>
        <span>Rentabilidad</span>
      </a>

      <a href="<?= $config['url'] ?>/alertas" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/alertas') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-bell"></i></span>
        <span>Centro de Alertas</span>
        <?php
          $newAlerts = $db->query("SELECT COUNT(*) FROM alertas WHERE estado='nueva'")->fetchColumn();
          if ($newAlerts > 0):
        ?>
        <span class="nav-badge"><?= $newAlerts ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section mt-2">Facturación</div>

      <a href="<?= $config['url'] ?>/facturas" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/facturas') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-receipt"></i></span>
        <span>Facturas & Boletas</span>
      </a>

      <div class="nav-section mt-2">Reportes</div>

      <a href="<?= $config['url'] ?>/reportes" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/reportes') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span>
        <span>Reportes & KPIs</span>
      </a>

    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= mb_strtoupper(mb_substr($user['nombre'] ?? 'U', 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= Security::e(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></div>
          <div class="user-role"><?= Security::e(ucfirst($user['rol'] ?? 'Operador')) ?></div>
        </div>
      </div>
      <a href="<?= $config['url'] ?>/logout" class="btn-bi secondary sm w-full mt-2" style="justify-content:center;">
        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
      </a>
    </div>

  </aside>

  <!-- ══ MAIN CONTENT ═══════════════════════════════════════ -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="btn-sidebar-toggle" id="sidebarToggle" title="Colapsar menú">
          <i class="bi bi-layout-sidebar"></i>
        </button>
        <div>
          <div class="page-title"><?= Security::e($title ?? 'Dashboard') ?></div>
          <div class="breadcrumb-bi">
            <span><?= date('l, d \d\e F Y', time()) ?></span>
          </div>
        </div>
      </div>

      <div class="topbar-right">
        <!-- Hora en vivo -->
        <div class="badge-bi info" id="live-clock" style="font-family: monospace; font-size:13px;">
          <?= date('H:i:s') ?>
        </div>

        <!-- Alertas -->
        <a href="<?= $config['url'] ?>/alertas" class="topbar-action" title="Alertas">
          <i class="bi bi-bell"></i>
          <?php if ($newAlerts > 0): ?>
          <span class="notif-dot"></span>
          <?php endif; ?>
        </a>

        <!-- Reportes -->
        <a href="<?= $config['url'] ?>/reportes" class="topbar-action" title="Exportar">
          <i class="bi bi-download"></i>
        </a>
      </div>
    </header>

    <!-- Contenido de la página -->
    <main class="page-content">
      <?= $content ?>
    </main>

  </div><!-- /main-content -->

</div><!-- /app-wrapper -->

<!-- Contenedor para notificaciones flotantes (Toasts) SSE -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<!-- Chart.js Plugins -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
/* ── Sidebar toggle ─────────────────────────────────── */
const sidebar  = document.getElementById('sidebar');
const toggle   = document.getElementById('sidebarToggle');
const PREF_KEY = 'faxel_sidebar_collapsed';

if (window.innerWidth > 768) {
  if (localStorage.getItem(PREF_KEY) === '1') sidebar.classList.add('collapsed');
}

toggle?.addEventListener('click', (e) => {
  e.stopPropagation();
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('open');
    toggleMobileBackdrop();
  } else {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem(PREF_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
  }
});

function toggleMobileBackdrop() {
  let backdrop = document.getElementById('sidebar-backdrop');
  if (sidebar.classList.contains('open')) {
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'sidebar-backdrop';
      backdrop.style.position = 'fixed';
      backdrop.style.inset = '0';
      backdrop.style.backgroundColor = 'rgba(0,0,0,0.6)';
      backdrop.style.backdropFilter = 'blur(4px)';
      backdrop.style.zIndex = '999';
      backdrop.style.transition = 'opacity 0.25s ease';
      document.body.appendChild(backdrop);
      
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('open');
        backdrop.remove();
      });
    }
  } else {
    if (backdrop) backdrop.remove();
  }
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 768) {
    sidebar.classList.remove('open');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (backdrop) backdrop.remove();
  }
});

/* ── Live clock ─────────────────────────────────────── */
function updateClock() {
  const el = document.getElementById('live-clock');
  if (el) el.textContent = new Date().toLocaleTimeString('es-PE');
}
setInterval(updateClock, 1000);

/* ── Chart.js defaults (light theme) ────────────────── */
Chart.defaults.color           = '#475569';
Chart.defaults.borderColor     = 'rgba(0,0,0,0.08)';
Chart.defaults.font.family     = "'Inter', sans-serif";
Chart.defaults.font.size       = 12;
Chart.defaults.plugins.legend.labels.padding = 20;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,22,41,0.95)';
Chart.defaults.plugins.tooltip.borderColor     = 'rgba(255,255,255,0.1)';
Chart.defaults.plugins.tooltip.borderWidth     = 1;
Chart.defaults.plugins.tooltip.padding         = 12;
Chart.defaults.plugins.tooltip.cornerRadius    = 8;
Chart.defaults.plugins.tooltip.titleColor      = '#F1F5F9';
Chart.defaults.plugins.tooltip.bodyColor       = '#94A3B8';

/* ── Animación de contadores ─────────────────────────── */
function animateCounter(el, target, prefix='', suffix='', decimals=0) {
  const duration = 1200;
  const start    = performance.now();

  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    const current = target * ease;
    el.textContent = prefix + current.toLocaleString('es-PE', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }) + suffix;
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

document.querySelectorAll('[data-counter]').forEach(el => {
  const target  = parseFloat(el.dataset.counter);
  const prefix  = el.dataset.prefix  || '';
  const suffix  = el.dataset.suffix  || '';
  const decimals= parseInt(el.dataset.decimals || '0');
  animateCounter(el, target, prefix, suffix, decimals);
});

/* ── CSRF helper ──────────────────────────────────────── */
function getCSRF() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// Registrar Service Worker para PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('<?= $config['url'] ?>/sw.js')
      .then(reg => console.log('Service Worker registrado:', reg.scope))
      .catch(err => console.error('Error al registrar Service Worker:', err));
  });
}

/* ── Listener de Alertas en Tiempo Real via SSE ────────────────── */
const urlBaseSSE = '<?= $config['url'] ?>';
const eventSourceSSE = new EventSource(urlBaseSSE + '/alertas/stream');

eventSourceSSE.onmessage = function(event) {
  try {
    const alerta = JSON.parse(event.data);
    mostrarToastAlerta(alerta);
    actualizarBadgesAlertas();
  } catch (err) {
    console.error('Error parseando alerta SSE:', err);
  }
};

eventSourceSSE.onerror = function(err) {
  console.warn('Error en la conexión EventSource SSE, reintentando...', err);
};

function mostrarToastAlerta(alerta) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toastEl = document.createElement('div');
  toastEl.className = `toast toast-bi toast-${alerta.nivel || 'warning'} hide`;
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');

  let iconClass = 'bi-exclamation-triangle';
  if (alerta.nivel === 'danger') iconClass = 'bi-x-circle';
  else if (alerta.nivel === 'success') iconClass = 'bi-check-circle';
  else if (alerta.nivel === 'info') iconClass = 'bi-info-circle';

  toastEl.innerHTML = `
    <div class="toast-header">
      <strong class="me-auto text-${alerta.nivel || 'warning'}">
        <i class="bi ${iconClass} me-2"></i> ${escapeHTML(alerta.titulo)}
      </strong>
      <small class="text-muted">Ahora mismo</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      ${escapeHTML(alerta.mensaje)}
    </div>
  `;

  container.appendChild(toastEl);

  const bsToast = new bootstrap.Toast(toastEl, {
    autohide: true,
    delay: 8000
  });
  bsToast.show();

  toastEl.addEventListener('hidden.bs.toast', () => {
    toastEl.remove();
  });
}

function escapeHTML(str) {
  return str.replace(/[&<>'"]/g, 
    tag => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      "'": '&#39;',
      '"': '&quot;'
    }[tag] || tag)
  );
}

function actualizarBadgesAlertas() {
  const sidebarBadge = document.querySelector('.sidebar-nav a[href$="/alertas"] .nav-badge');
  const topbarAction = document.querySelector('.topbar-action[href$="/alertas"]');

  if (sidebarBadge) {
    let currentVal = parseInt(sidebarBadge.textContent) || 0;
    sidebarBadge.textContent = currentVal + 1;
    sidebarBadge.style.display = 'inline-block';
  } else {
    const alertasLink = document.querySelector('.sidebar-nav a[href$="/alertas"]');
    if (alertasLink) {
      const badge = document.createElement('span');
      badge.className = 'nav-badge';
      badge.textContent = '1';
      alertasLink.appendChild(badge);
    }
  }

  if (topbarAction) {
    let dot = topbarAction.querySelector('.notif-dot');
    if (!dot) {
      dot = document.createElement('span');
      dot.className = 'notif-dot';
      topbarAction.appendChild(dot);
    }
  }
}
</script>

<?php if (isset($pageScript)): ?>
<script><?= $pageScript ?></script>
<?php endif; ?>

</body>
</html>
