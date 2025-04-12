<?php
/**
 * Router del sistema
 * 
 * Esta clase se encarga de registrar rutas y direccionar las peticiones
 * a los controladores y métodos correspondientes, soportando diferentes
 * tipos de peticiones HTTP y middleware.
 */

class Router {
    private $routes = [];
    private $request;
    private $response;
    private $apiPrefix;
    private $webPrefix;
    
    /**
     * Constructor
     * 
     * @param Request $request Instancia de la clase Request
     * @param Response $response Instancia de la clase Response
     */
    public function __construct($request, $response) {
        $this->request = $request;
        $this->response = $response;
        $this->apiPrefix = Config::get('app.api_url');
        $this->webPrefix = '';
    }
    
    /**
     * Registrar ruta GET
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function get($uri, $handler, $middleware = []) {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }
    
    /**
     * Registrar ruta POST
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function post($uri, $handler, $middleware = []) {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }
    
    /**
     * Registrar ruta PUT
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function put($uri, $handler, $middleware = []) {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }
    
    /**
     * Registrar ruta DELETE
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function delete($uri, $handler, $middleware = []) {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }
    
    /**
     * Registrar ruta PATCH
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function patch($uri, $handler, $middleware = []) {
        return $this->addRoute('PATCH', $uri, $handler, $middleware);
    }
    
    /**
     * Registrar ruta para cualquier método HTTP
     * 
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    public function any($uri, $handler, $middleware = []) {
        return $this->addRoute('ANY', $uri, $handler, $middleware);
    }
    
    /**
     * Agrupar rutas con un prefijo común
     * 
     * @param string $prefix Prefijo para las rutas
     * @param callable $callback Función con definiciones de rutas
     * @param array $middleware Middleware para el grupo
     * @return Router Instancia actual para encadenamiento
     */
    public function group($prefix, $callback, $middleware = []) {
        // Guardar prefijo actual para restaurarlo después
        $currentWebPrefix = $this->webPrefix;
        
        // Establecer nuevo prefijo
        $this->webPrefix = $currentWebPrefix . $prefix;
        
        // Ejecutar callback con rutas del grupo
        $callback($this);
        
        // Restaurar prefijo original
        $this->webPrefix = $currentWebPrefix;
        
        return $this;
    }
    
    /**
     * Agrupar rutas de API
     * 
     * @param callable $callback Función con definiciones de rutas
     * @param array $middleware Middleware para el grupo
     * @return Router Instancia actual para encadenamiento
     */
    public function api($callback, $middleware = ['Auth']) {
        // Guardar prefijo actual para restaurarlo después
        $currentWebPrefix = $this->webPrefix;
        
        // Establecer prefijo de API
        $this->webPrefix = $this->apiPrefix;
        
        // Ejecutar callback con rutas de API
        $callback($this);
        
        // Restaurar prefijo original
        $this->webPrefix = $currentWebPrefix;
        
        return $this;
    }
    
    /**
     * Registrar una ruta con método específico
     * 
     * @param string $method Método HTTP
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return Router Instancia actual para encadenamiento
     */
    private function addRoute($method, $uri, $handler, $middleware = []) {
        // Añadir prefijo actual a la URI
        $prefixedUri = $this->webPrefix . $uri;
        
        // Eliminar barra final si existe
        $prefixedUri = rtrim($prefixedUri, '/');
        
        // Asegurar que la ruta comience con /
        if (empty($prefixedUri) || $prefixedUri[0] !== '/') {
            $prefixedUri = '/' . $prefixedUri;
        }
        
        // Añadir ruta al registro
        $this->routes[] = [
            'method' => $method,
            'uri' => $prefixedUri,
            'handler' => $handler,
            'middleware' => $middleware
        ];
        
        return $this;
    }
    
    /**
     * Despachar la petición actual a la ruta correspondiente
     * 
     * @return mixed Resultado de la ejecución del controlador
     * @throws Exception Si no se encuentra la ruta
     */
    public function dispatch() {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();
        
        // Remover query string de la URI si existe
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Eliminar barra final si existe
        $uri = rtrim($uri, '/');
        
        // Asegurar que la ruta comience con /
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Buscar la ruta correspondiente
        foreach ($this->routes as $route) {
            // Verificar si el método coincide (o es ANY)
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }
            
            // Convertir URI de ruta a patrón de expresión regular
            $pattern = $this->uriToPattern($route['uri']);
            
            // Intentar hacer coincidir con la URI actual
            if (preg_match($pattern, $uri, $matches)) {
                // Extraer parámetros de la URI
                $params = $this->extractParams($matches);
                
                // Establecer parámetros en la instancia de Request
                $this->request->setParams($params);
                
                // Aplicar middleware
                if (!empty($route['middleware'])) {
                    $this->applyMiddleware($route['middleware']);
                }
                
                // Ejecutar controlador
                return $this->executeHandler($route['handler']);
            }
        }
        
