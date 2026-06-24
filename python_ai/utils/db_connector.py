"""
FAXEL BI — Conector MySQL para Python
"""

import os
from dotenv import load_dotenv

# Cargar variables de entorno desde python_ai/.env
env_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), '.env')
load_dotenv(env_path)


class DBConnector:
    """Conector MySQL/MariaDB para microservicios Python."""

    def __init__(self):
        self.config = {
            'host':     os.environ.get('DB_HOST',     'localhost'),
            'port':     int(os.environ.get('DB_PORT', 3306)),
            'user':     os.environ.get('DB_USER',     'iaws_brayner1'),
            'password': os.environ.get('DB_PASSWORD', 'brayner45A*'),
            'database': os.environ.get('DB_NAME',     'iaws_faxel_bi'),
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
