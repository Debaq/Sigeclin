<?php
/**
 * Sistema de Gestión de Sesiones - SIGECLIN
 * 
 * Esta clase maneja las sesiones de usuario en el sistema SIGECLIN.
 * Proporciona métodos para iniciar/terminar sesiones, almacenar/recuperar datos
 * y gestionar mensajes flash entre redirecciones.
 * 
 * @package SIGECLIN
 * @subpackage Core
 */

class Session {
    /**
     * Indicador de si la sesión ha sido iniciada
     *
     * @var boolean
     */
    private static $sessionStarted = false;
    
    /**
     * Tiempo de vida de la sesión en segundos (8 horas por defecto)
     *
     * @var integer
     */
    private static $sessionLifetime = 28800;
    
    /**
     * Inicia la sesión si aún no ha sido iniciada
     *
     * @return void
     */
    public static function start() {
        if (self::$sessionStarted) {
            return;
        }
        
        // Configuración de seguridad para cookies de sesión
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            self::$sessionLifetime,
            $cookieParams["path"],
            $cookieParams["domain"],
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',  // Secure flag
            true                                                      // HttpOnly flag
        );
        
        // Establecer nombre de sesión personalizado
        session_name('SIGECLIN_SESSION');
        
        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión periódicamente para prevenir ataques de fijación de sesión
        if (!isset($_SESSION['_session_created'])) {
            $_SESSION['_session_created'] = time();
        } elseif (time() - $_SESSION['_session_created'] > 1800) {
            // Regenerar ID cada 30 minutos
            session_regenerate_id(true);
            $_SESSION['_session_created'] = time();
        }
        
        // Inicializar arrays para datos de sesión si no existen
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        
        self::$sessionStarted = true;
    }
    
    /**
     * Establece un valor en la sesión
     *
     * @param string $key Clave del valor
     * @param mixed $value Valor a almacenar
     * @return void
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtiene un valor de la sesión
     *
     * @param string $key Clave del valor
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed Valor almacenado o valor por defecto
     */
    public static function get($key, $default = null) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Verifica si existe una clave en la sesión
     *
     * @param string $key Clave a verificar
     * @return boolean True si la clave existe, false en caso contrario
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Elimina un valor de la sesión
     *
     * @param string $key Clave a eliminar
     * @return void
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Elimina todos los datos de la sesión
     *
     * @return void
     */
    public static function clear() {
        self::start();
        
        // Preservar mensajes flash
        $flash = $_SESSION['_flash'] ?? [];
        
        // Limpiar la sesión
        $_SESSION = [];
        
        // Restaurar mensajes flash
        $_SESSION['_flash'] = $flash;
    }
    
    /**
     * Destruye completamente la sesión
     *
     * @return void
     */
    public static function destroy() {
        self::start();
        
        // Limpiar array de sesión
        $_SESSION = [];
        
        // Eliminar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        self::$sessionStarted = false;
    }
    
    /**
     * Establece un mensaje flash que estará disponible solo en la siguiente petición
     *
     * @param string $key Clave del mensaje
     * @param mixed $value Valor del mensaje
     * @return void
     */
    public static function flash($key, $value) {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Obtiene un mensaje flash y lo elimina
     *
     * @param string $key Clave del mensaje
     * @param mixed $default Valor por defecto si el mensaje no existe
     * @return mixed Valor del mensaje o valor por defecto
     */
    public static function getFlash($key, $default = null) {
        self::start();
        
        $value = $default;
        
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
        }
        
        return $value;
    }
    
    /**
     * Verifica si existe un mensaje flash
     *
     * @param string $key Clave del mensaje
     * @return boolean True si el mensaje existe, false en caso contrario
     */
    public static function hasFlash($key) {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Obtiene todos los mensajes flash y los elimina
     *
     * @return array Mensajes flash
     */
    public static function getAllFlash() {
        self::start();
        
        $flash = $_SESSION['_flash'];
        $_SESSION['_flash'] = [];
        
        return $flash;
    }
    
    /**
     * Guarda datos del usuario autenticado en la sesión
     *
     * @param array $userData Datos del usuario
     * @return void
     */
    public static function setUser($userData) {
        self::set('user', $userData);
        self::set('user_authenticated', true);
        self::set('last_activity', time());
    }
    
    /**
     * Obtiene los datos del usuario autenticado
     *
     * @return array|null Datos del usuario o null si no hay usuario autenticado
     */
    public static function getUser() {
        return self::get('user');
    }
    
    /**
     * Verifica si hay un usuario autenticado
     *
     * @return boolean True si hay un usuario autenticado, false en caso contrario
     */
    public static function isUserAuthenticated() {
        if (!self::has('user_authenticated')) {
            return false;
        }
        
        // Verificar tiempo de inactividad
        $lastActivity = self::get('last_activity', 0);
        $timeout = self::$sessionLifetime;
        
        if (time() - $lastActivity > $timeout) {
            // Sesión expirada por inactividad
            self::remove('user');
            self::remove('user_authenticated');
            return false;
        }
        
        // Actualizar tiempo de última actividad
        self::set('last_activity', time());
        
        return self::get('user_authenticated', false);
    }
    
    /**
     * Cierra la sesión del usuario
     *
     * @return void
     */
    public static function logout() {
        self::remove('user');
        self::remove('user_authenticated');
        self::remove('last_activity');
    }
    
    /**
     * Configura el tiempo de vida de la sesión
     *
     * @param integer $seconds Tiempo en segundos
     * @return void
     */
    public static function setSessionLifetime($seconds) {
        self::$sessionLifetime = (int)$seconds;
    }
    
    /**
     * Regenera el ID de la sesión
     *
     * @param boolean $deleteOldSession Si debe eliminar la sesión antigua
     * @return boolean Resultado de la operación
     */
    public static function regenerateId($deleteOldSession = true) {
        self::start();
        return session_regenerate_id($deleteOldSession);
    }
}