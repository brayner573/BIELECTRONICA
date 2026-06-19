<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">

<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);">Entrenamiento del Motor de IA</h2>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Entrena modelos de Machine Learning reales (Prophet, RandomForest, XGBoost) con tus propios datasets.</p>
    </div>
    <a href="<?= $config['url'] ?>/prediccion" class="btn-bi secondary sm">
      <i class="bi bi-arrow-left"></i> Volver a Predicciones
    </a>
  </div>
</div>

<div class="row">
  <!-- Cargar Dataset -->
  <div class="col-lg-5 mb-4">
    <div class="card-bi h-100">
      <h3 style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:20px;">Subir Dataset de Entrenamiento</h3>
      
      <form id="trainingForm" enctype="multipart/form-data">
        <div class="mb-4">
          <label class="form-label-bi" for="tipo_modelo">Objetivo del Modelo</label>
          <select id="tipo_modelo" name="tipo_modelo" class="input-bi" onchange="mostrarRequisitos()">
            <option value="ventas">Predicción de Ventas (Series Temporales)</option>
            <option value="churn">Scoring de Churn (Abandono de Clientes)</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label-bi" for="file">Dataset (.CSV o .XLSX)</label>
          <input type="file" id="file" name="file" class="input-bi" accept=".csv, .xlsx, .xls" required>
        </div>

        <!-- Requisitos del Formato -->
        <div id="requisitos-box" class="mb-4" style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:10px;padding:15px;font-size:12px;">
          <!-- Cargado vía JS -->
        </div>

        <button type="button" onclick="ejecutarEntrenamiento()" class="btn-bi primary w-full" id="btnTrain" style="height:48px;justify-content:center;font-size:15px;">
          <i class="bi bi-cpu"></i>
          <span>Iniciar Entrenamiento ML</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Historial de Modelos -->
  <div class="col-lg-7 mb-4">
    <div class="card-bi h-100">
      <h3 style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:20px;">Historial de Modelos Entrenados</h3>

      <div class="table-responsive">
        <table class="table-bi">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Modelo</th>
              <th>Algoritmo</th>
              <th>Métricas de Precisión</th>
              <th style="text-align:center;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($modelos)): ?>
            <tr>
              <td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px 0;">
                <i class="bi bi-clock-history" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                No hay modelos entrenados previamente para esta empresa. El sistema usa los modelos base por defecto.
              </td>
            </tr>
            <?php else: ?>
              <?php foreach ($modelos as $m): ?>
              <?php $met = json_decode($m['metricas_json'], true); ?>
              <tr>
                <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                <td>
                  <strong style="text-transform:capitalize;"><?= $m['tipo_modelo'] === 'ventas' ? 'Ventas' : 'Churn' ?></strong>
                </td>
                <td><span class="badge-bi info" style="text-transform:uppercase;"><?= Security::e($m['algoritmo']) ?></span></td>
                <td style="font-size:11px;font-family:monospace;color:var(--text-muted);">
                  R²: <?= $met['r2'] ?? '0' ?>% | MAE: <?= $met['mae'] ?? '0' ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($m['activo']): ?>
                    <span class="badge-bi success"><i class="bi bi-check-circle"></i> Activo</span>
                  <?php else: ?>
                    <span class="badge-bi info" style="opacity: 0.5;">Inactivo</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function mostrarRequisitos() {
  const tipo = document.getElementById('tipo_modelo').value;
  const box = document.getElementById('requisitos-box');
  
  if (tipo === 'ventas') {
    box.innerHTML = `
      <h6 style="font-weight:700;color:var(--primary);margin-bottom:8px;"><i class="bi bi-info-circle"></i> Estructura del Dataset de Ventas</h6>
      <p style="margin-bottom:8px;color:var(--text-muted);">El archivo debe contener el historial de ventas diarias con las siguientes columnas:</p>
      <ul style="padding-left:20px;margin-bottom:0;color:var(--text-muted);line-height:1.6;">
        <li><strong>ds</strong>: Fecha en formato <code style="color:var(--primary);">YYYY-MM-DD</code></li>
        <li><strong>y</strong>: Valor total de la venta en decimal/número.</li>
      </ul>
    `;
  } else {
    box.innerHTML = `
      <h6 style="font-weight:700;color:var(--primary);margin-bottom:8px;"><i class="bi bi-info-circle"></i> Estructura del Dataset de Churn</h6>
      <p style="margin-bottom:8px;color:var(--text-muted);">El archivo debe contener la segmentación histórica de clientes con las columnas:</p>
      <ul style="padding-left:20px;margin-bottom:0;color:var(--text-muted);line-height:1.6;">
        <li><strong>dias_sin_compra</strong>: Días de inactividad.</li>
        <li><strong>total_compras</strong>: Transacciones totales del cliente.</li>
        <li><strong>ticket_promedio</strong>: Monto promedio de compra.</li>
        <li><strong>monto_acumulado</strong>: Ventas totales acumuladas.</li>
        <li><strong>churn</strong>: Target (<code style="color:var(--primary);">1</code> si abandonó, <code style="color:var(--primary);">0</code> si sigue activo).</li>
      </ul>
    `;
  }
}

// Iniciar requisitos
mostrarRequisitos();

function ejecutarEntrenamiento() {
  const btn = document.getElementById('btnTrain');
  const fileInput = document.getElementById('file');
  const tipoModelo = document.getElementById('tipo_modelo').value;
  
  if (fileInput.files.length === 0) {
    alert('Debe seleccionar un archivo para el entrenamiento.');
    return;
  }
  
  const file = fileInput.files[0];
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  
  const formData = new FormData();
  formData.append('_csrf', token);
  formData.append('tipo_modelo', tipoModelo);
  formData.append('file', file);
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Entrenando modelo de ML real...';
  
  fetch('<?= $config['url'] ?>/prediccion/entrenamiento', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`🎉 Modelo ML entrenado con éxito usando el algoritmo: ${data.algoritmo.toUpperCase()}.\nMétricas de precisión:\nR²: ${data.metricas.r2}% | MAE: ${data.metricas.mae}`);
      location.reload();
    } else {
      alert('Error en entrenamiento:\n' + data.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cpu"></i><span>Iniciar Entrenamiento ML</span>';
    }
  })
  .catch(err => {
    console.error(err);
    alert('Ocurrió un error inesperado al entrenar el modelo de Machine Learning.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cpu"></i><span>Iniciar Entrenamiento ML</span>';
  });
}
</script>
