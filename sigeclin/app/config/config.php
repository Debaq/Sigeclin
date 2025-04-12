<?php
/**
 * Configuración General de la Aplicación
 * 
 * Este archivo contiene los parámetros de configuración global del sistema SIGECLIN.
 * Incluye constantes, rutas y otras configuraciones utilizadas en toda la aplicación.
 */

class Config {
    // Almacenamiento para configuraciones
    private static $config = [];
    
    /**
     * Inicializa la configuración del sistema
     */
    public static function init() {
        // Información de la aplicación
        self::$config['app'] = [
            'name'          => 'SIGECLIN',
            'full_name'     => 'Sistema Integrado de Gestión de Campos Clínicos',
            'version'       => '1.0.0',
            'environment'   => getenv('APP_ENV') ?: 'development', // production, development, testing
            'debug'         => getenv('APP_DEBUG') ?: true,
            'timezone'      => 'America/Santiago',
            'charset'       => 'UTF-8',
            'locale'        => 'es_CL',
            'base_url'      => self::determineBaseUrl(),
            'api_url'       => '/api/v1',
            'api_version'   => 'v1',
            'session_name'  => 'sigeclin_session',
            'session_lifetime' => 7200, // 2 horas en segundos
        ];
        
        // Seguridad
        self::$config['security'] = [
            'jwt_secret'     => getenv('JWT_SECRET') ?: 'sigeclin_secret_key_change_in_production',
            'jwt_expiration' => 86400, // 24 horas en segundos
            'password_algo'  => PASSWORD_BCRYPT,
            'password_cost'  => 10,
            'csrf_token_name' => 'sigeclin_csrf_token',
            'max_login_attempts' => 5,
            'lockout_time'   => 900, // 15 minutos en segundos
        ];
        
        // Rutas de directorios
        $rootPath = dirname(dirname(__DIR__));
        self::$config['paths'] = [
            'root'           => $rootPath,
            'app'            => $rootPath . '/app',
            'public'         => $rootPath . '/public',
            'uploads'        => $rootPath . '/public/uploads',
            'assets'         => $rootPath . '/public/assets',
            'storage'        => $rootPath . '/storage',
            'logs'           => $rootPath . '/storage/logs',
            'cache'          => $rootPath . '/storage/cache',
            'temp'           => $rootPath . '/storage/temp',
            'database'       => $rootPath . '/database',
            'views'          => $rootPath . '/app/views',
        ];
        
        // Configuración de uploads
        self::$config['uploads'] = [
            'max_size'       => 10 * 1024 * 1024, // 10MB en bytes
            'allowed_images' => ['jpg', 'jpeg', 'png', 'gif'],
            'allowed_docs'   => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'photos_dir'     => 'photos',
            'documents_dir'  => 'documents',
            'programs_dir'   => 'programs',
            'agreements_dir' => 'agreements',
        ];
        
        // Configuración de correo (si se implementa)
        self::$config['mail'] = [
            'enabled'        => false,
            'from_address'   => 'noreply@sigeclin.cl',
            'from_name'      => 'SIGECLIN',
            'smtp_host'      => getenv('MAIL_HOST') ?: '',
            'smtp_port'      => getenv('MAIL_PORT') ?: 587,
            'smtp_secure'    => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'smtp_auth'      => true,
            'smtp_user'      => getenv('MAIL_USERNAME') ?: '',
            'smtp_pass'      => getenv('MAIL_PASSWORD') ?: '',
        ];
        
        // Configuración visual/institucional
        self::$config['ui'] = [
            'colors' => [
                'red'        => '#C8102E', // Pantone 200
                'blue'       => '#D4E5F7', // Pantone 290
                'yellow'     => '#FFC72C', // Pantone 123
                'green'      => '#00843D', // Pantone 349
                'black'      => '#000000', // Black
                'gray1'      => '#666666', // 60% Black
                'gray2'      => '#999999', // 40% Black
                'gray3'      => '#B3B3B3', // 30% Black
                'pm_blue'    => '#0097C4', // Pantone 314C (Puerto Montt)
            ],
            'logo'           => '/assets/images/logo_uach.png',
            'favicon'        => '/assets/images/favicon.ico',
            'items_per_page' => 20,
        ];
        
        // Habilitar mensajes de depuración si estamos en modo desarrollo
        if (self::$config['app']['environment'] === 'development') {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }
        
        // Establecer zona horaria
        date_default_timezone_set(self::$config['app']['timezone']);
    }
    
    /**
     * Obtiene un valor de configuración
     * 
     * @param string $key Clave de configuración (formato: 'categoria.subclave')
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed Valor de configuración o valor por defecto
     */
    public static function get($key, $default = null) {
        // Inicializar configuración si aún no se ha hecho
        if (empty(self::$config)) {
            self::init();
        }
        
        // Permitir notación con puntos (ej: 'app.name')
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $category = $parts[0];
            $setting = $parts[1];
            
            if (isset(self::$config[$category][$setting])) {
                return self::$config[$category][$setting];
            }
        } elseif (isset(self::$config[$key])) {
            // Si solo se proporciona la categoría, devolver toda la categoría
            return self::$config[$key];
        }
        
        return $default;
    }
    
    /**
     * Actualiza un valor de configuración
     * 
     * @param string $key Clave de configuración (formato: 'categoria.subclave')
     * @param mixed $value Nuevo valor
     * @return bool True si se actualizó correctamente
     */
    public static function set($key, $value) {
        // Inicializar configuración si aún no se ha hecho
        if (empty(self::$config)) {
            self::init();
        }
        
        // Notación con puntos para actualizar valores específicos
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $category = $parts[0];
            $setting = $parts[1];
            
            if (isset(self::$config[$category])) {
                self::$config[$category][$setting] = $value;
                return true;
            }
        } else {
            // Actualizar toda una categoría
            self::$config[$key] = $value;
            return true;
        }
        
        return false;
    }
    
    /**
     * Determina la URL base de la aplicación
     * 
     * @return string URL base
     */
    private static function determineBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) 
            ? "https://" : "http://";
            
        $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Determinar la ruta base
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');
        
        return $protocol . $domainName . $basePath;
    }
    
    /**
     * Carga variables de entorno desde un archivo .env
     * Método simple para entornos de desarrollo
     */
    public static function loadEnv() {
        $envFile = dirname(dirname(__DIR__)) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentarios
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Eliminar comillas si existen
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                putenv("{$name}={$value}");
            }
        }
    }
}

// Cargar variables de entorno al incluir este archivo
Config::loadEnv();

// Inicializar configuración
Config::init();