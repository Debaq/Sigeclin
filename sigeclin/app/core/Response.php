<?php
/**
 * Clase Response
 * 
 * Esta clase encapsula la respuesta HTTP que será enviada al cliente
 * y proporciona métodos para configurar cabeceras, contenido, códigos
 * de estado y otras características de la respuesta.
 */

class Response {
    private $statusCode = 200;
    private $headers = [];
    private $content = '';
    private $contentType = 'text/html; charset=UTF-8';
    private $cookies = [];
    private $sent = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Establecer cabeceras por defecto
        $this->headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $this->contentType
        ];
        
        // Establecer zona horaria para la respuesta
        date_default_timezone_set(Config::get('app.timezone', 'America/Santiago'));
    }
    
    /**
     * Establecer código de estado HTTP
     * 
     * @param int $code Código de estado HTTP
     * @return $this Instancia actual para encadenamiento
     */
    public function setStatusCode($code) {
        $this->statusCode = (int) $code;
        return $this;
    }
    
    /**
     * Obtener código de estado HTTP actual
     * 
     * @return int Código de estado HTTP
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    /**
     * Establecer cabecera HTTP
     * 
     * @param string $name Nombre de la cabecera
     * @param string $value Valor de la cabecera
     * @return $this Instancia actual para encadenamiento
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Establecer múltiples cabeceras HTTP a la vez
     * 
     * @param array $headers Arreglo asociativo de cabeceras [nombre => valor]
     * @return $this Instancia actual para encadenamiento
     */
    public function setHeaders($headers) {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }
    
    /**
     * Eliminar una cabecera HTTP
     * 
     * @param string $name Nombre de la cabecera a eliminar
     * @return $this Instancia actual para encadenamiento
     */
    public function removeHeader($name) {
        unset($this->headers[$name]);
        return $this;
    }
    
    /**
     * Obtener todas las cabeceras HTTP configuradas
     * 
     * @return array Cabeceras HTTP
     */
    public function getHeaders() {
        return $this->headers;
    }
    
    /**
     * Establecer el tipo de contenido de la respuesta
     * 
     * @param string $contentType Tipo MIME del contenido
     * @param string $charset Juego de caracteres (opcional)
     * @return $this Instancia actual para encadenamiento
     */
    public function setContentType($contentType, $charset = 'UTF-8') {
        $this->contentType = $contentType . ($charset ? '; charset=' . $charset : '');
        $this->headers['Content-Type'] = $this->contentType;
        return $this;
    }
    
    /**
     * Establecer el contenido de la respuesta
     * 
     * @param string $content Contenido de la respuesta
     * @return $this Instancia actual para encadenamiento
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Obtener el contenido de la respuesta
     * 
     * @return string Contenido de la respuesta
     */
    public function getContent() {
        return $this->content;
    }
    
    /**
     * Añadir una cookie a la respuesta
     * 
     * @param string $name Nombre de la cookie
     * @param string $value Valor de la cookie
     * @param int $expire Tiempo de expiración (0 = sesión)
     * @param string $path Ruta donde es válida la cookie
     * @param string $domain Dominio donde es válida la cookie
     * @param bool $secure Solo enviar en conexiones seguras
     * @param bool $httpOnly No accesible desde JavaScript
     * @return $this Instancia actual para encadenamiento
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true) {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];
        return $this;
    }
    
    /**
     * Eliminar una cookie
     * 
     * @param string $name Nombre de la cookie a eliminar
     * @param string $path Ruta donde es válida la cookie
     * @param string $domain Dominio donde es válida la cookie
     * @return $this Instancia actual para encadenamiento
     */
    public function removeCookie($name, $path = '/', $domain = '') {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }
    
    /**
     * Enviar una respuesta JSON
     * 
     * @param mixed $data Datos a convertir a JSON
     * @param int $statusCode Código de estado HTTP (opcional)
     * @return $this Instancia actual para encadenamiento
     */
    public function json($data, $statusCode = null) {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }
        
        $json = json_encode($data);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
        }
        
        $this->setContentType('application/json');
        $this->setContent($json);
        
        return $this;
    }
    
    /**
     * Redireccionar a otra URL
     * 
     * @param string $url URL de destino
     * @param int $statusCode Código de estado HTTP (default: 302)
     * @return $this Instancia actual para encadenamiento
     */
    public function redirect($url, $statusCode = 302) {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->setContent('');
        $this->send();
        exit;
    }
    
    /**
     * Enviar archivo como descarga
     * 
     * @param string $filePath Ruta al archivo
     * @param string $filename Nombre del archivo a mostrar al usuario
     * @param string $contentType Tipo MIME del archivo
     * @return $this Instancia actual para encadenamiento
     */
    public function download($filePath, $filename = null, $contentType = null) {
        if (!file_exists($filePath)) {
            throw new Exception('Archivo no encontrado: ' . $filePath);
        }
        
        $filename = $filename ?: basename($filePath);
        
        // Detectar tipo MIME si no se proporciona
        if ($contentType === null) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $contentType = $finfo->file($filePath);
        }
        
        // Limpiar buffers de salida previos
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Configurar cabeceras para descarga
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setContentType($contentType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Content-Length', filesize($filePath));
        $this->setHeader('Expires', '0');
        $this->setHeader('Pragma', 'public');
        
        // Enviar cabeceras
        $this->sendHeaders();
        
        // Enviar archivo
        readfile($filePath);
        
        $this->sent = true;
        exit;
    }
    
    /**
     * Crear una respuesta con código de estado y mensaje
     * 
     * @param int $statusCode Código de estado HTTP
     * @param string $message Mensaje opcional
     * @return $this Instancia actual para encadenamiento
     */
    public function withStatus($statusCode, $message = '') {
        $this->setStatusCode($statusCode);
        
        if (!empty($message)) {
            $this->setContent($message);
        }
        
        return $this;
    }
    
    /**
     * Enviar la respuesta al cliente
     */
    public function send() {
        if ($this->sent) {
            return;
        }
        
        // Enviar cabeceras de estado
        http_response_code($this->statusCode);
        
        // Enviar cabeceras personalizadas
        $this->sendHeaders();
        
        // Enviar cookies
        $this->sendCookies();
        
        // Enviar contenido
        echo $this->content;
        
        $this->sent = true;
    }
    
    /**
     * Verificar si la respuesta ya ha sido enviada
     * 
     * @return bool True si la respuesta ya fue enviada
     */
    public function isSent() {
        return $this->sent;
    }
    
    /**
     * Enviar las cabeceras HTTP configuradas
     */
    private function sendHeaders() {
        // Verificar si las cabeceras ya fueron enviadas
        if (headers_sent()) {
            return;
        }
        
        // Enviar cada cabecera
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
    }
    
    /**
     * Enviar las cookies configuradas
     */
    private function sendCookies() {
        // Verificar si las cabeceras ya fueron enviadas
        if (headers_sent()) {
            return;
        }
        
        // Enviar cada cookie
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }
    }
    
    /**
     * Crear respuesta vacía o con mensaje simple
     * 
     * @param string $message Mensaje opcional
     * @param int $statusCode Código de estado HTTP (default: 200)
     * @return $this Instancia actual para encadenamiento
     */
    public function text($message = '', $statusCode = 200) {
        $this->setStatusCode($statusCode);
        $this->setContentType('text/plain');
        $this->setContent($message);
        return $this;
    }
    
    /**
     * Crear respuesta exitosa (código 200)
     * 
     * @param mixed $data Datos de la respuesta
     * @param string $message Mensaje opcional
     * @return $this Instancia actual para encadenamiento
     */
    public function success($data = null, $message = 'Operación realizada con éxito') {
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->json($response, 200);
    }
    
    /**
     * Crear respuesta de error
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP (default: 400)
     * @param array $errors Detalles de errores específicos
     * @return $this Instancia actual para encadenamiento
     */
    public function error($message, $statusCode = 400, $errors = []) {
        $response = [
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Crear respuesta "No encontrado" (código 404)
     * 
     * @param string $message Mensaje de error
     * @return $this Instancia actual para encadenamiento
     */
    public function notFound($message = 'Recurso no encontrado') {
        return $this->error($message, 404);
    }
    
    /**
     * Crear respuesta "No autorizado" (código 401)
     * 
     * @param string $message Mensaje de error
     * @return $this Instancia actual para encadenamiento
     */
    public function unauthorized($message = 'No autorizado') {
        return $this->error($message, 401);
    }
    
    /**
     * Crear respuesta "Prohibido" (código 403)
     * 
     * @param string $message Mensaje de error
     * @return $this Instancia actual para encadenamiento
     */
    public function forbidden($message = 'Acceso prohibido') {
        return $this->error($message, 403);
    }
    
    /**
     * Crear respuesta "Error del servidor" (código 500)
     * 
     * @param string $message Mensaje de error
     * @return $this Instancia actual para encadenamiento
     */
    public function serverError($message = 'Error interno del servidor') {
        return $this->error($message, 500);
    }
    
    /**
     * Crear respuesta "Entidad no procesable" (código 422)
     * 
     * @param array $errors Errores de validación
     * @param string $message Mensaje de error
     * @return $this Instancia actual para encadenamiento
     */
    public function validationError($errors, $message = 'Error de validación') {
        return $this->error($message, 422, $errors);
    }
}