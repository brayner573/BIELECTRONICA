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
  <!-- CSRF Token -->
  <meta name="csrf-token" content="<?= Security::generateCSRF() ?>">
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

      <a href="<?= $config['url'] ?>/prediccion" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/prediccion') && !str_contains($_SERVER['REQUEST_URI'], 'churn') && !str_contains($_SERVER['REQUEST_URI'], 'entrenamiento') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
        <span>Predicción de Ventas</span>
      </a>

      <?php if (ACL::hasPermission($user['rol'], 'prediccion.train')): ?>
      <a href="<?= $config['url'] ?>/prediccion/entrenamiento" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], '/prediccion/entrenamiento') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi bi-cpu"></i></span>
        <span>Entrenamiento IA</span>
      </a>
      <?php endif; ?>

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

/* ── Listener de Alertas en Tiempo Real via Polling ────────────────── */
const urlBasePoll = '<?= $config['url'] ?>';
let lastCheckedAlertId = 0;

// Obtener el ID de la alerta más reciente al cargar la página
async function initAlertsCheck() {
  try {
    const res = await fetch(urlBasePoll + '/alertas/stream?init=1');
    const data = await res.json();
    if (data && data.last_id) {
      lastCheckedAlertId = data.last_id;
    }
  } catch (err) {
    console.error('Error inicializando alertas:', err);
  }
}

async function checkNewAlerts() {
  try {
    const res = await fetch(urlBasePoll + '/alertas/stream?last_id=' + lastCheckedAlertId);
    const data = await res.json();
    if (data && data.nuevas && data.nuevas.length > 0) {
      data.nuevas.forEach(alerta => {
        mostrarToastAlerta(alerta);
        actualizarBadgesAlertas();
        lastCheckedAlertId = Math.max(lastCheckedAlertId, parseInt(alerta.id));
      });
    }
  } catch (err) {
    // Silencioso para evitar spam en consola en desarrollo local
  }
}

initAlertsCheck().then(() => {
  // Consultar nuevas alertas cada 10 segundos
  setInterval(checkNewAlerts, 10000);
});

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

<!-- ══ ESTILOS DEL ASISTENTE VIRTUAL GLOBAL ══════════════ -->
<style>
/* Botón Flotante */
.global-chat-btn {
  position: fixed;
  bottom: 25px;
  right: 25px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  cursor: pointer;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 4px 16px rgba(229, 62, 62, 0.4);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.global-chat-btn:hover {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 8px 24px rgba(229, 62, 62, 0.6);
}
.chat-btn-pulse {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  border-radius: 50%;
  border: 2px solid var(--primary);
  animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
  opacity: 0;
}
@keyframes pulse-ring {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { opacity: 0.5; }
  100% { transform: scale(1.4); opacity: 0; }
}

/* Ventana de Chat */
.global-chat-widget {
  position: fixed;
  bottom: 95px;
  right: 25px;
  width: 375px;
  height: 500px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
  z-index: 2000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  opacity: 0;
  transform: scale(0.9) translateY(10px);
  pointer-events: none;
  transition: all 0.25s cubic-bezier(0.3, 0.7, 0.4, 1.1);
  transform-origin: bottom right;
}
.global-chat-widget.show {
  opacity: 1;
  transform: scale(1) translateY(0);
  pointer-events: auto;
}

/* Cabecera */
.chat-widget-header {
  background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}
.chat-header-user {
  display: flex;
  align-items: center;
  gap: 10px;
}
.chat-header-avatar {
  font-size: 20px;
}
.chat-header-title {
  font-weight: 700;
  font-size: 14px;
}
.chat-header-status {
  font-size: 10px;
  color: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  gap: 4px;
}
.status-dot {
  width: 6px;
  height: 6px;
  background: #10B981;
  border-radius: 50%;
  display: inline-block;
  animation: pulse-dot 2.5s infinite;
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.3); opacity: 0.6; }
}
.chat-header-actions {
  display: flex;
  gap: 8px;
}
.chat-action-btn, .chat-action-btnClose {
  background: none;
  border: none;
  color: white;
  opacity: 0.8;
  cursor: pointer;
  padding: 4px;
  font-size: 16px;
  transition: opacity 0.2s;
}
.chat-action-btn:hover, .chat-action-btnClose:hover {
  opacity: 1;
}

