<?php
/**
 * Clase Request
 * 
 * Esta clase encapsula la petición HTTP actual y proporciona métodos
 * para acceder a los datos de la petición de forma segura.
 */

class Request {
    private $method;
    private $uri;
    private $params = [];
    private $queryParams = [];
    private $bodyParams = [];
    private $headers = [];
    private $files = [];
    private $cookies = [];
    private $isAjax = false;
    private $isApi = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Manejar métodos PUT, DELETE, etc. desde formularios
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $this->method = strtoupper($_POST['_method']);
        }
        
        // Cargar parámetros de query string
        $this->queryParams = $_GET ?? [];
        
        // Cargar parámetros del body según método
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->parseBodyParams();
        }
        
        // Cargar headers
        $this->loadHeaders();
        
        // Cargar archivos
        $this->files = $_FILES ?? [];
        
        // Cargar cookies
        $this->cookies = $_COOKIE ?? [];
        
        // Determinar si es petición AJAX
        $this->isAjax = $this->hasHeader('X-Requested-With') && 
                        $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
        
        // Determinar si es petición a la API
        $apiPrefix = Config::get('app.api_url');
        $this->isApi = strpos($this->uri, $apiPrefix) === 0;
    }
    
    /**
     * Cargar encabezados HTTP
     */
    private function loadHeaders() {
        // Si está disponible getallheaders(), usarlo
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
            return;
        }
        
        // Alternativa para servidores que no tienen getallheaders()
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerKey = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$headerKey] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerKey = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
                $headers[$headerKey] = $value;
            }
        }
        
        $this->headers = $headers;
    }
    
    /**
     * Parsear parámetros del cuerpo de la petición
     */
    private function parseBodyParams() {
        // Para POST normal
        if ($this->method === 'POST' && !empty($_POST)) {
            $this->bodyParams = $_POST;
            return;
        }
        
        // Para JSON, XML, etc.
        $contentType = $this->getHeader('Content-Type');
        $rawInput = file_get_contents('php://input');
        
        if (!empty($rawInput)) {
            if (strpos($contentType, 'application/json') !== false) {
                // Parsear JSON
                $jsonData = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->bodyParams = $jsonData;
                }
            } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                // Parsear form-urlencoded
                parse_str($rawInput, $data);
                $this->bodyParams = $data;
            } elseif (strpos($contentType, 'multipart/form-data') !== false) {
                // Los datos ya están en $_POST
                $this->bodyParams = $_POST;
            } else {
                // Otros formatos, guardar input en bruto
                $this->bodyParams = ['raw' => $rawInput];
            }
        }
    }
    
    /**
     * Obtener método HTTP
     * 
     * @return string Método HTTP (GET, POST, etc.)
     */
    public function getMethod() {
        return $this->method;
    }
    
    /**
     * Obtener URI de la petición
     * 
     * @return string URI
     */
    public function getUri() {
        return $this->uri;
    }
    
    /**
     * Establecer parámetros de ruta
     * 
     * @param array $params Parámetros de ruta
     */
    public function setParams($params) {
        $this->params = $params;
    }
    
    /**
     * Obtener todos los parámetros de ruta
     * 
     * @return array Parámetros de ruta
     */
    public function getParams() {
        return $this->params;
    }
    
    /**
     * Obtener un parámetro de ruta específico
     * 
     * @param string $name Nombre del parámetro
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor del parámetro o valor por defecto
     */
    public function getParam($name, $default = null) {
        return $this->params[$name] ?? $default;
    }
    
    /**
     * Obtener todos los parámetros de query string
     * 
     * @return array Parámetros de query string
     */
    public function getQueryParams() {
        return $this->queryParams;
    }
    
    /**
     * Obtener un parámetro de query string específico
     * 
     * @param string $name Nombre del parámetro
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor del parámetro o valor por defecto
     */
    public function getQueryParam($name, $default = null) {
        return $this->queryParams[$name] ?? $default;
    }
    
    /**
     * Obtener todos los parámetros del body
     * 
     * @return array Parámetros del body
     */
    public function getBodyParams() {
        return $this->bodyParams;
    }
    
    /**
     * Obtener un parámetro del body específico
     * 
     * @param string $name Nombre del parámetro
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor del parámetro o valor por defecto
     */
    public function getBodyParam($name, $default = null) {
        return $this->bodyParams[$name] ?? $default;
    }
    
    /**
     * Obtener datos JSON del body
     * 
     * @return array Datos JSON o array vacío si no es JSON
     */
    public function getJsonData() {
        return $this->bodyParams;
    }
    
    /**
     * Obtener todos los headers
     * 
     * @return array Headers de la petición
     */
    public function getHeaders() {
        return $this->headers;
    }
    
    /**
     * Verificar si existe un header específico
     * 
     * @param string $name Nombre del header
     * @return bool True si existe, false en caso contrario
     */
    public function hasHeader($name) {
        $normalized = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        return isset($this->headers[$normalized]);
    }
    
    /**
     * Obtener un header específico
     * 
     * @param string $name Nombre del header
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor del header o valor por defecto
     */
    public function getHeader($name, $default = null) {
        $normalized = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        return $this->headers[$normalized] ?? $default;
    }
    
    /**
     * Obtener todos los archivos subidos
     * 
     * @return array Archivos subidos
     */
    public function getFiles() {
        return $this->files;
    }
    
    /**
     * Obtener un archivo subido específico
     * 
     * @param string $name Nombre del campo de archivo
     * @return array|null Información del archivo o null si no existe
     */
    public function getFile($name) {
        return $this->files[$name] ?? null;
    }
    
    /**
     * Obtener todas las cookies
     * 
     * @return array Cookies
     */
    public function getCookies() {
        return $this->cookies;
    }
    
    /**
     * Obtener una cookie específica
     * 
     * @param string $name Nombre de la cookie
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor de la cookie o valor por defecto
     */
    public function getCookie($name, $default = null) {
        return $this->cookies[$name] ?? $default;
    }
    
    /**
     * Verificar si es una petición AJAX
     * 
     * @return bool True si es AJAX, false en caso contrario
     */
    public function isAjax() {
        return $this->isAjax;
    }
    
    /**
     * Verificar si es una petición a la API
     * 
     * @return bool True si es API, false en caso contrario
     */
    public function isApi() {
        return $this->isApi;
    }
    
    /**
     * Obtener la dirección IP del cliente
     * 
     * @return string Dirección IP
     */
    public function getClientIp() {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim(reset($ips));
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Obtener el user agent del cliente
     * 
     * @return string User agent
     */
    public function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Obtener el valor de un input específico (query, body o parámetro de ruta)
     * 
     * @param string $name Nombre del input
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor del input o valor por defecto
     */
    public function input($name, $default = null) {
        // Buscar en parámetros de ruta
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        
        // Buscar en parámetros de body
        if (isset($this->bodyParams[$name])) {
            return $this->bodyParams[$name];
        }
        
        // Buscar en parámetros de query string
        if (isset($this->queryParams[$name])) {
            return $this->queryParams[$name];
        }
        
        return $default;
    }
    
    /**
     * Verificar si es una petición segura (HTTPS)
     * 
     * @return bool True si es HTTPS, false en caso contrario
     */
    public function isSecure() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || $_SERVER['SERVER_PORT'] == 443;
    }
    
    /**
     * Obtener el tipo de contenido que acepta el cliente
     * 
     * @return string Tipo de contenido aceptado
     */
    public function getAcceptType() {
        return $this->getHeader('Accept', '*/*');
    }
    
    /**
     * Verificar si el cliente acepta JSON
     * 
     * @return bool True si acepta JSON, false en caso contrario
     */
    public function acceptsJson() {
        $accept = $this->getAcceptType();
        return strpos($accept, 'application/json') !== false || 
               strpos($accept, '*/*') !== false;
    }
    
    /**
     * Verificar si el cliente acepta HTML
     * 
     * @return bool True si acepta HTML, false en caso contrario
     */
    public function acceptsHtml() {
        $accept = $this->getAcceptType();
        return strpos($accept, 'text/html') !== false || 
               strpos($accept, '*/*') !== false;
    }
}