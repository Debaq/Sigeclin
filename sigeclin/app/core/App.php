<?php
/**
 * Clase principal de la aplicación
 * 
 * Esta clase es el núcleo del sistema SIGECLIN y se encarga de inicializar
 * los componentes necesarios y procesar las peticiones entrantes siguiendo
 * el patrón MVC (Modelo-Vista-Controlador).
 */

class App {
    private $router;
    private $request;
    private $response;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Inicializar componentes principales
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        // Configurar manejador de errores personalizado
        $this->setupErrorHandling();
    }
    
    /**
     * Iniciar la aplicación y procesar la petición
     */
    public function run() {
        try {
            // Cargar rutas del sistema
            $this->loadRoutes();
            
            // Procesar la petición actual
            $this->router->dispatch();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Cargar archivo de rutas
     */
    private function loadRoutes() {
        $routesFile = dirname(__DIR__) . '/config/routes.php';
        
        if (file_exists($routesFile)) {
            require_once $routesFile;
        } else {
            throw new Exception('Archivo de rutas no encontrado');
        }
    }
    
    /**
     * Configurar manejador de errores
     */
    private function setupErrorHandling() {
        // Configurar manejador de errores según entorno
        if (Config::get('app.environment') === 'development') {
            // En desarrollo, mostrar todos los errores
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            // En producción, ocultar errores y registrarlos
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', Config::get('paths.logs') . '/php_errors.log');
        }
        
        // Configurar manejador de excepciones
        set_exception_handler([$this, 'handleException']);
        
        // Configurar manejador de errores
        set_error_handler([$this, 'handleError']);
    }
    
    /**
     * Manejador de excepciones
     * 
     * @param Exception $exception Excepción capturada
     */
    public function handleException($exception) {
        // Registrar excepción en logs
        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . 
                      get_class($exception) . ": " . 
                      $exception->getMessage() . " in " . 
                      $exception->getFile() . " on line " . 
                      $exception->getLine() . "\n" . 
                      $exception->getTraceAsString() . "\n\n";
                      
        error_log($logMessage, 3, Config::get('paths.logs') . '/exceptions.log');
        
        // Preparar respuesta según entorno
        if (Config::get('app.environment') === 'development') {
            // En desarrollo, mostrar detalles de la excepción
            $errorData = [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        } else {
            // En producción, ocultar detalles técnicos
            $errorData = [
                'status' => 'error',
                'message' => 'Ha ocurrido un error en el sistema. Por favor, inténtelo más tarde.'
            ];
        }
        
        // Determinar el tipo de respuesta según el tipo de petición
        if ($this->request->isAjax() || $this->request->isApi()) {
            // Para peticiones AJAX o API, responder con JSON
            $this->response->setStatusCode(500);
            $this->response->setContentType('application/json');
            $this->response->setContent(json_encode($errorData));
            $this->response->send();
        } else {
            // Para peticiones normales, mostrar página de error
            $viewPath = dirname(__DIR__) . '/views/errors/500.php';
            
            if (file_exists($viewPath)) {
                extract($errorData);
                include $viewPath;
            } else {
                // Si no existe la vista de error, mostrar mensaje simple
                echo '<h1>Error del servidor</h1>';
                
                if (Config::get('app.environment') === 'development') {
                    echo '<pre>' . $exception->getMessage() . '</pre>';
                    echo '<pre>' . $exception->getTraceAsString() . '</pre>';
                } else {
                    echo '<p>Ha ocurrido un error en el sistema. Por favor, inténtelo más tarde.</p>';
                }
            }
        }
        
        exit;
    }
    
    /**
     * Manejador de errores PHP
     * 
     * @param int $errno Número de error
     * @param string $errstr Mensaje de error
     * @param string $errfile Archivo donde ocurrió el error
     * @param int $errline Línea donde ocurrió el error
     * @return bool True para evitar el manejador de errores estándar de PHP
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        // Ignorar errores suprimidos con @
        if (error_reporting() === 0) {
            return true;
        }
        
        // Convertir errores en excepciones
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    /**
     * Registrar mensaje en el log
     * 
     * @param string $message Mensaje a registrar
     * @param string $level Nivel de log (info, warning, error)
     * @param array $context Contexto adicional
     */
    public static function log($message, $level = 'info', $context = []) {
        $logFile = Config::get('paths.logs') . "/{$level}.log";
        $timestamp = date('Y-m-d H:i:s');
        
        // Formatear mensaje con contexto
        if (!empty($context)) {
            $contextStr = json_encode($context);
            $logMessage = "[{$timestamp}] {$message} - Context: {$contextStr}\n";
        } else {
            $logMessage = "[{$timestamp}] {$message}\n";
        }
        
        // Escribir en archivo de log
        error_log($logMessage, 3, $logFile);
    }
    
    /**
     * Obtener instancia del router
     * 
     * @return Router Instancia del router
     */
    public function getRouter() {
        return $this->router;
    }
    
    /**
     * Obtener instancia de la petición
     * 
     * @return Request Instancia de la petición
     */
    public function getRequest() {
        return $this->request;
    }
    
    /**
     * Obtener instancia de la respuesta
     * 
     * @return Response Instancia de la respuesta
     */
    public function getResponse() {
        return $this->response;
    }
}