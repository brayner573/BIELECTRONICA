<?php
/**
 * FAXEL BI — Definición de Rutas
 */

/** @var Router $router */

// ── Autenticación ─────────────────────────────────────────
$router->get('/login',  ['AuthController', 'login']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);
$router->get('/register',  ['RegisterController', 'show']);
$router->post('/register', ['RegisterController', 'store']);

// ── Dashboard (raíz) ──────────────────────────────────────
$router->get('/',          ['DashboardController', 'index']);
$router->get('/dashboard', ['DashboardController', 'index']);
$router->get('/dashboard/charts', ['DashboardController', 'apiCharts']);
$router->post('/dashboard/metas', ['DashboardController', 'guardarMetas']);

// ── Predicción IA ─────────────────────────────────────────
$router->get('/prediccion',          ['PrediccionController', 'index']);
$router->post('/prediccion/ejecutar',['PrediccionController', 'ejecutar']);
$router->get('/prediccion/churn',    ['PrediccionController', 'churn']);
$router->get('/prediccion/simulador', ['PrediccionController', 'simulador']);
$router->post('/prediccion/simular',  ['PrediccionController', 'simular']);
$router->get('/prediccion/entrenamiento',  ['TrainingController', 'index']);
$router->post('/prediccion/entrenamiento', ['TrainingController', 'entrenar']);

// ── Productos ─────────────────────────────────────────────
$router->get('/productos/rentabilidad', ['ProductoController', 'rentabilidad']);

// ── Alertas ───────────────────────────────────────────────
$router->get('/alertas',                  ['AlertaController', 'index']);
$router->get('/alertas/stream',           ['AlertaController', 'stream']);
$router->post('/alertas/{id}/resolver',   ['AlertaController', 'resolver']);
$router->post('/alertas/{id}/revisar',    ['AlertaController', 'revisar']);
$router->post('/alertas/generar',         ['AlertaController', 'generar']);

// ── Chat IA ───────────────────────────────────────────────
$router->get('/chat',            ['ChatController', 'index']);
$router->post('/chat/enviar',    ['ChatController', 'enviar']);
$router->get('/chat/nueva',      ['ChatController', 'nuevaSesion']);

// ── Reportes ──────────────────────────────────────────────
$router->get('/reportes',           ['ReporteController', 'index']);
$router->get('/reportes/pdf',       ['ReporteController', 'pdf']);
$router->get('/reportes/excel',     ['ReporteController', 'excel']);
$router->get('/reportes/kpis',      ['ReporteController', 'kpis']);

// ── Facturación Electrónica ───────────────────────────────
$router->get('/facturas',          ['FacturacionController', 'index']);
$router->get('/facturas/emitir',   ['FacturacionController', 'crear']);
$router->post('/facturas/emitir',  ['FacturacionController', 'store']);
$router->get('/facturas/{id}/pdf', ['FacturacionController', 'pdf']);
$router->post('/facturas/{id}/anular', ['FacturacionController', 'anular']);
