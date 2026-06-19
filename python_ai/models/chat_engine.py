"""
FAXEL BI — Chat Engine IA
SQL Analítico + LLM opcional (OpenAI / Ollama)
"""

import re
from typing import Optional, Dict, List, Any
from utils.db_connector import DBConnector


class ChatEngine:
    """
    Motor de chat empresarial con análisis SQL + LLM.
    Identifica la intención de la pregunta, ejecuta SQL
    y genera respuesta en lenguaje natural.
    """

    INTENCIONES = {
        'ventas_mes':      r'ven(ta|dí|di|do).*mes|cuanto.*vend|mes.*actual',
        'ventas_hoy':      r'ven(ta|dí|di).*hoy|hoy.*ven',
        'sucursal_caida':  r'sucursal.*(cay|baj|peor)|cual.*sucursal',
        'producto_top':    r'product.*(utilidad|rentabl|gananci|mejor)|mejor.*product',
        'churn':           r'abandon|churn|dej.*comprar|inactiv',
        'resumen':         r'resum|negocio|estado|panoram|general',
        'ticket':          r'ticket|promedio.*venta|venta.*promedio',
        'margen':          r'margen|rentabilidad|utilidad.*total|ganancia.*total',
        'clientes_top':    r'mejor.*client|client.*(top|principal|mayor)',
        'alertas':         r'alert|problem|atenci|anuncio|aviso|notifica',
    }

    def __init__(self):
        self.db = DBConnector()

    def query(self, pregunta: str, historial: List[Dict], usuario: Dict, empresa_id: int) -> Dict[str, Any]:
        """
        Procesa pregunta y retorna respuesta estructurada aislada por empresa.
        """
        p_lower = pregunta.lower()
        intencion = self._detectar_intencion(p_lower)

        # Intentar responder con SQL
        respuesta_sql = self._responder_sql(intencion, p_lower, empresa_id)
        if respuesta_sql:
            # Intentar enriquecer con LLM si está configurado
            texto_final = self._enriquecer_llm(pregunta, respuesta_sql['datos'], usuario) \
                         or respuesta_sql['texto']
            return {
                'texto':   texto_final,
                'grafico': respuesta_sql.get('grafico'),
                'sql':     respuesta_sql.get('sql'),
                'modelo':  respuesta_sql.get('modelo', 'sql_analyzer'),
            }

        return {
            'texto':  self._respuesta_generica(),
            'modelo': 'sql_analyzer',
        }

    def _detectar_intencion(self, pregunta: str) -> Optional[str]:
        for intencion, patron in self.INTENCIONES.items():
            if re.search(patron, pregunta, re.IGNORECASE):
                return intencion
        return None

    def _responder_sql(self, intencion: Optional[str], pregunta: str, empresa_id: int) -> Optional[Dict]:
        try:
            conn   = self.db.get_connection()
            cursor = conn.cursor(dictionary=True)

            if intencion == 'ventas_mes':
                cursor.execute("""
                    SELECT COALESCE(SUM(total),0) AS total,
                           COUNT(*) AS n,
                           COALESCE(AVG(total),0) AS ticket
                    FROM ventas
                    WHERE empresa_id = %s
                      AND YEAR(fecha_venta)=YEAR(CURDATE())
                      AND MONTH(fecha_venta)=MONTH(CURDATE())
                      AND estado='completada'
                """, (empresa_id,))
                r = cursor.fetchone()
                total, n, ticket = float(r['total']), int(r['n']), float(r['ticket'])
                texto = (
                    f"📊 **Ventas del mes actual:**\n\n"
                    f"• **Total:** S/ {total:,.2f}\n"
                    f"• **Transacciones:** {n}\n"
                    f"• **Ticket promedio:** S/ {ticket:,.2f}\n\n"
                    f"{'✅ Desempeño positivo.' if total > 80000 else '⚠️ Desempeño por mejorar.'}"
                )
                return {'texto': texto, 'datos': {'total':total,'n':n,'ticket':ticket},
                        'sql': f'SELECT SUM(total), COUNT(*), AVG(total) FROM ventas WHERE empresa_id={empresa_id} AND mes=actual',
                        'modelo': 'sql_analyzer'}

            elif intencion == 'ventas_hoy':
                cursor.execute("""
                    SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS n
                    FROM ventas
                    WHERE empresa_id = %s AND DATE(fecha_venta)=CURDATE() AND estado='completada'
                """, (empresa_id,))
                r = cursor.fetchone()
                texto = f"📊 **Ventas de hoy:** S/ {float(r['total']):,.2f} ({r['n']} transacciones)"
                return {'texto': texto, 'datos': dict(r), 'modelo': 'sql_analyzer'}

            elif intencion == 'sucursal_caida':
                cursor.execute("""
                    SELECT s.nombre,
                        SUM(CASE WHEN v.fecha_venta >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) THEN v.total ELSE 0 END) AS mes_actual,
                        SUM(CASE WHEN v.fecha_venta BETWEEN DATE_SUB(CURDATE(),INTERVAL 60 DAY)
                                 AND DATE_SUB(CURDATE(),INTERVAL 31 DAY) THEN v.total ELSE 0 END) AS mes_ant
                    FROM sucursales s 
                    LEFT JOIN ventas v ON v.sucursal_id=s.id AND v.estado='completada' AND v.empresa_id = %s
                    WHERE s.empresa_id = %s
                    GROUP BY s.id
                    HAVING mes_ant > 0
                    ORDER BY ((mes_actual-mes_ant)/NULLIF(mes_ant,0)) ASC
                    LIMIT 1
                """, (empresa_id, empresa_id))
                r = cursor.fetchone()
                if r:
                    cambio = round(((r['mes_actual'] - r['mes_ant']) / r['mes_ant']) * 100, 1)
                    texto  = (
                        f"📉 **Sucursal con mayor variación:**\n\n"
                        f"• **{r['nombre']}** — Cambio: **{cambio:+.1f}%**\n"
                        f"• Mes actual: S/ {float(r['mes_actual']):,.0f}\n"
                        f"• Mes anterior: S/ {float(r['mes_ant']):,.0f}"
                    )
                    return {'texto': texto, 'datos': dict(r), 'modelo': 'sql_analyzer'}

            elif intencion == 'producto_top':
                cursor.execute("""
                    SELECT p.nombre, SUM(dv.utilidad_linea) AS utilidad, SUM(dv.subtotal) AS ingresos
                    FROM detalle_venta dv
                    JOIN productos p ON p.id=dv.producto_id
                    JOIN ventas v ON v.id=dv.venta_id AND v.estado='completada' AND v.empresa_id = %s
                    WHERE p.empresa_id = %s
                    GROUP BY p.id ORDER BY utilidad DESC LIMIT 5
                """, (empresa_id, empresa_id))
                rows  = cursor.fetchall()
                lista = "\n".join([f"{i+1}. **{r['nombre']}** → S/ {float(r['utilidad']):,.2f}"
                                   for i, r in enumerate(rows)])
                return {
                    'texto':  f"💰 **Top 5 Productos por Utilidad:**\n\n{lista}",
                    'datos':  rows,
                    'grafico': {
                        'tipo':   'bar',
                        'labels': [r['nombre'] for r in rows],
                        'data':   [float(r['utilidad']) for r in rows],
                    },
                    'modelo': 'sql_analyzer'
                }

            elif intencion == 'churn':
                cursor.execute("""
                    SELECT razon_social, churn_score, churn_riesgo, ultima_compra,
                           DATEDIFF(CURDATE(),ultima_compra) AS dias
                    FROM clientes 
                    WHERE empresa_id = %s AND churn_riesgo='alto' AND activo=1
                    ORDER BY churn_score DESC LIMIT 5
                """, (empresa_id,))
                rows  = cursor.fetchall()
                lista = "\n".join([f"• **{r['razon_social']}** — Score: {r['churn_score']}/100 | {r['dias']} días sin comprar"
                                   for r in rows])
                return {
                    'texto':  f"⚠️ **Clientes en alto riesgo de abandono:**\n\n{lista}\n\n💡 Se recomienda contactar en las próximas 48h.",
                    'datos':  rows,
                    'modelo': 'sql_analyzer'
                }

            elif intencion == 'resumen':
                cursor.execute("""
                    SELECT
                        COALESCE((SELECT SUM(total) FROM ventas WHERE empresa_id=%s AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS ventas,
                        COALESCE((SELECT COUNT(DISTINCT cliente_id) FROM ventas WHERE empresa_id=%s AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS clientes,
                        COALESCE((SELECT AVG(margen_pct) FROM ventas WHERE empresa_id=%s AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS margen,
                        (SELECT COUNT(*) FROM alertas WHERE empresa_id=%s AND estado='nueva') AS alertas
                """, (empresa_id, empresa_id, empresa_id, empresa_id))
                r = cursor.fetchone()
                texto = (
                    f"📋 **Resumen Ejecutivo — {self._mes_actual()}:**\n\n"
                    f"• **Ventas del mes:** S/ {float(r['ventas']):,.2f}\n"
                    f"• **Clientes activos:** {r['clientes']}\n"
                    f"• **Margen promedio:** {float(r['margen']):.1f}%\n"
                    f"• **Alertas pendientes:** {r['alertas']}\n\n"
                    f"{'✅ El negocio muestra buen desempeño.' if float(r['ventas']) > 100000 else '⚠️ Hay oportunidades de mejora. Revisa las alertas.'}"
                )
                return {'texto': texto, 'datos': dict(r), 'modelo': 'sql_analyzer'}

            elif intencion == 'alertas':
                cursor.execute("""
                    SELECT titulo, nivel, mensaje, estado, created_at
                    FROM alertas
                    WHERE empresa_id = %s AND estado != 'resuelta'
                    ORDER BY created_at DESC
                    LIMIT 5
                """, (empresa_id,))
                rows = cursor.fetchall()
                if rows:
                    lista = []
                    for r in rows:
                        icon = "🔴" if r['nivel'] == 'danger' else ("🟡" if r['nivel'] == 'warning' else "ℹ️")
                        lista.append(f"{icon} **{r['titulo']}**:\n  {r['mensaje']}")
                    texto = "📢 **Anuncios y Alertas de Ventas Activas:**\n\n" + "\n\n".join(lista)
                else:
                    texto = "✅ **Todo en orden:** No se detectaron alertas ni anuncios de ventas pendientes para tu empresa."

                return {'texto': texto, 'datos': rows, 'modelo': 'sql_analyzer'}

        except Exception as e:
            return {'texto': f"❌ Error al consultar datos: {str(e)}", 'modelo': 'error'}

        finally:
            try:
                cursor.close()
                conn.close()
            except Exception:
                pass

        return None

    def _enriquecer_llm(self, pregunta: str, datos: Any, usuario: Dict) -> Optional[str]:
        # Enriquecimiento LLM opcional
        try:
            import openai
            import os
            api_key = os.environ.get('OPENAI_API_KEY', '')
            if not api_key:
                return None

            openai.api_key = api_key
            ctx = f"Datos de la base de datos: {str(datos)[:500]}"
            response = openai.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=[
                    {"role": "system", "content": "Eres un analista de negocios experto. Responde de forma concisa y en español."},
                    {"role": "user", "content": f"Pregunta: {pregunta}\n\n{ctx}"},
                ],
                max_tokens=300,
            )
            return response.choices[0].message.content

        except Exception:
            return None

    def _respuesta_generica(self) -> str:
        return (
            "🤖 **Asistente IA FAXEL BI**\n\n"
            "Puedo responder sobre:\n"
            "• 💰 **Ventas** — 'cuánto vendí este mes', 'ventas de hoy'\n"
            "• 🏢 **Sucursales** — 'qué sucursal cayó más'\n"
            "• 📦 **Productos** — 'qué producto genera más utilidad'\n"
            "• 👥 **Clientes** — 'quiénes dejarán de comprar'\n"
            "• 📋 **Resumen** — 'resume mi negocio'\n"
            "• 📊 **Márgenes** — 'cuál es mi rentabilidad'\n\n"
            "¿Qué deseas consultar?"
        )

    def _mes_actual(self) -> str:
        from datetime import date
        meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
        return meses[date.today().month]