/* Zona de mensajes */
.chat-widget-messages {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  background: var(--bg-elevated);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Sugerencias */
.chat-widget-suggestions {
  display: flex;
  gap: 6px;
  padding: 8px 12px;
  overflow-x: auto;
  background: var(--bg-card);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
.chat-widget-suggestions::-webkit-scrollbar {
  height: 3px;
}
.chat-widget-suggestions button {
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}
.chat-widget-suggestions button:hover {
  background: var(--bg-hover);
  border-color: var(--primary);
  color: var(--primary);
}

/* Entrada de texto */
.chat-widget-input-row {
  padding: 10px 12px;
  display: flex;
  gap: 8px;
  align-items: center;
  background: var(--bg-card);
  border-top: 1px solid var(--border);
}
.chat-widget-input-row textarea {
  flex: 1;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 8px 14px;
  font-size: 13px;
  resize: none;
  height: 38px;
  line-height: 1.4;
  color: var(--text-primary);
  max-height: 80px;
}
.chat-widget-input-row textarea:focus {
  outline: none;
  border-color: var(--primary);
}
.chat-send-btn {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: transform 0.2s;
  flex-shrink: 0;
}
.chat-send-btn:hover {
  transform: scale(1.05);
}
.chat-mic-btn {
  background: var(--bg-elevated);
  color: var(--text-secondary);
  border: 1px solid var(--border);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: all 0.2s;
  flex-shrink: 0;
}
.chat-mic-btn.recording {
  background: #EF4444;
  color: white;
  border-color: #EF4444;
  animation: mic-pulse 1.5s infinite;
}
@keyframes mic-pulse {
  0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
  100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Gráfico inline en chat */
.chat-chart-container {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 8px;
  width: 100%;
  margin-top: 5px;
}
</style>

<!-- Botón Flotante Global del Asistente -->
<button id="global-chat-btn" class="global-chat-btn" onclick="toggleGlobalChat()" title="Asistente Virtual IA">
  <i class="bi bi-robot"></i>
  <span class="chat-btn-pulse"></span>
</button>

<!-- Ventana del Asistente Virtual Global -->
<div id="global-chat-widget" class="global-chat-widget">
  <!-- Cabecera -->
  <div class="chat-widget-header">
    <div class="chat-header-user">
      <div class="chat-header-avatar">🤖</div>
      <div>
        <div class="chat-header-title">Asistente IA FAXEL</div>
        <div class="chat-header-status">
          <span class="status-dot"></span> En línea
        </div>
      </div>
    </div>
    <div class="chat-header-actions">
      <button id="global-tts-toggle" class="chat-action-btn" onclick="toggleGlobalTTS()" title="Activar lectura de voz (TTS)">
        <i class="bi bi-volume-mute-fill"></i>
      </button>
      <button class="chat-action-btnClose" onclick="toggleGlobalChat()" title="Cerrar">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>

  <!-- Zona de Mensajes -->
  <div id="global-chat-messages" class="chat-widget-messages">
    <div class="chat-bubble assistant">
      👋 **¡Hola, <?= Security::e($user['nombre'] ?? 'Usuario') ?>!**<br>
      Soy tu copiloto de Inteligencia de Negocios en FAXEL. Pregúntame dudas por texto o usando el micrófono.<br><br>
      *Prueba a decir o escribir:*
      <ul style="margin: 5px 0 0 15px; padding: 0; line-height: 1.6;">
        <li>"Resume mi negocio"</li>
        <li>"Ventas del mes"</li>
        <li>"Clientes en riesgo"</li>
      </ul>
    </div>
  </div>

  <!-- Panel de Sugerencias Rápidas -->
  <div class="chat-widget-suggestions">
    <button onclick="sendGlobalSuggestion('¿Cuánto vendí este mes?')">💰 Ventas Mes</button>
    <button onclick="sendGlobalSuggestion('¿Ventas de hoy?')">📅 Ventas Hoy</button>
    <button onclick="sendGlobalSuggestion('¿Qué producto genera más utilidad?')">📦 Más Rentable</button>
    <button onclick="sendGlobalSuggestion('¿Quiénes dejarán de comprar?')">⚠️ Churn IA</button>
    <button onclick="sendGlobalSuggestion('Resume mi negocio')">📋 Resumen Negocio</button>
    <button onclick="sendGlobalSuggestion('Anuncios')">📢 Ver Alertas</button>
  </div>

  <!-- Barra de Entrada de Datos -->
  <div class="chat-widget-input-row">
    <textarea id="global-chat-input" placeholder="Pregunta algo..." rows="1" onkeydown="handleGlobalChatKey(event)"></textarea>
    
    <button id="global-chat-mic" class="chat-mic-btn" onclick="toggleGlobalRecording()" title="Enviar nota de voz (Whisper STT)">
      <i class="bi bi-mic-fill"></i>
    </button>
    
    <button id="global-chat-send" class="chat-send-btn" onclick="sendGlobalMessage()" title="Enviar">
      <i class="bi bi-send-fill"></i>
    </button>
  </div>
</div>

<!-- ══ SCRIPT DE CONTROL DEL ASISTENTE VIRTUAL GLOBAL ════ -->
<script>
let isGlobalChatOpen = false;
let isGlobalRecording = false;
let globalTTSActive = false;
let globalInputEl = null;
let globalMessagesEl = null;
const globalAppUrlBase = '<?= $config['url'] ?>';

document.addEventListener('DOMContentLoaded', () => {
  globalInputEl = document.getElementById('global-chat-input');
  globalMessagesEl = document.getElementById('global-chat-messages');

  if (globalInputEl) {
    globalInputEl.addEventListener('input', () => {
      globalInputEl.style.height = 'auto';
      globalInputEl.style.height = Math.min(globalInputEl.scrollHeight, 80) + 'px';
    });
  }
});

function toggleGlobalChat() {
  const widget = document.getElementById('global-chat-widget');
  isGlobalChatOpen = !isGlobalChatOpen;
  if (isGlobalChatOpen) {
    widget.classList.add('show');
    document.getElementById('global-chat-input').focus();
  } else {
    widget.classList.remove('show');
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  }
}

function toggleGlobalTTS() {
  const ttsBtn = document.getElementById('global-tts-toggle');
  globalTTSActive = !globalTTSActive;
  if (globalTTSActive) {
    ttsBtn.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
    ttsBtn.style.color = '#10B981';
    ttsBtn.title = "Desactivar lectura de voz";
  } else {
    ttsBtn.innerHTML = '<i class="bi bi-volume-mute-fill"></i>';
    ttsBtn.style.color = '';
    ttsBtn.title = "Activar lectura de voz (TTS)";
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  }
}

function speakGlobalText(text) {
  if (!globalTTSActive || !('speechSynthesis' in window)) return;
  window.speechSynthesis.cancel();
  
  let clean = text.replace(/<[^>]*>/g, '')
                  .replace(/\*\*([^*]+)\*\*/g, '$1')
                  .replace(/•/g, '')
                  .replace(/_/g, '');
  
  const utterance = new SpeechSynthesisUtterance(clean);
  utterance.lang = 'es-PE';
  
  const voices = window.speechSynthesis.getVoices();
  const esVoice = voices.find(v => v.lang.startsWith('es'));
  if (esVoice) utterance.voice = esVoice;
  
  window.speechSynthesis.speak(utterance);
}

function sendGlobalSuggestion(text) {
  if (globalInputEl) {
    globalInputEl.value = text;
    sendGlobalMessage();
  }
}

function handleGlobalChatKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendGlobalMessage();
  }
}

function appendGlobalBubble(rol, text) {
  const div = document.createElement('div');
  div.className = `chat-bubble ${rol}`;
  
  if (rol === 'assistant') {
    let formatted = text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/^• /gm, '&#x2022; ')
      .replace(/\n/g, '<br>');
    div.innerHTML = formatted;
  } else {
    div.textContent = text;
  }
  
  if (globalMessagesEl) {
    globalMessagesEl.appendChild(div);
    globalMessagesEl.scrollTop = globalMessagesEl.scrollHeight;
  }
  return div;
}

