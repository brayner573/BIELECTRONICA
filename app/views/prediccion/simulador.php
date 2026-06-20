
<div class="row mb-4 animate-in">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);margin:0;">Simulador de Escenarios "What-If"</h2>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Ajusta variables operativas para simular proyecciones comerciales de los próximos 30 días con IA.</p>
    </div>
    <a href="<?= $config['url'] ?>/prediccion" class="btn-bi secondary sm">
      <i class="bi bi-arrow-left"></i> Volver a Predicciones
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Controles del Simulador -->
  <div class="col-xl-4 col-lg-5 animate-in">
    <div class="card-glass p-4" style="background:var(--bg-card); border-color:var(--border-accent);">
      <h3 style="font-size:16px; font-weight:800; color:var(--text-heading); margin-bottom:20px; border-left:3px solid var(--primary); padding-left:10px;">
        ⚙️ Variables del Escenario
      </h3>
      
      <form id="simulatorForm" onsubmit="ejecutarSimulacion(event)">
        <!-- Slider 1: Precios -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-1">
            <label class="form-label-bi mb-0" for="precio"><i class="bi bi-tag" style="color:var(--primary);"></i> Ajuste de Precios</label>
            <span id="val_precio" style="font-weight:700; font-size:13px; color:var(--primary);">0%</span>
          </div>
          <input type="range" id="precio" name="precio" min="-20" max="20" value="0" class="w-full cursor-pointer" style="accent-color:var(--primary);" oninput="updateLabel('precio', '%')">
          <div class="flex justify-between text-xs text-muted mt-1">
            <span>-20% (Descuento)</span>
            <span>+20% (Incremento)</span>
          </div>
        </div>

        <!-- Slider 2: Marketing -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-1">
            <label class="form-label-bi mb-0" for="marketing"><i class="bi bi-megaphone" style="color:var(--primary);"></i> Inversión en Marketing</label>
            <span id="val_marketing" style="font-weight:700; font-size:13px; color:var(--primary);">0%</span>
          </div>
          <input type="range" id="marketing" name="marketing" min="-50" max="100" value="0" class="w-full cursor-pointer" style="accent-color:var(--primary);" oninput="updateLabel('marketing', '%')">
          <div class="flex justify-between text-xs text-muted mt-1">
            <span>-50% (Reducción)</span>
            <span>+100% (Duplicar)</span>
          </div>
        </div>

        <!-- Slider 3: Descuentos -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-1">
            <label class="form-label-bi mb-0" for="descuento"><i class="bi bi-percent" style="color:var(--primary);"></i> Tasa de Promociones</label>
            <span id="val_descuento" style="font-weight:700; font-size:13px; color:var(--primary);">0%</span>
          </div>
          <input type="range" id="descuento" name="descuento" min="-10" max="30" value="0" class="w-full cursor-pointer" style="accent-color:var(--primary);" oninput="updateLabel('descuento', '%')">
          <div class="flex justify-between text-xs text-muted mt-1">
            <span>-10% (Menos promos)</span>
            <span>+30% (Agresiva)</span>
          </div>
        </div>

        <!-- Slider 4: Fuerza de ventas -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-1">
            <label class="form-label-bi mb-0" for="ventas"><i class="bi bi-people" style="color:var(--primary);"></i> Capacidad Fuerza Ventas</label>
            <span id="val_ventas" style="font-weight:700; font-size:13px; color:var(--primary);">0%</span>
          </div>
          <input type="range" id="ventas" name="ventas" min="-20" max="50" value="0" class="w-full cursor-pointer" style="accent-color:var(--primary);" oninput="updateLabel('ventas', '%')">
          <div class="flex justify-between text-xs text-muted mt-1">
            <span>-20% (Recorte personal)</span>
            <span>+50% (Expansión)</span>
          </div>
        </div>

        <button type="submit" class="btn-bi primary w-full mt-2" id="btnSimular" style="height:48px; justify-content:center; font-size:15px;">
          <i class="bi bi-play-circle-fill"></i> Simular Escenario Comercial
        </button>
      </form>
    </div>
  </div>

  <!-- Resultados de Simulación -->
  <div class="col-xl-8 col-lg-7 animate-in stagger-1">
    <!-- Fila de KPI comparativos -->
    <div class="row g-3 mb-4" id="kpisSimulacion" style="display:none;">
      
      <!-- Ventas -->
      <div class="col-md-4">
        <div class="kpi-card" style="background:#FFFFFF;">
          <div class="kpi-label">Ingresos Proyectados</div>
          <div style="display:flex; align-items:baseline; gap:8px;">
            <div class="kpi-value" id="kpi_ventas_sim" style="font-size:20px; color:#1A202C;">S/ 0</div>
            <div style="font-size:12px; color:var(--text-muted);" id="kpi_ventas_base">S/ 0 (Base)</div>
          </div>
          <div class="kpi-change" id="kpi_ventas_diff" style="font-size:12px; font-weight:700;">+0.0%</div>
        </div>
      </div>

      <!-- Margen -->
      <div class="col-md-4">
        <div class="kpi-card" style="background:#FFFFFF;">
          <div class="kpi-label">Margen Estimado</div>
          <div style="display:flex; align-items:baseline; gap:8px;">
            <div class="kpi-value" id="kpi_margen_sim" style="font-size:20px; color:#1A202C;">0%</div>
            <div style="font-size:12px; color:var(--text-muted);" id="kpi_margen_base">0% (Base)</div>
          </div>
          <div class="kpi-change" id="kpi_margen_diff" style="font-size:12px; font-weight:700;">+0.0%</div>
        </div>
      </div>

      <!-- Utilidad -->
      <div class="col-md-4">
        <div class="kpi-card" style="background:#FFFFFF;">
          <div class="kpi-label">Utilidad Proyectada</div>
          <div style="display:flex; align-items:baseline; gap:8px;">
            <div class="kpi-value" id="kpi_utilidad_sim" style="font-size:20px; color:#38A169;">S/ 0</div>
            <div style="font-size:12px; color:var(--text-muted);" id="kpi_utilidad_base">S/ 0 (Base)</div>
          </div>
          <div class="kpi-change" id="kpi_utilidad_diff" style="font-size:12px; font-weight:700;">+0.0%</div>
        </div>
      </div>

    </div>

    <!-- Panel de gráfico -->
    <div class="chart-wrapper h-100" style="min-height:360px;">
      <div class="chart-title">📊 Proyección Comparativa a 30 Días</div>
      <div class="chart-subtitle">Línea Roja = Proyección Base (Prophet) | Línea Dorada/Naranja = Proyección con Cambios Operativos</div>
      
      <!-- Loader de simulación -->
      <div id="simLoader" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:240px; color:var(--text-muted);">
        <i class="bi bi-sliders" style="font-size:48px; color:var(--primary); animation: pulse-badge 2s infinite;"></i>
        <p class="mt-3" style="font-weight:600;">Modifica las variables y presiona "Simular" para proyectar los resultados.</p>
      </div>

      <div id="chartContainer" style="display:none; position:relative; height:280px; width:100%;">
        <canvas id="chartSimulacion"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
