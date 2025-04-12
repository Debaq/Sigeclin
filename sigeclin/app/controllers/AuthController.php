<?php
/**
 * Controlador de Autenticación
 * 
 * Este controlador maneja todas las operaciones relacionadas con la autenticación
 * de usuarios, incluyendo login, logout, recuperación de contraseña y verificación
 * de permisos.
 */

class AuthController {
    private $userModel;
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->userModel = new User();
        $this->db = Database::getInstance();
    }
    
    /**
     * Procesar inicio de sesión (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado y token si es exitoso
     */
    public function login($request) {
        // Extraer datos del request
        $data = $request->getJsonData();
        
        // Validar datos requeridos
        if (!isset($data['correo']) || !isset($data['contrasena'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'El correo y la contraseña son requeridos'
            ];
        }
        
        // Limpiar datos
        $email = filter_var(trim($data['correo']), FILTER_SANITIZE_EMAIL);
        $password = trim($data['contrasena']);
        
        // Verificar formato de correo
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Formato de correo electrónico inválido'
            ];
        }
        
        // Buscar usuario por email
        $user = $this->userModel->getByEmail($email);
        
        // Si no existe el usuario o está inactivo
        if (!$user || !$user['activo']) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'Credenciales inválidas'
            ];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['contrasena'])) {
            // Registrar intento fallido
            $this->recordFailedLoginAttempt($user['id']);
            
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'Credenciales inválidas'
            ];
        }
        
        // Actualizar último acceso
        $this->userModel->updateLastAccess($user['id']);
        
        // Generar token JWT
        $token = $this->generateJwtToken($user);
        
        // Preparar datos de usuario (sin información sensible)
        $userData = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rut' => $user['rut'],
            'tipo' => $user['tipo'],
            'ultimo_acceso' => $user['ultimo_acceso']
        ];
        
        // Registrar inicio de sesión en logs
        $this->logActivity($user['id'], 'login', 'usuarios', $user['id'], null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        
        return [
            'status' => 'success',
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token,
                'user' => $userData,
                'expires_in' => Config::get('security.jwt_expiration')
            ]
        ];
    }
    
    /**
     * Procesar cierre de sesión (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function logout($request) {
        // En una implementación con JWT, el logout se maneja principalmente del lado del cliente
        // eliminando el token. Aquí podríamos implementar una lista negra de tokens si fuera necesario.
        
        // Obtenemos el ID del usuario desde el token
        $user = $this->getCurrentUser();
        
        if ($user) {
            // Registrar cierre de sesión en logs
            $this->logActivity($user['id'], 'logout', 'usuarios', $user['id'], null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        }
        
        return [
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ];
    }
    
    /**
     * Solicitar recuperación de contraseña (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function requestPasswordReset($request) {
        // Extraer datos del request
        $data = $request->getJsonData();
        
        // Validar datos requeridos
        if (!isset($data['correo'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'El correo electrónico es requerido'
            ];
        }
        
        // Limpiar datos
        $email = filter_var(trim($data['correo']), FILTER_SANITIZE_EMAIL);
        
        // Verificar formato de correo
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Formato de correo electrónico inválido'
            ];
        }
        
        // Buscar usuario por email
        $user = $this->userModel->getByEmail($email);
        
        // Si no existe el usuario o está inactivo
        if (!$user || !$user['activo']) {
            // Por seguridad, no revelar si el correo existe o no
            return [
                'status' => 'success',
                'message' => 'Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña'
            ];
        }
        
        // Generar token de recuperación
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Guardar token en la base de datos
        $this->userModel->saveResetToken($user['id'], $token, $expiry);
        
        // URL para reset de contraseña
        $resetUrl = Config::get('app.base_url') . '/reset-password/' . $token;
        
        // Si el envío de correos está habilitado
        if (Config::get('mail.enabled')) {
            // Aquí iría la lógica para enviar el correo con PHPMailer
            // ...
        }
        
        // Registrar solicitud en logs
        $this->logActivity($user['id'], 'solicitud_reset_password', 'usuarios', $user['id'], null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        
        return [
            'status' => 'success',
            'message' => 'Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña',
            'dev_reset_url' => Config::get('app.environment') === 'development' ? $resetUrl : null
        ];
    }
    
    /**
     * Validar token de recuperación (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function validateResetToken($request) {
        // Extraer token del request
        $token = $request->getParam('token');
        
        if (!$token) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token no proporcionado'
            ];
        }
        
        // Verificar token en la base de datos
        $user = $this->userModel->getUserByResetToken($token);
        
        if (!$user) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token inválido'
            ];
        }
        
        // Verificar si el token ha expirado
        if (strtotime($user['vencimiento_token']) < time()) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'El token ha expirado'
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Token válido'
        ];
    }
    
    /**
     * Actualizar contraseña con token (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function resetPassword($request) {
        // Extraer datos del request
        $data = $request->getJsonData();
        
        // Validar datos requeridos
        if (!isset($data['token']) || !isset($data['nueva_contrasena']) || !isset($data['confirmar_contrasena'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token y contraseñas son requeridos'
            ];
        }
        
        // Verificar que las contraseñas coincidan
        if ($data['nueva_contrasena'] !== $data['confirmar_contrasena']) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Las contraseñas no coinciden'
            ];
        }
        
        // Validar fortaleza de la contraseña
        if (strlen($data['nueva_contrasena']) < 8) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }
        
        // Verificar token en la base de datos
        $user = $this->userModel->getUserByResetToken($data['token']);
        
        if (!$user) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Token inválido'
            ];
        }
        
        // Verificar si el token ha expirado
        if (strtotime($user['vencimiento_token']) < time()) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'El token ha expirado'
            ];
        }
        
        // Hash de la nueva contraseña
        $passwordHash = password_hash(
            $data['nueva_contrasena'], 
            Config::get('security.password_algo'), 
            ['cost' => Config::get('security.password_cost')]
        );
        
        // Actualizar contraseña y limpiar token
        $this->userModel->updatePassword($user['id'], $passwordHash);
        
        // Registrar cambio en logs
        $this->logActivity($user['id'], 'reset_password', 'usuarios', $user['id'], null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        
        return [
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente'
        ];
    }
    
    /**
     * Cambiar contraseña (usuario autenticado)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function changePassword($request) {
        // Verificar autenticación
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'No autorizado'
            ];
        }
        
        // Extraer datos del request
        $data = $request->getJsonData();
        
        // Validar datos requeridos
        if (!isset($data['contrasena_actual']) || !isset($data['nueva_contrasena']) || !isset($data['confirmar_contrasena'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Todos los campos son requeridos'
            ];
        }
        
        // Verificar que las contraseñas coincidan
        if ($data['nueva_contrasena'] !== $data['confirmar_contrasena']) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Las contraseñas no coinciden'
            ];
        }
        
        // Validar fortaleza de la contraseña
        if (strlen($data['nueva_contrasena']) < 8) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }
        
        // Obtener datos completos del usuario
        $user = $this->userModel->getById($currentUser['id']);
        
        // Verificar contraseña actual
        if (!password_verify($data['contrasena_actual'], $user['contrasena'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'La contraseña actual es incorrecta'
            ];
        }
        
        // Hash de la nueva contraseña
        $passwordHash = password_hash(
            $data['nueva_contrasena'], 
            Config::get('security.password_algo'), 
            ['cost' => Config::get('security.password_cost')]
        );
        
        // Actualizar contraseña
        $this->userModel->updatePassword($user['id'], $passwordHash);
        
        // Registrar cambio en logs
        $this->logActivity($user['id'], 'cambio_password', 'usuarios', $user['id'], null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        
        return [
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente'
        ];
    }
    
    /**
     * Obtener información del usuario actual (API)
     * 
     * @param object $request Objeto Request con los datos de la petición
     * @return array Respuesta JSON con resultado
     */
    public function getProfile($request) {
        // Verificar autenticación
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'No autorizado'
            ];
        }
        
        // Obtener datos completos del usuario
        $user = $this->userModel->getById($currentUser['id']);
        
        // Preparar datos de usuario (sin información sensible)
        $userData = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rut' => $user['rut'],
            'correo' => $user['correo'],
            'telefono' => $user['telefono'],
            'tipo' => $user['tipo'],
            'fecha_creacion' => $user['fecha_creacion'],
            'ultimo_acceso' => $user['ultimo_acceso']
        ];
        
        // Si es estudiante, obtener su perfil
        if ($user['tipo'] === 'estudiante') {
            $studentProfile = new StudentProfile();
            $profile = $studentProfile->getByUserId($user['id']);
            
            if ($profile) {
                $userData['perfil_estudiante'] = $profile;
            }
        }
        
        // Si es coordinador, obtener sus carreras asignadas
        if ($user['tipo'] === 'coordinador') {
            $query = "
                SELECT cc.id, c.id as carrera_id, c.nombre, c.codigo, c.color
                FROM coordinadores_carreras cc
                JOIN carreras c ON cc.carrera_id = c.id
                WHERE cc.coordinador_id = :coordinador_id AND cc.activo = 1
            ";
            
            $params = [':coordinador_id' => $user['id']];
            $carreras = $this->db->query($query, $params);
            
            $userData['carreras_asignadas'] = $carreras;
        }
        
        return [
            'status' => 'success',
            'data' => $userData
        ];
    }
    
    /**
     * Verificar si el usuario actual tiene el rol especificado
     * 
     * @param string|array $roles Rol o roles permitidos
     * @return bool True si el usuario tiene el rol, false en caso contrario
     */
    public function hasRole($roles) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($currentUser['tipo'], $roles);
    }
    
    /**
     * Obtener el usuario actual a partir del token JWT
     * 
     * @return array|null Datos del usuario o null si no está autenticado
     */
    public function getCurrentUser() {
        // Obtener token de los headers
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
        try {
            // Verificar y decodificar token
            $decoded = $this->decodeJwtToken($token);
            
            // Verificar si el token ha expirado
            if ($decoded->exp < time()) {
                return null;
            }
            
            // Verificar si el usuario existe y está activo
            $user = $this->userModel->getById($decoded->user_id);
            
            if (!$user || !$user['activo']) {
                return null;
            }
            
            return $user;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Generar token JWT para el usuario
     * 
     * @param array $user Datos del usuario
     * @return string Token JWT
     */
    private function generateJwtToken($user) {
        $secret = Config::get('security.jwt_secret');
        $expiration = time() + Config::get('security.jwt_expiration');
        
        $payload = [
            'iat' => time(),
            'exp' => $expiration,
            'user_id' => $user['id'],
            'user_type' => $user['tipo']
        ];
        
        // Generar token con Firebase JWT
        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * Decodificar token JWT
     * 
     * @param string $token Token JWT
     * @return object Payload decodificado
     */
    private function decodeJwtToken($token) {
        $secret = Config::get('security.jwt_secret');
        
        // Decodificar token con Firebase JWT
        return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
    }
    
    /**
     * Registrar intento fallido de inicio de sesión
     * 
     * @param int $userId ID del usuario
     */
    private function recordFailedLoginAttempt($userId) {
        // Aquí se implementaría la lógica para registrar intentos fallidos
        // y bloquear la cuenta si se supera el límite
        
        // Registrar en logs
        $this->logActivity($userId, 'login_fallido', 'usuarios', $userId, null, null, $_SERVER['REMOTE_ADDR'] ?? '');
    }
    
    /**
     * Registrar actividad en logs
     * 
     * @param int $userId ID del usuario
     * @param string $action Acción realizada
     * @param string $table Tabla afectada
     * @param int $recordId ID del registro afectado
     * @param string $oldData Datos anteriores (opcional)
     * @param string $newData Nuevos datos (opcional)
     * @param string $ip Dirección IP (opcional)
     * @return bool True si se registró correctamente
     */
    private function logActivity($userId, $action, $table, $recordId, $oldData = null, $newData = null, $ip = null) {
        try {
            $query = "
                INSERT INTO logs_sistema (
                    usuario_id, accion, tabla_afectada, registro_afectado, 
                    datos_anteriores, datos_nuevos, direccion_ip
                ) VALUES (
                    :usuario_id, :accion, :tabla_afectada, :registro_afectado,
                    :datos_anteriores, :datos_nuevos, :direccion_ip
                )
            ";
            
            $params = [
                ':usuario_id' => $userId,
                ':accion' => $action,
                ':tabla_afectada' => $table,
                ':registro_afectado' => $recordId,
                ':datos_anteriores' => $oldData ? json_encode($oldData) : null,
                ':datos_nuevos' => $newData ? json_encode($newData) : null,
                ':direccion_ip' => $ip
            ];
            
            $this->db->prepare($query);
            return $this->db->execute($params);
        } catch (Exception $e) {
            error_log('Error al registrar actividad: ' . $e->getMessage());
            return false;
        }
    }
}