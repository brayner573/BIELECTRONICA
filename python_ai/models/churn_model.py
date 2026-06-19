"""
FAXEL BI — Modelo de Churn Scoring
Regresión Logística + RFM Analysis
"""

import numpy as np
from typing import List, Dict, Any


class ChurnScorer:
    """
    Scoring de abandono de clientes basado en RFM + ML.
    Cuando sklearn está disponible usa regresión logística entrenada,
    de lo contrario usa scoring por reglas con pesos calibrados.
    """

    def __init__(self):
        self.model = None
        self._try_load_model()

    def _try_load_model(self):
        try:
            from sklearn.linear_model import LogisticRegression
            from sklearn.preprocessing import StandardScaler
            self._train_synthetic_model()
        except ImportError:
            pass

    def _train_synthetic_model(self):
        """Entrena modelo con datos sintéticos calibrados."""
        try:
            from sklearn.linear_model import LogisticRegression
            from sklearn.preprocessing import StandardScaler

            np.random.seed(42)
            n = 1000

            # Generar datos sintéticos de clientes
            dias      = np.random.exponential(scale=45, size=n).clip(0, 365)
            compras   = np.random.poisson(lam=15, size=n).clip(1, 100)
            ticket    = np.random.lognormal(mean=7, sigma=1, size=n)
            monto     = compras * ticket

            # Etiqueta: churn = 1 si alto riesgo
            churn = (
                (dias > 90).astype(int) * 0.5 +
                (compras < 5).astype(int) * 0.3 +
                (monto < 5000).astype(int) * 0.2
            )
            churn = (churn + np.random.normal(0, 0.1, n)).clip(0, 1)
            y = (churn > 0.5).astype(int)

            X = np.column_stack([dias, compras, ticket, monto,
                                  np.log1p(monto), np.log1p(compras)])

            self.scaler = StandardScaler()
            X_scaled = self.scaler.fit_transform(X)

            self.model = LogisticRegression(max_iter=500, random_state=42)
            self.model.fit(X_scaled, y)

        except Exception:
            self.model = None

    def score_bulk(self, clientes: List[Dict]) -> List[Dict[str, Any]]:
        """Calcula score de churn para lista de clientes."""
        resultados = []

        for cliente in clientes:
            score, riesgo = self._calcular_score(cliente)
            resultados.append({
                'id':          cliente.get('id'),
                'score':       score,
                'riesgo':      riesgo,
                'accion':      self._recomendar_accion(score),
                'probabilidad':round(score / 100, 3),
            })

        return resultados

    def _calcular_score(self, cliente: Dict) -> tuple:
        """Calcula score individual."""
        dias    = float(cliente.get('dias_sin_compra', 999))
        compras = float(cliente.get('total_compras', 0))
        ticket  = float(cliente.get('ticket_promedio', 0))
        monto   = float(cliente.get('monto_acumulado', 0))

        if self.model is not None:
            try:
                X = np.array([[dias, compras, ticket, monto,
                               np.log1p(monto), np.log1p(compras)]])
                X_scaled = self.scaler.transform(X)
                prob = float(self.model.predict_proba(X_scaled)[0][1])
                score = int(prob * 100)
            except Exception:
                score = self._reglas_score(dias, compras, monto)
        else:
            score = self._reglas_score(dias, compras, monto)

        score  = max(0, min(100, score))
        riesgo = 'alto' if score >= 70 else ('medio' if score >= 40 else 'bajo')
        return score, riesgo

    def _reglas_score(self, dias: float, compras: float, monto: float) -> int:
        """Scoring por reglas de negocio (fallback)."""
        score = 0

        # Recencia (0-50 pts)
        if   dias <= 30:  score += 5
        elif dias <= 60:  score += 20
        elif dias <= 90:  score += 35
        elif dias <= 180: score += 50
        else:             score += 70

        # Frecuencia (0-30 pts)
        if   compras >= 20: score += 0
        elif compras >= 10: score += 10
        elif compras >= 5:  score += 20
        else:               score += 30

        # Monetario inverso (0-20 pts)
        if   monto >= 50000: score -= 10
        elif monto >= 20000: score -= 5
        elif monto < 5000:   score += 10

        return score

    def _recomendar_accion(self, score: int) -> str:
        if score >= 80:   return 'Contacto inmediato — Oferta retención premium'
        elif score >= 70: return 'Llamada personal del ejecutivo de cuenta'
        elif score >= 60: return 'Campaña email + descuento especial'
        elif score >= 40: return 'Newsletter mensual + recordatorio'
        else:             return 'Mantenimiento de relación estándar'
