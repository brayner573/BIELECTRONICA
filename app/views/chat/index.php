<?php
$user = Session::get('user');
$initMessages = json_encode($mensajes ?? []);
?>
<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">

<!-- ── Sugerencias de consulta ────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card-glass p-3 animate-in">
      <div class="text-xs text-muted mb-2 font-bold" style="text-transform:uppercase;letter-spacing:0.6px;">
        💡 Consultas sugeridas — haz clic para usar
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php
        $sugerencias = [
          '¿Cuánto vendí este mes?',
          '¿Ventas de hoy?',
          '¿Qué sucursal cayó más?',
          '¿Qué producto genera más utilidad?',
          '¿Quiénes dejarán de comprar?',
          'Resume mi negocio',
          '¿Cuál es mi rentabilidad?',
          '¿Cuáles son mis mejores clientes?',
        ];
        foreach ($sugerencias as $s):
        ?>
        <button class="btn-bi secondary sm" onclick="usarSugerencia(<?= json_encode($s) ?>)">
          <?= Security::e($s) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Chat Container ─────────────────────────────────────── -->
<div class="row g-3">
  <div class="col-xl-8 animate-in">
    <div class="chat-container">

      <!-- Header del chat -->
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">🤖</div>
          <div>
            <div style="font-weight:700;font-size:15px;">Asistente IA FAXEL</div>
            <div style="font-size:11px;color:var(--success);display:flex;align-items:center;gap:5px;">
              <span style="width:7px;height:7px;background:var(--success);border-radius:50%;display:inline-block;animation:pulse-badge 2s infinite;"></span>
              En línea — Listo para consultas
            </div>
          </div>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="<?= $config['url'] ?>/chat/nueva" class="btn-bi secondary sm" title="Nueva conversación">
            <i class="bi bi-plus-circle"></i> Nueva sesión
          </a>
        </div>
      </div>

      <!-- Mensajes -->
      <div class="chat-messages" id="chatMessages">

        <!-- Mensaje de bienvenida -->
        <?php if (empty($mensajes)): ?>
        <div class="chat-bubble assistant">
          <div style="margin-bottom:8px;">👋 <strong>¡Hola, <?= Security::e($user['nombre'] ?? 'Usuario') ?>!</strong></div>
          <div>Soy tu asistente de Business Intelligence. Puedo analizar tus datos en tiempo real y responder preguntas sobre:</div>
          <ul style="margin:10px 0 0 16px;line-height:2;">
            <li>📊 Ventas y métricas de negocio</li>
            <li>🏢 Rendimiento por sucursal</li>
            <li>📦 Rentabilidad de productos</li>
            <li>👥 Riesgo de abandono de clientes</li>
            <li>📈 Tendencias y predicciones</li>
          </ul>
          <div style="margin-top:10px;">¿Qué deseas consultar?</div>
        </div>
        <?php else: ?>
        <!-- Historial de mensajes -->
        <?php foreach ($mensajes as $msg): ?>
        <div class="chat-bubble <?= $msg['rol'] === 'user' ? 'user' : 'assistant' ?>">
          <?php if ($msg['rol'] === 'assistant'): ?>
            <?= nl2br(Security::e($msg['mensaje'])) ?>
          <?php else: ?>
            <?= Security::e($msg['mensaje']) ?>
          <?php endif; ?>
        </div>
        <?php if (!empty($msg['grafico_data'])): ?>
        <div class="chat-bubble assistant" style="width:100%;max-width:95%;">
          <canvas class="chat-chart" data-chart='<?= htmlspecialchars($msg['grafico_data']) ?>' height="100"></canvas>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <!-- Input -->
      <div class="chat-input-row">
        <textarea
          id="chatInput"
          class="chat-input"
          placeholder="Escribe tu pregunta... (Enter para enviar, Shift+Enter para nueva línea)"
          rows="1"
          onkeydown="handleChatKey(event)"
        ></textarea>
        <button id="sendBtn" class="btn-bi primary" onclick="enviarMensaje()" style="height:44px;min-width:100px;">
          <i class="bi bi-send"></i> Enviar
        </button>
      </div>

    </div>
  </div>

  <!-- Panel lateral -->
  <div class="col-xl-4">

    <!-- KPIs rápidos -->
    <div class="chart-wrapper mb-3 animate-in stagger-1">
      <div class="chart-title mb-3">📊 Datos en Vivo</div>
      <?php
        $db = Database::getInstance();
        $ventasMes = $db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas WHERE YEAR(fecha_venta)=YEAR(CURDATE()) AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'")->fetchColumn();
        $ventasHoy = $db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_venta)=CURDATE() AND estado='completada'")->fetchColumn();
        $alertas   = $db->query("SELECT COUNT(*) FROM alertas WHERE estado='nueva'")->fetchColumn();
        $churnAlto = $db->query("SELECT COUNT(*) FROM clientes WHERE churn_riesgo='alto' AND activo=1")->fetchColumn();
      ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div style="background:var(--bg-elevated);border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:12px;color:var(--text-muted);">Ventas del mes</span>
          <span style="font-weight:700;color:var(--primary);">S/ <?= number_format($ventasMes,0) ?></span>
        </div>
        <div style="background:var(--bg-elevated);border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:12px;color:var(--text-muted);">Ventas de hoy</span>
          <span style="font-weight:700;color:var(--success);">S/ <?= number_format($ventasHoy,0) ?></span>
        </div>
        <div style="background:var(--bg-elevated);border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:12px;color:var(--text-muted);">Alertas activas</span>
          <span style="font-weight:700;color:var(--warning);"><?= $alertas ?></span>
        </div>
        <div style="background:var(--bg-elevated);border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:12px;color:var(--text-muted);">Clientes riesgo alto</span>
          <span style="font-weight:700;color:var(--danger);"><?= $churnAlto ?></span>
        </div>
      </div>
    </div>

    <!-- Tips -->
    <div class="chart-wrapper animate-in stagger-2">
      <div class="chart-title mb-3">💡 Ejemplos de preguntas</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php
        $ejemplos = [
          ['texto' => '¿Cuánto vendí este mes?', 'icon' => '💰', 'cat' => 'Ventas'],
          ['texto' => '¿Qué sucursal cayó más?', 'icon' => '🏢', 'cat' => 'Sucursales'],
          ['texto' => '¿Qué producto genera más utilidad?', 'icon' => '📦', 'cat' => 'Productos'],
          ['texto' => '¿Quiénes dejarán de comprar?', 'icon' => '👥', 'cat' => 'Clientes'],
          ['texto' => 'Resume mi negocio', 'icon' => '📋', 'cat' => 'Resumen'],
          ['texto' => '¿Cuál es mi rentabilidad?', 'icon' => '📈', 'cat' => 'Márgenes'],
        ];
        foreach ($ejemplos as $e):
        ?>
        <div onclick="usarSugerencia(<?= json_encode($e['texto']) ?>)"
             style="background:var(--bg-elevated);border-radius:8px;padding:10px 12px;cursor:pointer;transition:var(--transition);display:flex;gap:10px;align-items:center;border:1px solid transparent;"
             onmouseenter="this.style.borderColor='rgba(79,142,247,0.4)'"
             onmouseleave="this.style.borderColor='transparent'">
          <span style="font-size:16px;"><?= $e['icon'] ?></span>
          <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;"><?= $e['cat'] ?></div>
            <div style="font-size:13px;color:var(--text-primary);"><?= Security::e($e['texto']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<script>
const BASE_URL  = '<?= $config['url'] ?>';
let CSRF;
let messagesEl;
let inputEl;
let sendBtn;

document.addEventListener('DOMContentLoaded', () => {
  CSRF = getCSRF();
  messagesEl = document.getElementById('chatMessages');
  inputEl    = document.getElementById('chatInput');
  sendBtn    = document.getElementById('sendBtn');

  /* Scroll al final al cargar */
  if (messagesEl) {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  /* Auto-resize textarea */
  if (inputEl) {
    inputEl.addEventListener('input', () => {
      inputEl.style.height = 'auto';
      inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
    });
  }
});

function handleChatKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    enviarMensaje();
  }
}

