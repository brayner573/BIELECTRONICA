
<div class="row mb-4">
  <div class="col-12">
    <a href="<?= $config['url'] ?>/facturas" class="btn-bi secondary sm mb-3">
      <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);">Emitir Factura Electrónica</h2>
    <p style="color:var(--text-muted);font-size:13px;margin:0;">Genera un comprobante de pago UBL 2.1 firmado y enviado a SUNAT.</p>
  </div>
</div>

<div class="row">
  <!-- Panel de Formulario -->
  <div class="col-lg-8">
    <div class="card-bi mb-4">
      <h3 style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:20px;">Cabecera del CPE</h3>
      
      <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label-bi" for="cliente_id">Seleccionar Cliente</label>
          <select id="cliente_id" name="cliente_id" class="input-bi" required>
            <option value="">-- Seleccione Cliente --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= $c['id'] ?>"><?= Security::e($c['razon_social']) ?> (<?= $c['ruc_dni'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
          <label class="form-label-bi" for="tipo_cpe">Tipo de Comprobante</label>
          <select id="tipo_cpe" name="tipo_cpe" class="input-bi">
            <option value="01">Factura (RUC)</option>
            <option value="03">Boleta (DNI/Otros)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label-bi">Número Proyectado</label>
          <input type="text" class="input-bi" value="<?= $serie ?>-<?= str_pad($correlativo, 8, '0', STR_PAD_LEFT) ?>" disabled style="opacity: 0.6;">
        </div>
      </div>

      <hr style="border-color:var(--border);margin:25px 0;">

      <h3 style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;justify-content:between;align-items:center;">
        <span>Ítems / Detalle de Venta</span>
        <button type="button" class="btn-bi primary sm" onclick="agregarFila()" style="padding:4px 10px;font-size:12px;">
          <i class="bi bi-plus"></i> Agregar Fila
        </button>
      </h3>

      <div class="table-responsive">
        <table class="table-bi" id="itemsTable">
          <thead>
            <tr>
              <th style="width:40%;">Producto</th>
              <th style="width:15%;">Cantidad</th>
              <th style="width:20%;">Precio Unitario (S/)</th>
              <th style="width:20%;">Total (S/)</th>
              <th style="width:5%;text-align:center;">Acción</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            <!-- Filas dinámicas -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Panel Desglose Totales -->
  <div class="col-lg-4">
    <div class="card-bi mb-4" style="position:sticky;top:20px;">
      <h3 style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:10px;">Resumen de Totales</h3>
      
      <div style="display:flex;justify-content:between;margin-bottom:12px;font-size:14px;color:var(--text-muted);">
        <span>Operaciones Gravadas:</span>
        <span id="txt-gravada">S/ 0.00</span>
      </div>
      <div style="display:flex;justify-content:between;margin-bottom:12px;font-size:14px;color:var(--text-muted);">
        <span>IGV (18%):</span>
        <span id="txt-igv">S/ 0.00</span>
      </div>
      
      <hr style="border-color:var(--border);margin:15px 0;">
      
      <div style="display:flex;justify-content:between;margin-bottom:25px;font-size:18px;font-weight:800;color:var(--text-primary);">
        <span>Importe Total:</span>
        <span id="txt-total" style="color:var(--primary);">S/ 0.00</span>
      </div>

      <button type="button" onclick="procesarEmision()" class="btn-bi primary w-full" id="btnEmitir" style="height:48px;justify-content:center;font-size:15px;">
        <i class="bi bi-cloud-arrow-up"></i>
        <span>Emitir Comprobante</span>
      </button>
    </div>
  </div>
</div>

<!-- Template para Productos en Javascript -->
<script>
const arrayProductos = <?= json_encode($productos) ?>;

// Agregar la primera fila de forma automática al iniciar
window.onload = function() {
  agregarFila();
};

