<?php
/**
 * FAXEL BI — Controlador de Autenticación
 */
class AuthController extends Controller
{
    private $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        // Modelo inline para no requerir archivo separado en login
        $this->usuarioModel = null;
    }

    public function login(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
            return;
        }

        $this->view('auth/login', [
            'title' => 'Iniciar Sesión — FAXEL BI',
        ], 'auth');
    }

    private function processLogin(): void
    {
        $this->verifyCSRF();

        $email    = $this->post('email', '');
        $password = $this->post('password', '');
        $errors   = [];

        if (!$email)    $errors[] = 'El email es requerido.';
        if (!$password) $errors[] = 'La contraseña es requerida.';

        if (empty($errors)) {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && !$user['locked_until']) {
                if (Security::verifyPassword($password, $user['password_hash'])) {
                    // Reset intentos fallidos
                    $db->prepare("UPDATE usuarios SET failed_logins = 0, ultimo_login = NOW() WHERE id = ?")
                       ->execute([$user['id']]);

                    Session::setUser($user);
                    Logger::audit('LOGIN', 'usuarios', (int)$user['id']);

                    $this->redirect('/dashboard');
                } else {
                    // Incrementar intentos fallidos
                    $fails = $user['failed_logins'] + 1;
                    $lock  = $fails >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
                    $db->prepare("UPDATE usuarios SET failed_logins = ?, locked_until = ? WHERE id = ?")
                       ->execute([$fails, $lock, $user['id']]);

                    $errors[] = 'Credenciales incorrectas.';
                    Logger::warning("Login fallido para: $email | Intentos: $fails");
                }
            } elseif ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $errors[] = 'Cuenta bloqueada temporalmente. Intente en 15 minutos.';
            } else {
                $errors[] = 'Credenciales incorrectas.';
            }
        }

        $this->view('auth/login', [
            'title'  => 'Iniciar Sesión — FAXEL BI',
            'errors' => $errors,
            'email'  => $email,
        ], 'auth');
    }

    public function logout(array $params = []): void
    {
        Logger::audit('LOGOUT', 'usuarios', (int)(Session::get('user')['id'] ?? 0));
        Session::destroy();
        $this->redirect('/login');
    }
}
