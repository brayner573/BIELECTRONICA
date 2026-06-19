<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Security::e($title ?? 'FAXEL BI') ?></title>
  <meta name="description" content="Iniciar sesión en FAXEL BI">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= $config['url'] ?>/assets/css/main.css?v=2.0.3" rel="stylesheet">
</head>
<body>

<div class="login-page">

  <!-- Fondo con glow orbs -->
  <div class="login-bg-effects">
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>
    <div class="glow-orb glow-orb-3"></div>
  </div>

  <!-- Grid de puntos decorativo -->
  <div style="position:fixed;inset:0;background-image:radial-gradient(rgba(229,62,62,0.08) 1px,transparent 1px);background-size:32px 32px;pointer-events:none;z-index:0;"></div>

  <!-- Card de login -->
  <div class="login-card">

    <!-- Logo -->
    <div class="text-center mb-4">
      <div style="background: #E53E3E; border-radius: var(--radius-lg); padding: 20px 24px; display: inline-flex; align-items: center; gap: 14px; box-shadow: 0 10px 30px rgba(229, 62, 62, 0.25); margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.05);">
        <div style="width: 50px; height: 50px; background: #000000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 28px; color: #FFFFFF; font-family: 'Inter', sans-serif; min-width: 50px;">F</div>
        <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.1;">
          <span style="font-weight: 900; font-size: 26px; color: #FFFFFF; letter-spacing: 0.8px;">FAXEL</span>
          <span style="font-size: 9px; color: rgba(255,255,255,0.95); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">Facturación Electrónica</span>
        </div>
      </div>
      <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Business Intelligence Platform</p>
    </div>

    <!-- Errores -->
    <?php if (!empty($errors)): ?>
    <div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;">
      <?php foreach ($errors as $err): ?>
        <div style="color:#EF4444;font-size:13px;display:flex;align-items:center;gap:8px;">
          <i class="bi bi-exclamation-circle"></i>
          <?= Security::e($err) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" action="<?= $config['url'] ?>/login" id="loginForm">
      <?= Security::csrfField() ?>

      <div class="mb-4">
        <label class="form-label-bi" for="email">
          <i class="bi bi-envelope"></i> Correo Electrónico
        </label>
        <input type="email" id="email" name="email" class="input-bi"
               value="<?= Security::e($email ?? '') ?>"
               placeholder="usuario@empresa.com"
               required autocomplete="email">
      </div>

      <div class="mb-4">
        <label class="form-label-bi" for="password">
          <i class="bi bi-lock"></i> Contraseña
        </label>
        <div style="position:relative;">
          <input type="password" id="password" name="password" class="input-bi"
                 placeholder="••••••••"
                 required autocomplete="current-password"
                 style="padding-right:44px;">
          <button type="button" id="togglePass"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-bi primary w-full" id="submitBtn" style="justify-content:center;height:48px;">
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Iniciar Sesión</span>
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;margin-bottom:12px;">
      <span style="color:var(--text-muted);font-size:13px;">¿No tienes cuenta de empresa?</span>
      <a href="<?= $config['url'] ?>/register" style="color:var(--primary);font-weight:700;text-decoration:none;font-size:13px;margin-left:4px;">Regístrate aquí</a>
    </div>

    <!-- Demo credentials -->
    <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:15px;">
      <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:0.6px;">Credenciales de Demo</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <?php
          $demos = [
            ['admin@faxel.pe', 'Administrador', '#E53E3E'],
            ['gerente@faxel.pe', 'Gerente', '#8B5CF6'],
            ['analista@faxel.pe', 'Analista', '#06B6D4'],
            ['operador@faxel.pe', 'Operador', '#10B981'],
          ];
          foreach ($demos as [$email, $rol, $color]):
        ?>
        <button type="button" class="demo-btn"
                onclick="fillDemo('<?= $email ?>')"
                style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:10px;text-align:left;cursor:pointer;transition:all 0.2s;color:var(--text-primary);">
          <div style="font-size:10px;color:<?= $color ?>;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:3px;"><?= $rol ?></div>
          <div style="font-size:12px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $email ?></div>
        </button>
        <?php endforeach; ?>
      </div>
      <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:10px;">
        Contraseña para todos: <span style="color:var(--primary);font-family:monospace;">password</span>
      </p>
    </div>

  </div><!-- /login-card -->
</div>

<script>
document.querySelectorAll('.demo-btn').forEach(b => {
  b.addEventListener('mouseenter', () => b.style.borderColor = 'rgba(229,62,62,0.4)');
  b.addEventListener('mouseleave', () => b.style.borderColor = 'var(--border)');
});

function fillDemo(email) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = 'password';
  document.getElementById('password').focus();
}

document.getElementById('togglePass')?.addEventListener('click', function() {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
});

document.getElementById('loginForm')?.addEventListener('submit', function() {
  const btn  = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';
});
</script>
</body>
</html>
