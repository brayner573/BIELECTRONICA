<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= Security::e($factura['numero_completo']) ?> — Representación Impresa</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #333;
      margin: 0;
      padding: 20px;
      background-color: #fff;
    }
    .invoice-box {
      max-width: 800px;
      margin: auto;
      border: 1px solid #eee;
      padding: 30px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }
    .header-table, .info-table, .totals-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .header-table td {
      vertical-align: top;
    }
    .emisor-info {
      width: 60%;
    }
    .emisor-logo {
      max-height: 60px;
      margin-bottom: 10px;
    }
    .emisor-name {
      font-size: 16px;
      font-weight: bold;
      color: #1a252f;
      text-transform: uppercase;
    }
    .ruc-box {
      width: 35%;
      border: 2px solid #1a252f;
      text-align: center;
      padding: 15px;
      border-radius: 8px;
      background-color: #fcfcfc;
    }
    .ruc-box h2 {
      margin: 0 0 5px;
      font-size: 18px;
      letter-spacing: 0.5px;
    }
    .ruc-box h3 {
      margin: 0 0 5px;
      font-size: 14px;
      color: #7f8c8d;
    }
    .ruc-box h1 {
      margin: 0;
      font-size: 16px;
      color: #e74c3c;
    }
    .info-table {
      background-color: #f9f9f9;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
    }
    .info-table td {
      padding: 8px 12px;
      border-bottom: 1px solid #eee;
    }
    .info-label {
      font-weight: bold;
      color: #555;
      width: 15%;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 20px;
    }
    .items-table th {
      background-color: #1a252f;
      color: #ffffff;
      padding: 10px;
      font-size: 11px;
      text-transform: uppercase;
      text-align: left;
    }
    .items-table td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
    .totals-table td {
      padding: 6px 12px;
    }
    .qr-section {
      width: 100%;
      margin-top: 30px;
      border-top: 1px solid #eee;
      padding-top: 20px;
      display: flex;
      align-items: center;
    }
    .qr-code {
      width: 100px;
      height: 100px;
      border: 1px solid #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 9px;
      color: #aaa;
      margin-right: 20px;
      background: repeating-linear-gradient(45deg, #f5f5f5, #f5f5f5 10px, #e5e5e5 10px, #e5e5e5 20px);
    }
    .hash-info {
      font-size: 11px;
      color: #555;
    }
    .text-right {
      text-align: right;
    }
    .btn-print {
      display: block;
      width: 120px;
      margin: 10px auto 20px;
      padding: 10px;
      background-color: #1a252f;
      color: #fff;
      text-align: center;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    }
    @media print {
      .btn-print {
        display: none;
      }
      body {
        padding: 0;
      }
      .invoice-box {
        border: none;
        box-shadow: none;
        padding: 0;
      }
    }
  </style>
</head>
<body>

  <button class="btn-print" onclick="window.print()">Imprimir CPE</button>

  <div class="invoice-box">
    
    <!-- Encabezado y RUC -->
    <table class="header-table">
      <tr>
        <td class="emisor-info">
          <?php if ($factura['logo_path']): ?>
            <img src="<?= $config['url'] . $factura['logo_path'] ?>" class="emisor-logo" alt="Logo">
          <?php endif; ?>
          <div class="emisor-name"><?= Security::e($factura['emisor_razon']) ?></div>
          <div style="margin-top: 6px; color:#555; line-height: 1.4;">
            <?= Security::e($factura['emisor_dir']) ?><br>
            Email: <?= Security::e($factura['email'] ?? 'contacto@empresa.com') ?>
          </div>
        </td>
        <td class="ruc-box">
          <h3>R.U.C. <?= Security::e($factura['emisor_ruc']) ?></h3>
          <h2><?= $factura['tipo_comp'] === '01' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' ?></h2>
          <h1>N° <?= Security::e($factura['numero_completo']) ?></h1>
        </td>
      </tr>
    </table>

    <!-- Información del Cliente -->
    <table class="info-table">
      <tr>
        <td class="info-label">Adquiriente:</td>
        <td><?= Security::e($factura['cliente_razon']) ?></td>
        <td class="info-label">Fecha Emisión:</td>
        <td><?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></td>
      </tr>
      <tr>
        <td class="info-label">R.U.C./D.N.I.:</td>
        <td><?= Security::e($factura['cliente_ruc']) ?></td>
        <td class="info-label">Moneda:</td>
        <td><?= $factura['moneda'] === 'PEN' ? 'Soles (S/ )' : 'Dólares ($)' ?></td>
      </tr>
      <tr>
        <td class="info-label">Dirección:</td>
        <td colspan="3"><?= Security::e($factura['cliente_dir'] ?: 'Dirección General') ?></td>
      </tr>
    </table>

    <!-- Tabla de Ítems -->
    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 10%;">Código</th>
          <th style="width: 50%;">Descripción</th>
          <th style="width: 10%;">Unidad</th>
          <th style="width: 10%; text-align: right;">Cantidad</th>
          <th style="width: 10%; text-align: right;">P. Unit</th>
          <th style="width: 10%; text-align: right;">Importe</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($detalles as $det): ?>
        <tr>
          <td><?= Security::e($det['codigo']) ?></td>
          <td><?= Security::e($det['nombre']) ?></td>
          <td><?= Security::e($det['unidad'] ?: 'UND') ?></td>
          <td class="text-right"><?= number_format($det['cantidad'], 2) ?></td>
          <td class="text-right">S/ <?= number_format($det['precio_unitario'], 2) ?></td>
          <td class="text-right">S/ <?= number_format($det['subtotal'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Desglose de Totales -->
    <table class="totals-table">
      <tr>
        <td style="width: 60%; vertical-align: top; font-size:11px; color:#555;">
          <strong>Representación impresa de la factura electrónica.</strong><br>
          Autorizado mediante resolución SUNAT de factores electrónicos.<br>
          Consulte la validez del comprobante en el portal de SUNAT con el código hash.
        </td>
        <td style="width: 40%;">
          <table style="width: 100%; border-collapse: collapse;">
            <tr>
              <td style="color:#555;">Op. Gravadas:</td>
              <td class="text-right">S/ <?= number_format($factura['subtotal'], 2) ?></td>
            </tr>
            <tr>
              <td style="color:#555;">I.G.V. (18%):</td>
              <td class="text-right">S/ <?= number_format($factura['igv'], 2) ?></td>
            </tr>
            <tr style="font-size: 14px; font-weight: bold; border-top: 1px solid #ddd;">
              <td style="padding-top: 8px;">Importe Total:</td>
              <td class="text-right" style="padding-top: 8px;">S/ <?= number_format($factura['total'], 2) ?></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Pie del CPE / QR y Hash -->
    <div class="qr-section">
      <div class="qr-code">
        [ CÓDIGO QR SUNAT ]<br>
        <?= Security::e($factura['numero_completo']) ?>
      </div>
      <div class="hash-info">
        <strong>Código Hash CPE:</strong><br>
        <span style="font-family: monospace; font-size: 10px; color:#7f8c8d;"><?= Security::e($factura['hash_cpe']) ?></span><br><br>
        <span>El CPE ha sido **aceptado** por la SUNAT en modalidad asíncrona local (CDR recibido satisfactoriamente).</span>
      </div>
    </div>

  </div>

</body>
</html>
