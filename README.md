# FAXEL BI — Plataforma SaaS de Inteligencia de Negocios + Facturación Electrónica + IA

<div align="center">

![FAXEL BI](https://img.shields.io/badge/FAXEL%20BI-v2.0.4-E53E3E?style=for-the-badge&logo=chart-bar)
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

## 🚀 Guía de Instalación Paso a Paso (Web App)

### 📋 Prerrequisitos
Antes de comenzar, asegúrate de tener instalado en tu máquina local:
1. **PHP 8.4+** con soporte para las siguientes extensiones habilitadas en tu archivo `php.ini`:
   - `pdo_mysql` (Conexión a Base de Datos)
   - `curl` (Comunicación con el microservicio de IA)
   - `mbstring` (Soporte multibyte)
   - `openssl` (Cifrado y firmas)
   - `json` (Procesamiento de respuestas API)
2. **MySQL 8.0+** o MariaDB equivalente ejecutándose en el puerto predeterminado `3306`.
3. **Servidor Web local**:
   - **Opción A (Recomendada para desarrollo)**: Usar el servidor web interno de PHP.
   - **Opción B**: Apache configurado a través de XAMPP, WampServer o Laragon.

---

### 🔧 Proceso de Instalación

#### Paso 1 — Configurar y Levantar la Base de Datos
El proyecto cuenta con un script automatizado para la creación de esquemas y carga de datos de demostración en Windows.
1. Inicia tu servidor local de MySQL.
2. Ejecuta el archivo `instalar.bat` haciendo doble clic o desde la consola de comandos de PowerShell:
   ```powershell
   .\instalar.bat
   ```
   *Este script se encargará de:*
   - Validar la disponibilidad del puerto de MySQL y conexión en `localhost` con el usuario `root` (sin contraseña).
   - Crear la base de datos relacional multi-tenant `faxel_bi`.
   - Importar la estructura del esquema desde [database/schema.sql](file:///d:/SISTEMA_FAXEL/database/schema.sql).
   - Poblar la base de datos con registros demo e históricos (ventas, clientes, productos, alertas y usuarios) desde [database/seed_demo.sql](file:///d:/SISTEMA_FAXEL/database/seed_demo.sql).

#### Paso 2 — Ajustar Configuración de la Aplicación
Edita el archivo de configuración global en [config/app.php](file:///d:/SISTEMA_FAXEL/config/app.php) para configurar los entornos:
```php
return [
    'url' => 'http://localhost:8080/SISTEMA_FAXEL/public', // Ajusta según tu ruta en Apache o puerto alternativo
    // ...
];
```
*(Nota: Si usas el servidor web integrado de PHP en el puerto 8080, la URL del sistema será `http://localhost:8080/SISTEMA_FAXEL/public`).*

#### Paso 3 — Iniciar el Servidor Web PHP
- **Si usas XAMPP/Laragon**: Coloca la carpeta del proyecto en `htdocs` o `www` y accede mediante Apache.
- **Si usas la terminal con el router integrado (Desarrollo directo)**:
  Ejecuta el siguiente comando en la raíz del proyecto `d:\SISTEMA_FAXEL`:
  ```powershell
  php -S localhost:8080 router.php
  ```
  *(El archivo [router.php](file:///d:/SISTEMA_FAXEL/router.php) redirigirá automáticamente todas las solicitudes de páginas al controlador frontal e index global de manera limpia).*

---

## 🤖 Guía de Instalación y Funcionamiento de la IA (Python AI)

El backend de Inteligencia Artificial está estructurado como un microservicio independiente desarrollado en **Python (Flask)**. Procesa las solicitudes analíticas pesadas (Machine Learning, Text-to-SQL y Audio-to-Text) para no saturar el servidor PHP.

### 📋 Prerrequisitos de Python
- **Python 3.10+** instalado y en el PATH del sistema.
- Gestor de paquetes `pip` actualizado.

---

### 🔧 Proceso de Instalación del Microservicio

1. Abre una consola y navega al directorio del microservicio de IA:
   ```powershell
   cd d:\SISTEMA_FAXEL\python_ai
   ```
2. Instala las dependencias principales del proyecto declaradas en `requirements.txt`:
   ```powershell
   pip install -r requirements.txt
   ```
3. Instala la biblioteca requerida para la transcripción y procesamiento de voz (Speech Recognition):
   ```powershell
   pip install SpeechRecognition
   ```
   *(Nota: Para el procesamiento local de transcripción, no necesitas instalar PyAudio en el servidor host, ya que el audio es grabado directamente en el navegador del cliente y enviado al microservicio en formato WAV pregrabado).*

4. Ejecuta el microservicio:
   ```powershell
   python app.py
   ```
   El microservicio se iniciará y quedará escuchando peticiones en: `http://localhost:5000`.

---

### 🧠 Funcionamiento Interno de los Módulos de IA

#### 1. Predicción de Ventas (Machine Learning Real)
* **Algoritmo**: Utiliza la biblioteca **Prophet** de Meta para realizar predicciones de series temporales.
* **Fallback**: Si Prophet no se encuentra compilado o falla la inicialización en el entorno del sistema, el pipeline conmuta automáticamente a una regresión lineal real estructurada con `scikit-learn` (`LinearRegression`) a partir de ingeniería de características basada en fechas ordinales.
* **Funcionamiento**: Envía los datos históricos de transacciones del tenant. Retorna las proyecciones puntuales para 7 y 30 días futuros con límites superiores/inferiores (`yhat_upper`, `yhat_lower`) al 80% de nivel de confianza.
* **Endpoint**: `POST /predict/sales`

#### 2. Score de Churn (Predicción de Riesgo de Abandono)
* **Algoritmo**: Modelo clasificador **RandomForestClassifier** entrenado sobre el comportamiento histórico del cliente.
* **Factores evaluados**:
  - Días transcurridos desde su última compra (`dias_sin_compra`).
  - Total de transacciones históricas (`total_compras`).
  - Ticket promedio por compra (`ticket_promedio`).
  - Monto acumulado total facturado (`monto_acumulado`).
* **Funcionamiento**: Genera una probabilidad porcentual (0% a 100%). Clasifica al cliente en nivel de riesgo Bajo, Medio o Alto y activa flujos de automatización de campañas de retención en PHP.
* **Endpoint**: `POST /predict/churn`

#### 3. Simulador de Escenarios "What-If"
* **Lógica**: Modela curvas de demanda elásticas usando factores combinados:
  - Cambios en Precios (elasticidad de demanda estándar).
  - Inversión en Marketing (impacto exponencial decreciente en tráfico).
  - Tasas de Promociones/Descuentos (atracción vs erosión de margen).
  - Capacidad de Fuerza de Ventas (escalamiento operativo).
* **Funcionamiento**: Proyecta las variaciones estimadas frente a la línea base de Prophet e informa en tiempo real los aumentos/pérdidas tanto en ingresos como en margen de utilidad neta en un gráfico interactivo.
* **Endpoint**: `POST /predict/simulate`

#### 4. Chat Copiloto Inteligente (Text-to-SQL Seguro)
* **Lógica**: Motor conversacional NLP que interpreta preguntas del usuario (ej: *"¿Qué producto genera más utilidad?"*), las traduce en consultas SQL dinámicas optimizadas y seguras, las ejecuta en la base de datos de la empresa y devuelve los resultados formateados y con datos listos para graficar en Chart.js.
* **Aislamiento Multi-tenant**: El motor de chat restringe estrictamente todas las consultas SQL inyectando obligatoriamente el `empresa_id` del usuario autenticado en las cláusulas `WHERE`, evitando fugas de información entre empresas del SaaS.
* **Endpoint**: `POST /chat/query`

#### 5. Entrenamiento Dinámico en Caliente (Re-entrenamiento)
* **Lógica**: Desde el panel de **Entrenamiento IA**, los usuarios con privilegios (`prediccion.train`) pueden subir archivos CSV o Excel personalizados para entrenar nuevos modelos específicos de Ventas (requiere columnas `ds` y `y`) o Churn (columnas `dias_sin_compra`, `total_compras`, `ticket_promedio`, `monto_acumulado`, `churn`).
* **Guardado**: Al terminar, el modelo es serializado mediante `joblib` y guardado en `python_ai/storage/models/modelo_{empresa_id}_{tipo}.joblib` para ser recargado de forma persistente en las siguientes consultas.
* **Endpoint**: `POST /predict/train`

#### 6. Entrada de Voz (Whisper STT) y Voz del Asistente (TTS)
* **Lógica de Entrada (STT)**:
  - El navegador web del usuario graba la voz mediante el micrófono a través de la API `MediaRecorder`.
  - Para evitar distorsiones de velocidad en el backend (efecto acelerado/desacelerado por tasa de hardware), el cliente en JavaScript downsamplea linealmente la onda de audio a **16000Hz PCM Mono en un contenedor WAV** nativo.
  - El PHP (`/chat/transcribir`) envía este archivo WAV al endpoint de Python `/audio/transcribe`.
  - Python ejecuta el motor `SpeechRecognition`. Primero intenta transcribir a través del reconocedor gratuito en la nube de Google (`recognize_google` configurado para `es-PE`). Si no hay internet o falla, conmuta al reconocedor local de **OpenAI Whisper** (`recognize_whisper`).
* **Lógica de Salida (TTS)**:
  - La respuesta de texto generada por el asistente se sanitiza de etiquetas markdown y es procesada localmente por el navegador del usuario utilizando la API nativa de síntesis de voz `SpeechSynthesisUtterance` en español sin consumir CPU del servidor.

---

## 🔍 Verificación del Sistema y Diagnóstico

### Prueba de Health Check de la IA
Puedes verificar si el microservicio de IA está activo y cargó correctamente los componentes ejecutando la siguiente llamada en tu navegador o terminal:
```bash
curl http://localhost:5000/health
```
**Respuesta esperada (JSON):**
```json
{
  "status": "ok",
  "service": "FAXEL BI Python AI",
  "version": "2.0.0",
  "ai_available": true,
  "timestamp": "2026-06-19T20:50:00.000000"
}
```

### Credenciales de Acceso Demo:
Accede a `http://localhost:8080/SISTEMA_FAXEL/public/login` e ingresa:
- **Administrador**: `admin@faxel.pe` | Clave: `password`
- **Gerente**: `gerente@faxel.pe` | Clave: `password`
- **Analista**: `analista@faxel.pe` | Clave: `password`
- **Operador**: `operador@faxel.pe` | Clave: `password`