function usarSugerencia(texto) {
  if (inputEl) {
    inputEl.value = texto;
    inputEl.focus();
  }
}

function appendBubble(rol, texto) {
  const div  = document.createElement('div');
  div.className = `chat-bubble ${rol}`;

  if (rol === 'assistant') {
    // Formato markdown básico
    texto = texto
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/^• /gm, '&#x2022; ')
      .replace(/\n/g, '<br>');
    div.innerHTML = texto;
  } else {
    div.textContent = texto;
  }

  if (messagesEl) {
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }
  return div;
}

function appendTyping() {
  const div = document.createElement('div');
  div.className = 'chat-bubble assistant';
  div.id = 'typingIndicator';
  div.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function removeTyping() {
  document.getElementById('typingIndicator')?.remove();
}

async function enviarMensaje() {
  const texto = inputEl.value.trim();
  if (!texto || sendBtn.disabled) return;

  // Mostrar mensaje del usuario
  appendBubble('user', texto);
  inputEl.value = '';
  inputEl.style.height = 'auto';
  sendBtn.disabled = true;
  sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

  // Indicador de escritura
  appendTyping();

  try {
    const res = await fetch(BASE_URL + '/chat/enviar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ mensaje: texto, _csrf: CSRF }),
    });

    const data = await res.json();
    removeTyping();

    if (data.success) {
      appendBubble('assistant', data.respuesta);

      // Gráfico si viene
      if (data.grafico) {
        renderChatChart(data.grafico);
      }
    } else {
      appendBubble('assistant', '❌ ' + (data.error || 'Error al procesar la consulta.'));
    }

  } catch (err) {
    removeTyping();
    appendBubble('assistant', '❌ Error de conexión. Verifica que el servidor esté activo.');
  } finally {
    sendBtn.disabled = false;
    sendBtn.innerHTML = '<i class="bi bi-send"></i> Enviar';
  }
}

function renderChatChart(grafico) {
  const wrap = document.createElement('div');
  wrap.className = 'chat-bubble assistant';
  wrap.style.width = '100%';
  wrap.style.maxWidth = '95%';

  const canvas = document.createElement('canvas');
  canvas.height = 80;
  wrap.appendChild(canvas);
  messagesEl.appendChild(wrap);

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

  messagesEl.scrollTop = messagesEl.scrollHeight;
}
</script>
