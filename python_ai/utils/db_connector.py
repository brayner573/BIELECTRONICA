"""
FAXEL BI — Conector MySQL para Python
"""

import os


class DBConnector:
    """Conector MySQL/MariaDB para microservicios Python."""

    def __init__(self):
        self.config = {
            'host':     os.environ.get('DB_HOST',     '127.0.0.1'),
            'port':     int(os.environ.get('DB_PORT', 3306)),
            'user':     os.environ.get('DB_USER',     'root'),
            'password': os.environ.get('DB_PASSWORD', ''),
            'database': os.environ.get('DB_NAME',     'faxel_bi'),
            'charset':  'utf8mb4',
        }

    def get_connection(self):
        """Retorna conexión MySQL."""
        try:
            import mysql.connector
            return mysql.connector.connect(**self.config)
        except ImportError:
            raise ImportError(
                "mysql-connector-python no instalado. "
                "Ejecuta: pip install mysql-connector-python"
            )
        except Exception as e:
            raise ConnectionError(f"No se pudo conectar a MySQL: {e}")
