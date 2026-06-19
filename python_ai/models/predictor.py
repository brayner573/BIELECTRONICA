"""
FAXEL BI — Modelo de Predicción de Ventas
Prophet + XGBoost con validación cruzada y métricas de error
"""

import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from typing import Optional


class SalesPredictor:
    """Predictor de ventas usando Prophet como modelo principal."""

    def __init__(self):
        self.model_prophet = None
        self.model_xgb     = None

    def predict(self, data: list, horizon_7: bool = True, horizon_30: bool = True) -> dict:
        """
        Realiza predicción de ventas temporales.

        Args:
            data: Lista de {"ds": "YYYY-MM-DD", "y": float}
            horizon_7: Si predecir 7 días
            horizon_30: Si predecir 30 días

        Returns:
            Diccionario con predicciones y métricas
        """
        try:
            df = pd.DataFrame(data)
            df['ds'] = pd.to_datetime(df['ds'])
            df['y']  = pd.to_numeric(df['y'], errors='coerce').fillna(0)
            df = df.sort_values('ds').reset_index(drop=True)

            if len(df) < 10:
                raise ValueError("Se requieren al menos 10 registros para entrenar el modelo.")

            # Seleccionar modelo cargado o entrenar uno en vivo
            model = self.model_prophet
            
            # Si no hay modelo cargado, intentar usar Prophet o fallback a LinearRegression
            if model is None:
                try:
                    from prophet import Prophet
                    model = Prophet(
                        seasonality_mode='multiplicative',
                        yearly_seasonality=True,
                        weekly_seasonality=True,
                        daily_seasonality=False,
                        changepoint_prior_scale=0.1,
                        interval_width=0.80,
                    )
                    model.add_country_holidays(country_name='PE')
                    model.fit(df)
                except Exception:
                    # Fallback on the fly to LinearRegression
                    from sklearn.linear_model import LinearRegression
                    df['ordinal'] = df['ds'].apply(lambda x: x.toordinal())
                    model = LinearRegression()
                    model.fit(df[['ordinal']].values, df['y'].values)

            # --- INFERENCIA LINEAR REGRESSION ---
            if type(model).__name__ == 'LinearRegression':
                ultimo_ds = df['ds'].max()
                
                # Predicciones 7 días
                preds_7d = []
                if horizon_7:
                    for i in range(1, 8):
                        dia = ultimo_ds + timedelta(days=i)
                        ord_val = dia.toordinal()
                        yhat = float(model.predict([[ord_val]])[0])
                        preds_7d.append({
                            'ds':         dia.strftime('%Y-%m-%d'),
                            'yhat':       max(0.0, round(yhat, 2)),
                            'yhat_lower': max(0.0, round(yhat * 0.9, 2)),
                            'yhat_upper': max(0.0, round(yhat * 1.1, 2)),
                            'trend':      round(yhat, 2),
                        })

                # Predicciones 30 días
                preds_30d = []
                if horizon_30:
                    for i in range(1, 31):
                        dia = ultimo_ds + timedelta(days=i)
                        ord_val = dia.toordinal()
                        yhat = float(model.predict([[ord_val]])[0])
                        preds_30d.append({
                            'ds':         dia.strftime('%Y-%m-%d'),
                            'yhat':       max(0.0, round(yhat, 2)),
                            'yhat_lower': max(0.0, round(yhat * 0.85, 2)),
                            'yhat_upper': max(0.0, round(yhat * 1.15, 2)),
                        })

                # Métricas
                df['ordinal'] = df['ds'].apply(lambda x: x.toordinal())
                X_in = df[['ordinal']].values
                y_true = df['y'].values
                y_pred = model.predict(X_in)
                
                from sklearn.metrics import mean_absolute_error, mean_squared_error
                mae  = float(mean_absolute_error(y_true, y_pred))
                rmse = float(np.sqrt(mean_squared_error(y_true, y_pred)))
                r2   = float(model.score(X_in, y_true))

                return {
                    'predicciones_7d':  preds_7d,
                    'predicciones_30d': preds_30d,
                    'metricas': {
                        'r2':    round(r2 * 100, 2),
                        'mae':   round(mae, 4),
                        'rmse':  round(rmse, 4),
                        'modelo':'linear_regression',
                        'n_obs': len(df),
                    },
                    'tendencia': _detectar_tendencia(preds_7d),
                }

            # --- INFERENCIA PROPHET ---
            else:
                from sklearn.metrics import mean_absolute_error, mean_squared_error
                
                # Predicciones 7 días
                preds_7d = []
                if horizon_7:
                    future7  = model.make_future_dataframe(periods=7, freq='D')
                    forecast7 = model.predict(future7)
                    last7    = forecast7.tail(7)
                    preds_7d = [
                        {
                            'ds':         row['ds'].strftime('%Y-%m-%d'),
                            'yhat':       max(0.0, round(float(row['yhat']), 2)),
                            'yhat_lower': max(0.0, round(float(row['yhat_lower']), 2)),
                            'yhat_upper': max(0.0, round(float(row['yhat_upper']), 2)),
                            'trend':      round(float(row.get('trend', row['yhat'])), 2),
                        }
                        for _, row in last7.iterrows()
                    ]

                # Predicciones 30 días
                preds_30d = []
                if horizon_30:
                    future30  = model.make_future_dataframe(periods=30, freq='D')
                    forecast30 = model.predict(future30)
                    last30    = forecast30.tail(30)
                    preds_30d = [
                        {
                            'ds':         row['ds'].strftime('%Y-%m-%d'),
                            'yhat':       max(0.0, round(float(row['yhat']), 2)),
                            'yhat_lower': max(0.0, round(float(row['yhat_lower']), 2)),
                            'yhat_upper': max(0.0, round(float(row['yhat_upper']), 2)),
                        }
                        for _, row in last30.iterrows()
                    ]

                # Métricas
                forecast_all = model.predict(df[['ds']])
                y_true = df['y'].values
                y_pred = forecast_all['yhat'].values

                mae  = float(mean_absolute_error(y_true, y_pred))
                rmse = float(np.sqrt(mean_squared_error(y_true, y_pred)))
                ss_res = np.sum((y_true - y_pred) ** 2)
                ss_tot = np.sum((y_true - np.mean(y_true)) ** 2)
                r2   = float(1 - ss_res / ss_tot) if ss_tot > 0 else 0.0

                return {
                    'predicciones_7d':  preds_7d,
                    'predicciones_30d': preds_30d,
                    'metricas': {
                        'r2':    round(r2 * 100, 2),
                        'mae':   round(mae, 4),
                        'rmse':  round(rmse, 4),
                        'modelo':'prophet',
                        'n_obs': len(df),
                    },
                    'tendencia': _detectar_tendencia(preds_7d),
                }

        except Exception as e:
            raise RuntimeError(f"Error en predicción: {e}")

    def predict_xgboost(self, data: list) -> dict:
        """
        Predicción con XGBoost usando features de ventana temporal.
        """
        try:
            import xgboost as xgb
            from sklearn.model_selection import train_test_split
            from sklearn.metrics import mean_absolute_error, mean_squared_error
            from sklearn.preprocessing import StandardScaler

            df = pd.DataFrame(data)
            df['ds'] = pd.to_datetime(df['ds'])
            df['y']  = pd.to_numeric(df['y'], errors='coerce').fillna(0)
            df = df.sort_values('ds').reset_index(drop=True)

            # Feature engineering
            df['dia_semana']  = df['ds'].dt.dayofweek
            df['dia_mes']     = df['ds'].dt.day
            df['mes']         = df['ds'].dt.month
            df['semana']      = df['ds'].dt.isocalendar().week.astype(int)
            df['lag_7']       = df['y'].shift(7).fillna(df['y'].mean())
            df['lag_14']      = df['y'].shift(14).fillna(df['y'].mean())
            df['lag_30']      = df['y'].shift(30).fillna(df['y'].mean())
            df['rolling_7']   = df['y'].shift(1).rolling(7).mean().fillna(df['y'].mean())
            df['rolling_30']  = df['y'].shift(1).rolling(30).mean().fillna(df['y'].mean())

            features = ['dia_semana','dia_mes','mes','semana','lag_7','lag_14','lag_30','rolling_7','rolling_30']
            X = df[features].values
            y = df['y'].values

            X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, shuffle=False)

            model = xgb.XGBRegressor(
                n_estimators=200,
                max_depth=5,
                learning_rate=0.05,
                subsample=0.8,
                colsample_bytree=0.8,
                random_state=42,
                verbosity=0,
            )
            model.fit(X_train, y_train,
                      eval_set=[(X_test, y_test)],
                      verbose=False)

            y_pred = model.predict(X_test)
            mae  = float(mean_absolute_error(y_test, y_pred))
            rmse = float(np.sqrt(mean_squared_error(y_test, y_pred)))

            # Importancia de features
            importances = dict(zip(features, model.feature_importances_.tolist()))

            return {
                'metricas': {
                    'mae':          round(mae, 4),
                    'rmse':         round(rmse, 4),
                    'modelo':       'xgboost',
                    'feature_imp':  importances,
                }
            }

        except ImportError:
            raise ImportError("XGBoost no instalado. Ejecuta: pip install xgboost")


def _detectar_tendencia(preds: list) -> str:
    if not preds or len(preds) < 2:
        return 'estable'
    inicio = preds[0]['yhat']
    fin    = preds[-1]['yhat']
    if inicio == 0:
        return 'estable'
    cambio = (fin - inicio) / inicio * 100
    if cambio > 5:   return 'positiva'
    elif cambio < -5: return 'negativa'
    return 'estable'
