<?php
/**
 * FAXEL BI — Controlador de Registro Público SaaS
 */
class RegisterController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function show(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/register', [
            'title' => 'Registro de Empresa — FAXEL BI',
        ], 'auth');
    }

    public function store(): void
    {
        $this->verifyCSRF();

        $nombre        = $this->post('nombre', '');
        $apellido      = $this->post('apellido', '');
        $razonSocial   = $this->post('empresa', '');
        $ruc           = $this->post('ruc', '');
        $email         = $this->post('email', '');
        $celular       = $this->post('celular', '');
        $password      = $this->post('password', '');
        $direccion     = $this->post('direccion', '');
        $sector        = $this->post('sector', '');
        $empleados     = (int)$this->post('empleados', 1);
        $errors        = [];

        // Validaciones
        if (!$nombre)      $errors[] = 'El nombre del administrador es requerido.';
        if (!$apellido)    $errors[] = 'El apellido del administrador es requerido.';
        if (!$razonSocial) $errors[] = 'La razón social de la empresa es requerida.';
        
        if (!preg_match('/^[0-9]{11}$/', $ruc)) {
            $errors[] = 'El RUC debe tener exactamente 11 dígitos numéricos.';
        }
        
        if (!Security::isEmail($email)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        
        $passErrors = Security::validatePassword($password);
        if (!empty($passErrors)) {
            $errors = array_merge($errors, $passErrors);
        }

        // Conectar a la base de datos
        $db = Database::getInstance();

        // Verificar RUC duplicado
        $stmt = $db->prepare("SELECT id FROM empresas WHERE ruc = ? LIMIT 1");
        $stmt->execute([$ruc]);
        if ($stmt->fetch()) {
            $errors[] = 'El RUC ingresado ya está registrado.';
        }

        // Verificar Email duplicado
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'El correo electrónico ya está en uso.';
        }

        // Procesamiento del logo
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['logo']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/logos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $fileName = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
                    $logoPath = '/uploads/logos/' . $fileName;
                } else {
                    $errors[] = 'Error al subir el logo corporativo.';
                }
            } else {
                $errors[] = 'El logo debe ser una imagen (JPG, PNG, WEBP).';
            }
        }

        if (!empty($errors)) {
            $this->view('auth/register', [
                'title'     => 'Registro de Empresa — FAXEL BI',
                'errors'    => $errors,
                'nombre'    => $nombre,
                'apellido'  => $apellido,
                'empresa'   => $razonSocial,
                'ruc'       => $ruc,
                'email'     => $email,
                'celular'   => $celular,
                'direccion' => $direccion,
                'sector'    => $sector,
                'empleados' => $empleados
            ], 'auth');
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Crear empresa
            $stmt = $db->prepare("INSERT INTO empresas (razon_social, ruc, email, telefono, direccion, sector, empleados_count, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$razonSocial, $ruc, $email, $celular, $direccion, $sector, $empleados, $logoPath]);
            $empresaId = (int)$db->lastInsertId();

            // 2. Crear sucursal matriz por defecto
            $stmt = $db->prepare("INSERT INTO sucursales (empresa_id, nombre, ciudad, direccion, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $empresaId, 
                'Oficina Principal ' . $razonSocial, 
                'Lima', 
                $direccion ?: 'Dirección Principal', 
                $celular, 
                $email
            ]);
            $sucursalId = (int)$db->lastInsertId();

            // 3. Crear usuario dueño/administrador ('empresa')
            $passwordHash = Security::hashPassword($password);
            $stmt = $db->prepare("INSERT INTO usuarios (empresa_id, sucursal_id, nombre, apellido, email, password_hash, rol) VALUES (?, ?, ?, ?, ?, ?, 'empresa')");
            $stmt->execute([$empresaId, $sucursalId, $nombre, $apellido, $email, $passwordHash]);
            $usuarioId = (int)$db->lastInsertId();

            // Auditar la creación
            $stmt = $db->prepare("INSERT INTO audit_log (empresa_id, usuario_id, accion, tabla, registro_id, datos_desp) VALUES (?, ?, 'REGISTRO_SAAS', 'empresas', ?, ?)");
            $stmt->execute([$empresaId, $usuarioId, $empresaId, json_encode([
                'empresa' => $razonSocial, 
                'ruc' => $ruc, 
                'admin' => $nombre . ' ' . $apellido
            ])]);

            $db->commit();

            // Iniciar sesión automáticamente
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$usuarioId]);
            $user = $stmt->fetch();
            
            Session::setUser($user);
            $this->redirect('/dashboard');

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error("Error en registro de empresa: " . $e->getMessage());
            
            $this->view('auth/register', [
                'title'  => 'Registro de Empresa — FAXEL BI',
                'errors' => ['Ocurrió un error inesperado al procesar el registro. Intente de nuevo.'],
                'nombre' => $nombre,
                'email'  => $email
            ], 'auth');
        }
    }
}
