<?php
/**
 * FAXEL BI — Controlador de Entrenamiento de Modelos IA SaaS Multiempresa
 */
class TrainingController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('prediccion.train');
        $empresaId = $this->getEmpresaId();

        $db = Database::getInstance();
        
        // Historial de modelos entrenados de la empresa
        $stmt = $db->prepare("
            SELECT * FROM modelos_ia
            WHERE empresa_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$empresaId]);
        $modelos = $stmt->fetchAll();

        $this->view('prediccion/training', [
            'title'   => 'Entrenamiento del Motor de IA',
            'modelos' => $modelos,
        ]);
    }

    public function entrenar(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('prediccion.train');
        $this->verifyCSRF();
        $empresaId = $this->getEmpresaId();

        $tipoModelo = $this->post('tipo_modelo', 'ventas');
        $errors = [];

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Debe subir un archivo de datos válido (CSV o Excel).'], 400);
            return;
        }

        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName    = $_FILES['file']['name'];
        $fileSize    = $_FILES['file']['size'];
        $fileType    = $_FILES['file']['type'];
        
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['csv', 'xlsx', 'xls'];

        if (!in_array($extension, $allowedExtensions)) {
            $this->json(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se aceptan archivos CSV y Excel.'], 400);
            return;
        }

        // Llamar al microservicio de Python por cURL (usando multipart/form-data)
        $pythonUrl = $this->config['python_ai']['base_url'] . '/train';
        
        $ch = curl_init($pythonUrl);
        
        $curlFile = new CURLFile($fileTmpPath, $fileType, $fileName);
        
        $payload = [
            'empresa_id'  => $empresaId,
            'tipo_modelo' => $tipoModelo,
            'file'        => $curlFile
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 120, // Aumentar timeout para entrenamiento pesado
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            Logger::error("Error de entrenamiento en Python AI: HTTP $httpCode | Respuesta: $response");
            $this->json(['success' => false, 'message' => 'El microservicio de entrenamiento de Python no respondió adecuadamente.'], 500);
            return;
        }

        $resData = json_decode($response, true);
        
        if (isset($resData['success']) && $resData['success']) {
            Logger::audit('ENTRENAMIENTO_IA', 'modelos_ia', 0);
            $this->json([
                'success'   => true,
                'message'   => $resData['message'],
                'algoritmo' => $resData['algoritmo'],
                'metricas'  => $resData['metricas']
            ]);
        } else {
            $this->json(['success' => false, 'message' => $resData['error'] ?? 'Error en entrenamiento.'], 400);
        }
    }
}
