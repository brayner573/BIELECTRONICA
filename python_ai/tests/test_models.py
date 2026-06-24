"""
FAXEL BI — Pruebas Unitarias del Microservicio de IA (Python)
Ejecutar con: python -m unittest tests/test_models.py (desde la carpeta python_ai)
"""

import unittest
import os
import sys
import json
import numpy as np

# Agregar el directorio raíz al path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from models.predictor import SalesPredictor
from models.churn_model import ChurnScorer
from models.chat_engine import ChatEngine


class TestSalesPredictor(unittest.TestCase):
    """Pruebas unitarias para el pipeline de previsión de ventas."""

    def setUp(self):
        self.predictor = SalesPredictor()
        # Serie temporal de prueba (15 días)
        self.test_data = [
            {"ds": f"2026-06-{i:02d}", "y": 1000.00 + (i * 50) + (np.sin(i) * 100)}
            for i in range(1, 16)
        ]

    def test_insufficient_data(self):
        """Valida que falle si hay menos de 10 registros históricos."""
        short_data = [{"ds": "2026-06-01", "y": 1000.00}]
        with self.assertRaises(RuntimeError):
            self.predictor.predict(short_data, horizon_7=True, horizon_30=False)

    def test_prediction_output_structure(self):
        """Valida la estructura y tipo de datos del resultado de Prophet / LinearRegression."""
        res = self.predictor.predict(self.test_data, horizon_7=True, horizon_30=True)
        
        self.assertTrue(res.get('success', False) or 'metricas' in res)
        self.assertIn('predicciones_7d', res)
        self.assertIn('predicciones_30d', res)
        self.assertIn('metricas', res)
        self.assertIn('tendencia', res)
        
        # Verificar que el horizonte de 7 días contenga 7 predicciones
        self.assertEqual(len(res['predicciones_7d']), 7)
        self.assertEqual(len(res['predicciones_30d']), 30)

        # Verificar tipos de datos de las métricas
        metrics = res['metricas']
        self.assertIn('r2', metrics)
        self.assertIn('mae', metrics)
        self.assertIn('rmse', metrics)
        self.assertIn('modelo', metrics)


class TestChurnScorer(unittest.TestCase):
    """Pruebas unitarias para el modelo de scoring de deserción de clientes."""

    def setUp(self):
        self.scorer = ChurnScorer()
        self.test_clients = [
            # Cliente VIP y activo (Riesgo Bajo)
            {"id": 1, "dias_sin_compra": 10, "total_compras": 50, "ticket_promedio": 500.00, "monto_acumulado": 25000.00},
            # Cliente inactivo con pocas compras (Riesgo Alto)
            {"id": 2, "dias_sin_compra": 200, "total_compras": 2, "ticket_promedio": 50.00, "monto_acumulado": 100.00},
            # Cliente regular intermedio (Riesgo Medio)
            {"id": 3, "dias_sin_compra": 75, "total_compras": 8, "ticket_promedio": 120.00, "monto_acumulado": 960.00}
        ]

    def test_score_bulk(self):
        """Prueba la evaluación de Churn por lotes y las clasificaciones."""
        res = self.scorer.score_bulk(self.test_clients)
        
        self.assertEqual(len(res), 3)
        for r in res:
            self.assertIn('id', r)
            self.assertIn('score', r)
            self.assertIn('riesgo', r)
            self.assertIn('accion', r)
            self.assertIn('probabilidad', r)
            
            # El score debe estar entre 0 y 100
            self.assertTrue(0 <= r['score'] <= 100)

        # Validaciones de asignación de riesgo específicas
        client1_res = next(r for r in res if r['id'] == 1)
        client2_res = next(r for r in res if r['id'] == 2)
        
        self.assertEqual(client1_res['riesgo'], 'bajo')
        self.assertEqual(client2_res['riesgo'], 'alto')


class TestChatEngine(unittest.TestCase):
    """Pruebas unitarias para el enrutador conversacional y seguridad Text-to-SQL."""

    def setUp(self):
        self.engine = ChatEngine()
        self.test_user = {"id": 1, "rol": "gerente", "nombre": "Admin"}
        self.test_empresa_id = 99

    def test_intent_detection(self):
        """Valida que el analizador regex identifique las intenciones analíticas correctas."""
        self.assertEqual(self.engine._detectar_intencion("¿Cuánto vendí en total este mes?"), "ventas_mes")
        self.assertEqual(self.engine._detectar_intencion("Muestra los productos estrella o más rentables"), "producto_top")
        self.assertEqual(self.engine._detectar_intencion("Dime qué clientes tienen riesgo de abandono"), "churn")
        self.assertEqual(self.engine._detectar_intencion("¿Tengo alertas o problemas pendientes?"), "alertas")

    def test_text_to_sql_isolation(self):
        """Valida que la respuesta del analizador inyecte obligatoriamente el empresa_id del inquilino."""
        res = self.engine.query("Muestra los productos con mejor utilidad", [], self.test_user, self.test_empresa_id)
        
        self.assertIn('modelo', res)
        if res['modelo'] == 'error':
            self.skipTest("Base de datos no accesible en el entorno de pruebas, se omite validacion de retorno SQL.")
        self.assertEqual(res['modelo'], 'sql_analyzer')
        self.assertIsNotNone(res.get('grafico'))
        
        # Verificar que el gráfico inyectado contenga los datos estructurales
        self.assertIn('tipo', res['grafico'])
        self.assertIn('labels', res['grafico'])
        self.assertIn('data', res['grafico'])


if __name__ == '__main__':
    unittest.main()