let chartSim = null;

function updateLabel(id, suffix) {
  const input = document.getElementById(id);
  const label = document.getElementById('val_' + id);
  const val = parseInt(input.value);
  label.textContent = (val > 0 ? '+' : '') + val + suffix;
}

async function ejecutarSimulacion(e) {
  if (e) e.preventDefault();
  
  const form = document.getElementById('simulatorForm');
  const btn = document.getElementById('btnSimular');
  const loader = document.getElementById('simLoader');
  const chartContainer = document.getElementById('chartContainer');
  const kpisPanel = document.getElementById('kpisSimulacion');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Simulando...';
  
  const formData = new FormData(form);
  formData.append('_csrf', getCSRF());

  try {
    const res = await fetch('<?= $config['url'] ?>/prediccion/simular', {
      method: 'POST',
      body: new URLSearchParams(formData)
    });
    const data = await res.json();
    
    if (data.success && data.data) {
      const sim = data.data;
      
      // Ocultar loader y mostrar paneles
      loader.style.display = 'none';
      chartContainer.style.display = 'block';
      kpisPanel.style.display = 'flex';
      
      // Rellenar KPI Cards
      document.getElementById('kpi_ventas_sim').textContent = 'S/ ' + Math.round(sim.ventas_simulada).toLocaleString('es-PE');
      document.getElementById('kpi_ventas_base').textContent = 'S/ ' + Math.round(sim.ventas_base).toLocaleString('es-PE') + ' (Base)';
      
      const vDiff = ((sim.ventas_simulada - sim.ventas_base) / sim.ventas_base) * 100;
      const vDiffEl = document.getElementById('kpi_ventas_diff');
      vDiffEl.textContent = (vDiff >= 0 ? '+' : '') + vDiff.toFixed(1) + '%';
      vDiffEl.className = 'kpi-change ' + (vDiff >= 0 ? 'up' : 'down');
      vDiffEl.style.color = vDiff >= 0 ? '#38A169' : '#E53E3E';

      document.getElementById('kpi_margen_sim').textContent = sim.margen_simulada_pct + '%';
      document.getElementById('kpi_margen_base').textContent = sim.margen_base_pct + '% (Base)';
      
      const mDiff = sim.margen_simulada_pct - sim.margen_base_pct;
      const mDiffEl = document.getElementById('kpi_margen_diff');
      mDiffEl.textContent = (mDiff >= 0 ? '+' : '') + mDiff.toFixed(1) + ' pts';
      mDiffEl.className = 'kpi-change ' + (mDiff >= 0 ? 'up' : 'down');
      mDiffEl.style.color = mDiff >= 0 ? '#38A169' : '#E53E3E';

      document.getElementById('kpi_utilidad_sim').textContent = 'S/ ' + Math.round(sim.utilidad_simulada).toLocaleString('es-PE');
      document.getElementById('kpi_utilidad_base').textContent = 'S/ ' + Math.round(sim.utilidad_base).toLocaleString('es-PE') + ' (Base)';
      
      const uDiff = ((sim.utilidad_simulada - sim.utilidad_base) / sim.utilidad_base) * 100;
      const uDiffEl = document.getElementById('kpi_utilidad_diff');
      uDiffEl.textContent = (uDiff >= 0 ? '+' : '') + uDiff.toFixed(1) + '%';
      uDiffEl.className = 'kpi-change ' + (uDiff >= 0 ? 'up' : 'down');
      uDiffEl.style.color = uDiff >= 0 ? '#38A169' : '#E53E3E';

      // Dibujar gráfico comparativo
      renderChart(sim.diario_base, sim.diario_simulada);
      
    } else {
      alert('Error en simulación: ' + (data.message || 'Error desconocido'));
    }
  } catch(err) {
    console.error(err);
    alert('Error al conectar con el motor de simulación.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> Simular Escenario Comercial';
  }
}

function renderChart(baseData, simData) {
  const ctx = document.getElementById('chartSimulacion').getContext('2d');
  
  if (chartSim) {
    chartSim.destroy();
  }

  const labels = baseData.map(d => {
    const f = new Date(d.ds + 'T00:00:00');
    return f.toLocaleDateString('es-PE', {day:'2-digit', month:'short'});
  });

  chartSim = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Proyección Base (S/)',
          data: baseData.map(d => parseFloat(d.yhat)),
          borderColor: '#E53E3E',
          backgroundColor: 'rgba(229, 62, 62, 0.05)',
          borderWidth: 2,
          pointRadius: 2,
          fill: true,
          tension: 0.4
        },
        {
          label: 'Proyección Simulada (S/)',
          data: simData.map(d => parseFloat(d.yhat)),
          borderColor: '#F59E0B',
          backgroundColor: 'rgba(245, 158, 11, 0.05)',
          borderWidth: 2,
          pointRadius: 2,
          fill: true,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: ctx => ` S/ ${(ctx.raw||0).toLocaleString('es-PE', {minimumFractionDigits:0})}`
          }
        }
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          grid: { color: 'rgba(0,0,0,0.06)' },
          ticks: { callback: v => 'S/ ' + (v/1000).toFixed(0) + 'K' }
        }
      }
    }
  });
}
</script>
