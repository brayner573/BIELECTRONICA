# FAXEL BI — Plataforma SaaS de Inteligencia de Negocios + Facturación Electrónica + IA

<div align="center">

![FAXEL BI](https://img.shields.io/badge/FAXEL%20BI-v2.0.0-4F8EF7?style=for-the-badge&logo=chart-bar)
![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php)
![Python](https://img.shields.io/badge/Python-3.10+-3776AB?style=for-the-badge&logo=python)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)
![PWA](https://img.shields.io/badge/PWA-Compatible-success?style=for-the-badge&logo=pwa)

**Plataforma local comercial SaaS multi-empresa de Business Intelligence, Invoicing UBL 2.1 y Machine Learning Predictivo**

</div>

---

## 📖 Documentación Adicional Obligatoria
Para conocer detalles profundos del funcionamiento y del desarrollo del software, consulte los siguientes manuales:
* [Manual de Usuario (Visualizar Guía)](file:///d:/SISTEMA_FAXEL/MANUAL_USUARIO.md) — Instrucciones paso a paso sobre registro, facturación, entrenamiento de IA y chat bot copiloto.
* [Manual Técnico y Arquitectura](file:///d:/SISTEMA_FAXEL/MANUAL_TECNICO.md) — Diagramas UML/ER, seguridad estricta, pipelines de IA y estrategia para migración a AWS en producción.

---

## 🚀 Instalación y Despliegue Rápido

### Prerrequisitos
* **PHP 8.4+** con Apache en XAMPP o Laragon (con extensiones `pdo_mysql`, `curl`, `json` y `mbstring`).
* **MySQL 8.0+** corriendo localmente en el puerto default `3306`.
* **Python 3.10+** (con `pip` instalado).

### Paso 1 — Inicializar la Base de Datos
Ejecute el instalador automático haciendo doble clic en [instalar.bat](file:///d:/SISTEMA_FAXEL/instalar.bat) o ejecútelo desde la terminal:
```powershell
.\instalar.bat
```
Este script verificará su conexión MySQL, creará el esquema multi-empresa `faxel_bi` e importará las tablas base y registros de ejemplo.

### Paso 2 — Configurar Entornos de Red
Asegúrese de que la URL de su aplicación en [config/app.php](file:///d:/SISTEMA_FAXEL/config/app.php) coincida con su entorno Apache:
```php
'url' => 'http://localhost/SISTEMA_FAXEL/public',
```

### Paso 3 — Ejecutar Microservicio de IA (Python Flask)
Abra una terminal en la carpeta del microservicio e instale las dependencias e inicie el servidor:
```bash
cd d:\SISTEMA_FAXEL\python_ai
pip install -r requirements.txt
python app.py
```
El servidor IA quedará activo en: `http://localhost:5000` con los pipelines reales de Prophet y RandomForest listos para trabajar.

### Paso 4 — Iniciar Sesión en la Plataforma
Abra en su navegador la URL:
`http://localhost/SISTEMA_FAXEL/public/login`

**Credenciales de Prueba:**
* **Administrador**: `admin@faxel.pe` | Clave: `password`
* **Gerente**: `gerente@faxel.pe` | Clave: `password`
* **Analista**: `analista@faxel.pe` | Clave: `password`

---

## 📊 Módulos Completamente Funcionales

### 1. Multi-tenant SaaS y Registro Corporativo
Autenticación robusta JWT + Sesión con aislamiento estricto por `empresa_id` a nivel de base de datos y API. Cuenta con panel de registro público para nuevas empresas que inicializa automáticamente sucursales, usuarios administradores y subida de logotipos corporativos.

### 2. Facturación Electrónica CPE SUNAT
Emisor desacoplado que genera XMLs reglamentarios bajo el estándar **UBL 2.1**, simula firmas digitales SHA-256, crea CDRs (Constancias de Aceptación) de SUNAT y representa comprobantes impresos en A4 PDF con código de barras y códigos QR funcionales.

### 3. Predicciones IA (Prophet / Sklearn)
Proyecta volumen de ventas para 7, 30, 90 y 365 días con intervalos de confianza de 80%. Si Prophet no se encuentra disponible, se activa automáticamente un motor de regresión lineal.

### 4. Riesgo de Abandono (Churn)
Aplica algoritmos de RandomForest a los datos transaccionales para calcular un score de deserción (0-100%) por cliente y sugiere planes de fidelización interactivos.

### 5. Chat Copiloto IA (Text-to-SQL)
Asistente NLP que permite consultar datos del negocio mediante lenguaje natural, ejecutando consultas SQL parametrizadas y visualizando gráficos estadísticos interactivos Chart.js en la ventana de chat de forma automática.

### 6. PWA (Progressive Web App)
El sistema cuenta con `manifest.json` y `sw.js` (Service Worker) que permiten la instalación de la app en teléfonos móviles y tablets Android/iOS, el almacenamiento en caché de archivos CSS/JS y la preparación para recibir notificaciones push corporativas.

---

## 🔒 Estándares de Seguridad
* **Aislamiento de Datos**: Consultas SQL filtradas con `empresa_id` inyectado.
* **Control de Accesos**: Implementación estricta de ACL para 6 roles empresariales.
* **Protección Web**: Middleware contra CSRF, XSS e inyecciones SQL.
* **Sesiones**: Cifrado, control de intentos de inicio de sesión y cookies seguras.
