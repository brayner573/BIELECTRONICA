<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Security::e($title ?? 'Registro Empresa — FAXEL BI') ?></title>
  <meta name="description" content="Regístrate en FAXEL BI">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= $config['url'] ?>/assets/css/main.css?v=2.0.3" rel="stylesheet">
  <style>
    body { background-color: var(--bg-base); }
    .register-container {
      max-width: 850px;
      margin: 40px auto;
      position: relative;
      z-index: 1;
    }
    .register-card {
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 40px;
      box-shadow: var(--shadow);
    }
    .register-bg-effects {
      position: fixed;
      inset: 0;
      overflow: hidden;
      pointer-events: none;
      z-index: 0;
    }
  </style>
</head>
<body>

<div class="register-bg-effects">
  <div class="glow-orb glow-orb-1" style="top:-20%; left:-10%; width:600px; height:600px;"></div>
  <div class="glow-orb glow-orb-2" style="bottom:-20%; right:-10%; width:600px; height:600px;"></div>
</div>

<div class="register-container">
  <div class="register-card">
    
    <!-- Logo y Encabezado -->
    <div class="text-center mb-4">
      <div style="background: #E53E3E; border-radius: var(--radius-lg); padding: 16px 20px; display: inline-flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(229, 62, 62, 0.2); margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.05);">
        <div style="width: 44px; height: 44px; background: #000000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 24px; color: #FFFFFF; font-family: 'Inter', sans-serif; min-width: 44px;">F</div>
        <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.1;">
          <span style="font-weight: 900; font-size: 22px; color: #FFFFFF; letter-spacing: 0.8px;">FAXEL</span>
          <span style="font-size: 8px; color: rgba(255,255,255,0.95); font-weight: 700; text-transform: uppercase; letter-spacing: 0.2px;">Facturación Electrónica</span>
        </div>
      </div>
      <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Empieza a analizar tu facturación e impulsar decisiones con IA</p>
    </div>

    <!-- Errores -->
    <?php if (!empty($errors)): ?>
    <div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:12px 16px;margin-bottom:24px;">
      <?php foreach ($errors as $err): ?>
        <div style="color:#EF4444;font-size:13px;display:flex;align-items:center;gap:8px;margin-bottom:4px;">
          <i class="bi bi-exclamation-circle"></i>
          <?= Security::e($err) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Formulario de Registro -->
    <form method="POST" action="<?= $config['url'] ?>/register" enctype="multipart/form-data" id="registerForm">
      <?= Security::csrfField() ?>

      <h4 style="font-size:16px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:8px;">
        <i class="bi bi-person-workspace"></i> Datos del Administrador
      </h4>

      <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label-bi" for="nombre">Nombre</label>
          <input type="text" id="nombre" name="nombre" class="input-bi" placeholder="Carlos" value="<?= Security::e($nombre ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label-bi" for="apellido">Apellido</label>
          <input type="text" id="apellido" name="apellido" class="input-bi" placeholder="Mendoza" value="<?= Security::e($apellido ?? '') ?>" required>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label-bi" for="email">Correo Electrónico de Acceso</label>
          <input type="email" id="email" name="email" class="input-bi" placeholder="carlos@miempresa.com" value="<?= Security::e($email ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label-bi" for="password">Contraseña segura</label>
          <input type="password" id="password" name="password" class="input-bi" placeholder="Mínimo 8 caracteres, 1 mayúscula, 1 número" required>
        </div>
      </div>

      <h4 class="mt-4" style="font-size:16px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:8px;">
        <i class="bi bi-building"></i> Datos Corporativos de la Empresa
      </h4>

      <div class="row mb-4">
        <div class="col-md-8 mb-3 mb-md-0">
          <label class="form-label-bi" for="empresa">Razón Social o Nombre Comercial</label>
          <input type="text" id="empresa" name="empresa" class="input-bi" placeholder="Mi Empresa S.A.C." value="<?= Security::e($empresa ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label-bi" for="ruc">RUC de la Empresa (11 dígitos)</label>
          <input type="text" id="ruc" name="ruc" class="input-bi" placeholder="20100000002" maxlength="11" value="<?= Security::e($ruc ?? '') ?>" required>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label-bi" for="celular">Celular de Contacto</label>
          <input type="tel" id="celular" name="celular" class="input-bi" placeholder="999888777" value="<?= Security::e($celular ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label-bi" for="sector">Sector de Negocio</label>
          <select id="sector" name="sector" class="input-bi">
            <option value="Tecnología" <?= isset($sector) && $sector == 'Tecnología' ? 'selected' : '' ?>>Tecnología / Software</option>
            <option value="Comercio" <?= isset($sector) && $sector == 'Comercio' ? 'selected' : '' ?>>Comercio Minorista / Retail</option>
            <option value="Servicios" <?= isset($sector) && $sector == 'Servicios' ? 'selected' : '' ?>>Servicios Profesionales</option>
            <option value="Manufactura" <?= isset($sector) && $sector == 'Manufactura' ? 'selected' : '' ?>>Manufactura / Industria</option>
            <option value="Alimentos" <?= isset($sector) && $sector == 'Alimentos' ? 'selected' : '' ?>>Alimentos y Bebidas</option>
            <option value="Otros" <?= isset($sector) && $sector == 'Otros' ? 'selected' : '' ?>>Otros</option>
          </select>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label-bi" for="empleados">Cantidad de Empleados</label>
          <select id="empleados" name="empleados" class="input-bi">
            <option value="5" <?= isset($empleados) && $empleados == 5 ? 'selected' : '' ?>>1 - 5 empleados</option>
            <option value="15" <?= isset($empleados) && $empleados == 15 ? 'selected' : '' ?>>6 - 20 empleados</option>
            <option value="50" <?= isset($empleados) && $empleados == 50 ? 'selected' : '' ?>>21 - 100 empleados</option>
            <option value="200" <?= isset($empleados) && $empleados == 200 ? 'selected' : '' ?>>Más de 100 empleados</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label-bi" for="logo">Logo Corporativo (.png, .jpg, .webp)</label>
          <input type="file" id="logo" name="logo" class="input-bi" accept="image/*">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label-bi" for="direccion">Dirección Fiscal</label>
        <input type="text" id="direccion" name="direccion" class="input-bi" placeholder="Av. Principal 123, Oficina 401, San Isidro, Lima" value="<?= Security::e($direccion ?? '') ?>">
      </div>

      <div class="mt-5 text-center">
        <button type="submit" class="btn-bi primary w-md" id="submitBtn" style="height:48px; min-width: 250px; justify-content:center;">
          <i class="bi bi-rocket-takeoff-fill"></i>
          <span>Registrar Empresa</span>
        </button>
        
        <p class="mt-4" style="color:var(--text-muted); font-size:14px;">
          ¿Ya tienes una cuenta? <a href="<?= $config['url'] ?>/login" style="color:var(--primary); font-weight:700; text-decoration:none;">Inicia Sesión aquí</a>
        </p>
      </div>
    </form>

  </div>
</div>

<script>
document.getElementById('registerForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando tu SaaS...';
});
</script>
</body>
</html>