function agregarFila() {
  const tbody = document.getElementById('itemsBody');
  const index = tbody.children.length;
  
  let options = '<option value="">-- Seleccione --</option>';
  arrayProductos.forEach(p => {
    options += `<option value="${p.id}" data-precio="${p.precio_venta}" data-stock="${p.stock}" data-codigo="${p.codigo}">${p.nombre} (Stock: ${p.stock})</option>`;
  });
  
  const tr = document.createElement('tr');
  tr.id = `fila-${index}`;
  tr.innerHTML = `
    <td>
      <select name="items[${index}][producto_id]" class="input-bi select-prod" onchange="seleccionarProducto(${index}, this)" required>
        ${options}
      </select>
    </td>
    <td>
      <input type="number" name="items[${index}][cantidad]" class="input-bi input-cant" min="1" step="1" value="1" oninput="calcularFila(${index})" required>
    </td>
    <td>
      <input type="number" class="input-bi input-precio" step="0.01" value="0.00" oninput="calcularFila(${index})" required>
    </td>
    <td style="vertical-align:middle;font-weight:700;">
      S/ <span class="txt-subtotal-fila">0.00</span>
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <button type="button" class="btn-bi danger sm" onclick="eliminarFila(${index})" style="padding:4px 8px;"><i class="bi bi-trash"></i></button>
    </td>
  `;
  
  tbody.appendChild(tr);
}

function eliminarFila(index) {
  const fila = document.getElementById(`fila-${index}`);
  if (fila) {
    fila.remove();
    recalcularTotales();
  }
}

function seleccionarProducto(index, select) {
  const option = select.options[select.selectedIndex];
  const precio = option.getAttribute('data-precio') || 0;
  const fila = document.getElementById(`fila-${index}`);
  
  fila.querySelector('.input-precio').value = parseFloat(precio).toFixed(2);
  calcularFila(index);
}

function calcularFila(index) {
  const fila = document.getElementById(`fila-${index}`);
  const cant = parseFloat(fila.querySelector('.input-cant').value) || 0;
  const precio = parseFloat(fila.querySelector('.input-precio').value) || 0;
  
  const subtotal = cant * precio;
  fila.querySelector('.txt-subtotal-fila').textContent = subtotal.toFixed(2);
  
  recalcularTotales();
}

function recalcularTotales() {
  let totalComprobante = 0;
  
  document.querySelectorAll('.txt-subtotal-fila').forEach(el => {
    totalComprobante += parseFloat(el.textContent) || 0;
  });
  
  const subtotalGravada = totalComprobante / 1.18;
  const igv = subtotalGravada * 0.18;
  
  document.getElementById('txt-gravada').textContent = 'S/ ' + subtotalGravada.toFixed(2);
  document.getElementById('txt-igv').textContent = 'S/ ' + igv.toFixed(2);
  document.getElementById('txt-total').textContent = 'S/ ' + totalComprobante.toFixed(2);
}

function procesarEmision() {
  const btn = document.getElementById('btnEmitir');
  const clienteId = document.getElementById('cliente_id').value;
  const tipoCpe = document.getElementById('tipo_cpe').value;
  
  if (!clienteId) {
    alert('Debe seleccionar un cliente.');
    return;
  }
  
  const filas = document.querySelectorAll('#itemsBody tr');
  if (filas.length === 0) {
    alert('Debe agregar al menos un ítem al comprobante.');
    return;
  }
  
  // Recopilar items
  const items = [];
  let valid = true;
  
  filas.forEach(f => {
    const pId = f.querySelector('.select-prod').value;
    const cant = f.querySelector('.input-cant').value;
    
    if (!pId || cant <= 0) {
      valid = false;
    }
    
    items.push({
      producto_id: pId,
      cantidad: cant
    });
  });
  
  if (!valid) {
    alert('Asegúrese de seleccionar el producto e ingresar una cantidad válida en todas las filas.');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Firmando y enviando CPE...';
  
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  
  // Enviar vía Fetch POST
  const formData = new URLSearchParams();
  formData.append('_csrf', token);
  formData.append('cliente_id', clienteId);
  formData.append('tipo_cpe', tipoCpe);
  
  items.forEach((it, idx) => {
    formData.append(`items[${idx}][producto_id]`, it.producto_id);
    formData.append(`items[${idx}][cantidad]`, it.cantidad);
  });

  fetch('<?= $config['url'] ?>/facturas/emitir', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: formData.toString()
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`🎉 CPE ${data.numero} emitido y aceptado por SUNAT exitosamente.`);
      window.location.href = '<?= $config['url'] ?>/facturas';
    } else {
      alert('Error en validación:\n' + data.errors.join('\n'));
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i><span>Emitir Comprobante</span>';
    }
  })
  .catch(err => {
    console.error(err);
    alert('Ocurrió un error inesperado al emitir el comprobante.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i><span>Emitir Comprobante</span>';
  });
}
</script>
