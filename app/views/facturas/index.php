
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);">Comprobantes de Pago Electrónicos (CPE)</h2>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Visualiza, emite y anula tus facturas y boletas electrónicas SUNAT.</p>
    </div>
    <?php if (ACL::hasPermission($user['rol'], 'facturas.create')): ?>
    <a href="<?= $config['url'] ?>/facturas/emitir" class="btn-bi primary">
      <i class="bi bi-plus-lg"></i> Emitir Factura
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Grid de Facturas -->
<div class="card-bi mb-4">
  <div class="table-responsive">
    <table class="table-bi">
      <thead>
        <tr>
          <th>Fecha Emisión</th>
          <th>Tipo CPE</th>
          <th>Número</th>
          <th>Cliente</th>
          <th>Moneda</th>
          <th style="text-align:right;">Subtotal</th>
          <th style="text-align:right;">IGV (18%)</th>
          <th style="text-align:right;">Total</th>
          <th>Estado</th>
          <th>Archivos</th>
          <th style="text-align:center;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($facturas)): ?>
        <tr>
          <td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px 0;">
            <i class="bi bi-file-earmark-text" style="font-size:32px;display:block;margin-bottom:10px;"></i>
            No hay comprobantes electrónicos emitidos en este periodo.
          </td>
        </tr>
        <?php else: ?>
          <?php foreach ($facturas as $f): ?>
          <tr id="row-fact-<?= $f['id'] ?>">
            <td><?= date('d/m/Y', strtotime($f['fecha_emision'])) ?></td>
            <td>
              <span class="badge-bi info">
                <?= $f['tipo_comp'] === '01' ? 'Factura' : 'Boleta' ?>
              </span>
            </td>
            <td><strong style="color:var(--primary);"><?= Security::e($f['numero_completo']) ?></strong></td>
            <td>
              <div><?= Security::e($f['cliente']) ?></div>
            </td>
            <td><span class="text-muted"><?= $f['moneda'] ?></span></td>
            <td style="text-align:right;">S/ <?= number_format($f['subtotal'], 2) ?></td>
            <td style="text-align:right;">S/ <?= number_format($f['igv'], 2) ?></td>
            <td style="text-align:right;font-weight:700;">S/ <?= number_format($f['total'], 2) ?></td>
            <td>
              <span class="badge-bi id-status-<?= $f['id'] ?> <?= $f['estado'] === 'pagada' ? 'success' : ($f['estado'] === 'anulada' ? 'danger' : 'warning') ?>">
                <?= ucfirst($f['estado']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:8px;">
                <?php if ($f['xml_path']): ?>
                <a href="<?= $config['url'] . $f['xml_path'] ?>" target="_blank" class="badge-bi info" style="text-decoration:none;" title="Descargar XML">
                  <i class="bi bi-filetype-xml"></i> XML
                </a>
                <?php endif; ?>
                <?php if ($f['cdr_path']): ?>
                <a href="<?= $config['url'] . $f['cdr_path'] ?>" target="_blank" class="badge-bi success" style="text-decoration:none;" title="Descargar Constancia CDR">
                  <i class="bi bi-check-circle"></i> CDR
                </a>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:center;">
              <div style="display:flex;gap:8px;justify-content:center;">
                <a href="<?= $config['url'] ?>/facturas/<?= $f['id'] ?>/pdf" target="_blank" class="btn-bi secondary sm" title="Ver PDF / Imprimir">
                  <i class="bi bi-printer"></i>
                </a>
                <?php if ($f['estado'] !== 'anulada' && ACL::hasPermission($user['rol'], 'facturas.void')): ?>
                <button onclick="anularComprobante(<?= $f['id'] ?>, '<?= $f['numero_completo'] ?>')" class="btn-bi danger sm" title="Anular Comprobante">
                  <i class="bi bi-x-circle"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function anularComprobante(id, numero) {
  if (!confirm(`¿Está seguro que desea anular el comprobante ${numero}?\nEsta acción devolverá el stock de productos y no se puede deshacer.`)) {
    return;
  }
  
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  
  fetch(`<?= $config['url'] ?>/facturas/${id}/anular`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `_csrf=${encodeURIComponent(token)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Ocurrió un error al intentar anular el comprobante.');
  });
}
</script>