function appendGlobalTyping() {
  const div = document.createElement('div');
  div.className = 'chat-bubble assistant';
  div.id = 'global-typingIndicator';
  div.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  if (globalMessagesEl) {
    globalMessagesEl.appendChild(div);
    globalMessagesEl.scrollTop = globalMessagesEl.scrollHeight;
  }
}

function removeGlobalTyping() {
  document.getElementById('global-typingIndicator')?.remove();
}

async function sendGlobalMessage() {
  const text = globalInputEl.value.trim();
  if (!text) return;
  
  globalInputEl.value = '';
  globalInputEl.style.height = 'auto';
  appendGlobalBubble('user', text);
  appendGlobalTyping();
  
  const token = getCSRF();
  
  try {
    const res = await fetch(globalAppUrlBase + '/chat/enviar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ mensaje: text, _csrf: token }),
    });
    
    const data = await res.json();
    removeGlobalTyping();
    
    if (data.success) {
      appendGlobalBubble('assistant', data.respuesta);
      speakGlobalText(data.respuesta);
      
      if (data.grafico) {
        renderGlobalChatChart(data.grafico);
      }
    } else {
      appendGlobalBubble('assistant', '❌ ' + (data.error || 'Error al procesar consulta.'));
    }
  } catch (err) {
    removeGlobalTyping();
    appendGlobalBubble('assistant', '❌ Error de conexión con el asistente.');
  }
}

