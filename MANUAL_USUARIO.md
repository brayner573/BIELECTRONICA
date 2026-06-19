# MANUAL DE USUARIO — PLATAFORMA FAXEL BI + IA
## Inteligencia de Negocios y Facturación Electrónica Predictiva SaaS

¡Bienvenido a FAXEL BI! Esta guía le enseñará paso a paso cómo registrar su empresa, gestionar ventas, emitir comprobantes electrónicos reglamentarios y utilizar las potentes herramientas de Inteligencia Artificial para hacer crecer su negocio.

---

## 1. REGISTRO DE EMPRESA Y CONFIGURACIÓN INICIAL
FAXEL BI es una plataforma SaaS multi-empresa. Esto significa que los datos de su negocio están completamente aislados y seguros.

1. **Registrar Cuenta de Empresa**:
   * Ingrese a la plataforma y haga clic en **"Regístrate aquí"** en la pantalla de inicio de sesión o vaya directamente a: `http://localhost/SISTEMA_FAXEL/public/register`.
   * Complete el formulario con sus datos personales (Nombre, Apellido, Correo, Contraseña) y los datos fiscales de su negocio:
     * **Razón Social** y **RUC de la empresa** (11 dígitos, ej. `20100000002`).
     * **Sector de Negocio** y **Cantidad de Empleados**.
     * **Logotipo corporativo** (se usará en los comprobantes de facturación).
   * Al hacer clic en **"Registrar Empresa"**, la plataforma creará automáticamente su cuenta administradora corporativa, asignará una sucursal principal por defecto y lo guiará al Dashboard.

---

## 2. COMPRENSIÓN DEL DASHBOARD EJECUTIVO
El panel principal le ofrece un resumen de salud financiera y comercial de su empresa en tiempo real:
* **KPIs Clave**: Muestra el total de ventas del mes, el ticket promedio, la rentabilidad global, cantidad de clientes activos y notificaciones sobre alertas activas.
* **Gráficos Dinámicos**:
  * **Ventas vs Gastos Mensuales**: Análisis de margen neto.
  * **Distribución de Ventas por Sucursal**: Rendimiento de sus sedes.
  * **Productos Estrella**: Top 5 de artículos con mayor volumen y margen.
  * **Análisis de Clientes**: Comparación de clientes VIP vs regulares.

---

## 3. FACTURACIÓN ELECTRÓNICA CPE (SUNAT UBL 2.1)
El sistema le permite emitir facturas y boletas electrónicas reglamentarias.

### 3.1. Emitir un Comprobante (CPE)
1. Diríjase a **"Facturas & Boletas"** en el menú lateral.
2. Haga clic en **"Emitir Comprobante"** (botón azul).
3. Seleccione el tipo de documento (Factura o Boleta), el cliente y la sucursal emisora.
4. Añada productos al detalle. El sistema calculará automáticamente el subtotal, el **IGV (18%)** y el importe neto total.
5. Presione **"Emitir Comprobante"**.

### 3.2. Descargar XML, CDR y PDF
Una vez emitido el comprobante, regresará al listado donde podrá:
* **Descargar XML**: Archivo UBL 2.1 de SUNAT conteniendo la firma digital simulada SHA-256.
* **Descargar CDR**: Constancia de Recepción de la SUNAT aprobando el comprobante.
* **Ver PDF**: Representación impresa en tamaño A4 con el código QR reglamentario de SUNAT y la firma electrónica.

---

## 4. ENTRENAMIENTO PERSONALIZADO DE MODELOS IA (MACHINE LEARNING)
El motor de IA de FAXEL BI puede ser entrenado con sus propios archivos de datos (CSV o Excel) para generar predicciones sumamente exactas sobre su negocio.

### 4.1. Dónde Encontrar los Datasets de Prueba
Hemos incluido dos datasets realistas listos para usar en la carpeta del sistema:
* **Dataset de Ventas**: Ubicado en `database/datasets/ventas_ejemplo.csv`.
* **Dataset de Churn (Abandono)**: Ubicado en `database/datasets/churn_ejemplo.csv`.

### 4.2. Cómo Entrenar el Modelo
1. Vaya a **"Predicción de Ventas"** o **"Riesgo Abandono"** en el menú.
2. Haga clic en el botón **"Panel de Entrenamiento"** arriba a la derecha.
3. Seleccione el tipo de modelo que desea entrenar:
   * **Ventas** (ds y y)
   * **Churn** (abandono de clientes)
4. Suba el archivo correspondiente de la carpeta `database/datasets/` y haga clic en **"Iniciar Entrenamiento ML"**.
5. El servidor Python Flask entrenará los algoritmos correspondientes en segundo plano, registrará los nuevos pesos en el almacenamiento local y reportará la precisión del modelo en la tabla del historial.
6. A partir de ese momento, todas las predicciones de la plataforma usarán el modelo optimizado de su empresa.

---

## 5. CÓMO USAR EL ASISTENTE IA EMPRESARIAL (COPILOTO)
El Copiloto IA es un chat conversacional inteligente que entiende preguntas sobre la base de datos de su negocio.

### Ejemplos de Preguntas que Puede Hacer:
* `¿Cuánto vendí en total este mes?`
* `¿Cuáles son mis clientes con alto riesgo de abandono?`
* `¿Qué productos tienen la mayor rentabilidad o utilidad?`
* `Predice las ventas diarias de la próxima semana.`
* `Muestra un gráfico de la rentabilidad de las sucursales.`

El asistente procesará la pregunta, compilará una sentencia SQL segura aislada a su empresa, consultará los datos y presentará la respuesta junto con un gráfico interactivo relevante.