        // Si llegamos aquí, no se encontró la ruta
        $this->handleNotFound();
    }
    
    /**
     * Convertir URI con parámetros a patrón de expresión regular
     * 
     * @param string $uri URI a convertir
     * @return string Patrón de expresión regular
     */
    private function uriToPattern($uri) {
        // Escapar caracteres especiales
        $pattern = preg_quote($uri, '/');
        
        // Convertir parámetros {param} a grupos de captura
        $pattern = preg_replace('/\\\{([a-zA-Z0-9_]+)\\\}/', '([^\/]+)', $pattern);
        
        // Finalizar patrón
        $pattern = '/^' . $pattern . '$/';
        
        return $pattern;
    }
    
    /**
     * Extraer parámetros de la URI
     * 
     * @param array $matches Coincidencias de la expresión regular
     * @return array Parámetros extraídos
     */
    private function extractParams($matches) {
        $params = [];
        
        // Extraer nombres de parámetros de la ruta
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $this->routes[array_key_last($this->routes)]['uri'], $paramNames);
        
        // Ignorar la coincidencia completa (índice 0)
        array_shift($matches);
        
        // Asociar valores con nombres de parámetros
        if (!empty($paramNames[1])) {
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }
    
    /**
     * Aplicar middleware a la petición
     * 
     * @param array $middlewareList Lista de middleware a aplicar
     * @throws Exception Si el middleware rechaza la petición
     */
    private function applyMiddleware($middlewareList) {
        foreach ($middlewareList as $middleware) {
            $middlewareClass = $middleware . 'Middleware';
            $middlewarePath = dirname(__DIR__) . '/middlewares/' . $middlewareClass . '.php';
            
            // Verificar si existe el archivo de middleware
            if (file_exists($middlewarePath)) {
                // Cargar clase si no está disponible
                if (!class_exists($middlewareClass)) {
                    require_once $middlewarePath;
                }
                
                // Instanciar middleware
                $middlewareInstance = new $middlewareClass();
                
                // Ejecutar middleware
                if (method_exists($middlewareInstance, 'handle')) {
                    $result = $middlewareInstance->handle($this->request, $this->response);
                    
                    // Si el middleware devuelve false, detener la ejecución
                    if ($result === false) {
                        throw new Exception('Acceso denegado por middleware: ' . $middleware);
                    }
                }
            } else {
                throw new Exception('Middleware no encontrado: ' . $middleware);
            }
        }
    }
    
    /**
     * Ejecutar controlador o función anónima
     * 
     * @param string|callable $handler Controlador@método o función anónima
     * @return mixed Resultado de la ejecución
     * @throws Exception Si no se encuentra el controlador o método
     */
    private function executeHandler($handler) {
        // Si es una función anónima, ejecutarla directamente
        if (is_callable($handler)) {
            $result = $handler($this->request, $this->response);
            $this->handleResponse($result);
            return $result;
        }
        
        // Si es una cadena, debe ser Controlador@método
        if (is_string($handler)) {
            list($controllerName, $methodName) = explode('@', $handler);
            
            // Añadir "Controller" si no está presente
            if (strpos($controllerName, 'Controller') === false) {
                $controllerName .= 'Controller';
            }
            
            $controllerPath = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';
            
            // Verificar si existe el archivo del controlador
            if (!file_exists($controllerPath)) {
                throw new Exception('Controlador no encontrado: ' . $controllerName);
            }
            
            // Cargar clase si no está disponible
            if (!class_exists($controllerName)) {
                require_once $controllerPath;
            }
            
            // Instanciar controlador
            $controller = new $controllerName();
            
            // Verificar si existe el método
            if (!method_exists($controller, $methodName)) {
                throw new Exception('Método no encontrado: ' . $controllerName . '::' . $methodName);
            }
            
            // Ejecutar método del controlador
            $result = $controller->$methodName($this->request, $this->response);
            $this->handleResponse($result);
            return $result;
        }
        
        throw new Exception('Tipo de controlador no válido');
    }
    
    /**
     * Manejar respuestas de controladores
     * 
     * @param mixed $result Resultado de la ejecución del controlador
     */
    private function handleResponse($result) {
        // Si ya se ha enviado la respuesta, no hacer nada
        if ($this->response->isSent()) {
            return;
        }
        
        // Si es un array o un objeto, convertirlo a JSON
        if (is_array($result) || is_object($result)) {
            $this->response->setContentType('application/json');
            $this->response->setContent(json_encode($result));
            $this->response->send();
            return;
        }
        
        // Si es una cadena, enviarla como contenido
        if (is_string($result)) {
            $this->response->setContent($result);
            $this->response->send();
            return;
        }
        
        // Si no hay contenido, enviar respuesta vacía
        $this->response->send();
    }
    
    /**
     * Manejar rutas no encontradas
     * 
     * @throws Exception Siempre lanza una excepción de ruta no encontrada
     */
    private function handleNotFound() {
        // Determinar el tipo de respuesta según el tipo de petición
        if ($this->request->isAjax() || $this->request->isApi()) {
            // Para peticiones AJAX o API, responder con JSON
            $this->response->setStatusCode(404);
            $this->response->setContentType('application/json');
            $this->response->setContent(json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ruta no encontrada'
            ]));
            $this->response->send();
        } else {
            // Para peticiones normales, mostrar página de error
            $viewPath = dirname(__DIR__) . '/views/errors/404.php';
            
            $this->response->setStatusCode(404);
            
            if (file_exists($viewPath)) {
                ob_start();
                include $viewPath;
                $content = ob_get_clean();
                $this->response->setContent($content);
            } else {
                // Si no existe la vista de error, mostrar mensaje simple
                $this->response->setContent('<h1>Error 404</h1><p>Página no encontrada</p>');
            }
            
            $this->response->send();
        }
        
        exit;
    }
    
    /**
     * Obtener todas las rutas registradas
     * 
     * @return array Lista de rutas
     */
    public function getRoutes() {
        return $this->routes;
    }
}