function renderGlobalChatChart(grafico) {
  const wrap = document.createElement('div');
  wrap.className = 'chat-bubble assistant chat-chart-container';
  
  const canvas = document.createElement('canvas');
  canvas.height = 100;
  wrap.appendChild(canvas);
  if (globalMessagesEl) {
    globalMessagesEl.appendChild(wrap);
  }
  
  const colors = ['#4F8EF7','#8B5CF6','#06B6D4','#10B981','#F59E0B'];
  
  new Chart(canvas.getContext('2d'), {
    type: grafico.tipo || 'bar',
    data: {
      labels: grafico.labels,
      datasets: [{
        label: grafico.label || 'Datos',
        data: grafico.data,
        backgroundColor: colors.map(c => c + 'BB'),
        borderColor: colors,
        borderWidth: 1,
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { grid: { color: 'rgba(255,255,255,0.04)' } },
        x: { grid: { display: false } }
      }
    }
  });
  
  if (globalMessagesEl) {
    globalMessagesEl.scrollTop = globalMessagesEl.scrollHeight;
  }
}

// ── WavRecorder para codificación directa en el Cliente (WAV 16kHz Mono) ──
class GlobalWavRecorder {
  constructor() {
    this.audioContext = null;
    this.processor = null;
    this.input = null;
    this.leftChannel = [];
    this.recordingLength = 0;
  }
  
  async start() {
    this.leftChannel = [];
    this.recordingLength = 0;
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    
    // Crear el AudioContext sin predefinir el sampleRate (usa el del hardware del dispositivo)
    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    this.input = this.audioContext.createMediaStreamSource(stream);
    
    this.processor = this.audioContext.createScriptProcessor(4096, 1, 1);
    this.processor.onaudioprocess = (e) => {
      const chunk = e.inputBuffer.getChannelData(0);
      this.leftChannel.push(new Float32Array(chunk));
      this.recordingLength += chunk.length;
    };
    
    this.input.connect(this.processor);
    this.processor.connect(this.audioContext.destination);
    this.stream = stream;
  }
  
  stop() {
    if (this.processor) {
      this.processor.disconnect();
      this.input.disconnect();
      this.stream.getTracks().forEach(track => track.stop());
      
      const inputSampleRate = this.audioContext.sampleRate;
      this.audioContext.close();
      
      const rawBuffer = new Float32Array(this.recordingLength);
      let offset = 0;
      for (let i = 0; i < this.leftChannel.length; i++) {
        rawBuffer.set(this.leftChannel[i], offset);
        offset += this.leftChannel[i].length;
      }
      
      // Downsampling dinámico de la frecuencia del hardware a 16000Hz (frecuencia ideal para IA)
      const targetSampleRate = 16000;
      const downsampledBuffer = this.downsample(rawBuffer, inputSampleRate, targetSampleRate);
      
      const buffer = new ArrayBuffer(44 + downsampledBuffer.length * 2);
      const view = new DataView(buffer);
      
      this.writeString(view, 0, 'RIFF');
      view.setUint32(4, 36 + downsampledBuffer.length * 2, true);
      this.writeString(view, 8, 'WAVE');
      this.writeString(view, 12, 'fmt ');
      view.setUint32(16, 16, true);
      view.setUint16(20, 1, true); // Raw PCM
      view.setUint16(22, 1, true); // Canal Mono
      view.setUint32(24, targetSampleRate, true);
      view.setUint32(28, targetSampleRate * 2, true);
      view.setUint16(32, 2, true);
      view.setUint16(34, 16, true);
      this.writeString(view, 36, 'data');
      view.setUint32(40, downsampledBuffer.length * 2, true);
      
      this.floatTo16BitPCM(view, 44, downsampledBuffer);
      
      return new Blob([view], { type: 'audio/wav' });
    }
    return null;
  }
  
  downsample(buffer, inputSampleRate, outputSampleRate) {
    if (inputSampleRate === outputSampleRate) {
      return buffer;
    }
    const sampleRateRatio = inputSampleRate / outputSampleRate;
    const newLength = Math.round(buffer.length / sampleRateRatio);
    const result = new Float32Array(newLength);
    let offsetResult = 0;
    let offsetBuffer = 0;
    while (offsetResult < result.length) {
      const nextOffsetBuffer = Math.round((offsetResult + 1) * sampleRateRatio);
      let accum = 0;
      let count = 0;
      for (let i = offsetBuffer; i < nextOffsetBuffer && i < buffer.length; i++) {
        accum += buffer[i];
        count++;
      }
      result[offsetResult] = count > 0 ? accum / count : 0;
      offsetResult++;
      offsetBuffer = nextOffsetBuffer;
    }
    return result;
  }
  
  floatTo16BitPCM(output, offset, input) {
    for (let i = 0; i < input.length; i++, offset += 2) {
      let s = Math.max(-1, Math.min(1, input[i]));
      output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
    }
  }
  
  writeString(view, offset, string) {
    for (let i = 0; i < string.length; i++) {
      view.setUint8(offset + i, string.charCodeAt(i));
    }
  }
}

const globalWavRecorder = new GlobalWavRecorder();

async function toggleGlobalRecording() {
  const micBtn = document.getElementById('global-chat-mic');
  isGlobalRecording = !isGlobalRecording;
  
  if (isGlobalRecording) {
    micBtn.classList.add('recording');
    micBtn.innerHTML = '<i class="bi bi-stop-fill"></i>';
    try {
      await globalWavRecorder.start();
    } catch (err) {
      console.error('Error al acceder al micrófono:', err);
      alert('No se pudo acceder al micrófono. Por favor, concede los permisos correspondientes.');
      micBtn.classList.remove('recording');
      micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
      isGlobalRecording = false;
    }
  } else {
    micBtn.classList.remove('recording');
    micBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    micBtn.disabled = true;
    
    const audioBlob = globalWavRecorder.stop();
    if (audioBlob) {
      await transcribirAudioGlobal(audioBlob);
    } else {
      micBtn.disabled = false;
      micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
    }
  }
}

async function transcribirAudioGlobal(blob) {
  const micBtn = document.getElementById('global-chat-mic');
  const token = getCSRF();
  const formData = new FormData();
  formData.append('audio', blob, 'audio.wav');
  formData.append('_csrf', token);
  
  try {
    const res = await fetch(globalAppUrlBase + '/chat/transcribir', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.success && data.texto) {
      globalInputEl.value = data.texto;
      sendGlobalMessage();
    } else {
      alert('Error de transcripción: ' + (data.error || 'No se pudo transcribir el audio.'));
    }
  } catch (err) {
    console.error(err);
    alert('Fallo de conexión al transcribir el audio.');
  } finally {
    micBtn.disabled = false;
    micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
  }
}
</script>

<?php if (isset($pageScript)): ?>
<script><?= $pageScript ?></script>
<?php endif; ?>

</body>
</html>
