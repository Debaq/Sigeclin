<?php
/**
 * Sistema de Vistas - SIGECLIN
 * 
 * Esta clase maneja la renderización de vistas en el sistema SIGECLIN.
 * Permite cargar plantillas, pasar datos a las vistas y componerlas con layouts.
 * 
 * @package SIGECLIN
 * @subpackage Core
 */

class View {
    /**
     * Ruta base de las vistas
     *
     * @var string
     */
    private static $viewsPath = __DIR__ . '/../../app/views/';
    
    /**
     * Datos que se pasarán a la vista
     *
     * @var array
     */
    private static $data = [];
    
    /**
     * Renderiza una vista y devuelve el contenido como string
     *
     * @param string $view Ruta de la vista relativa a la carpeta de vistas
     * @param array $data Datos para pasar a la vista
     * @return string Contenido renderizado
     */
    public static function render($view, $data = []) {
        // Combinar con datos existentes
        $data = array_merge(self::$data, $data);
        
        // Extraer variables para que estén disponibles en la vista
        extract($data);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Construir ruta completa del archivo
        $viewFile = self::$viewsPath . $view . '.php';
        
        // Verificar si existe la vista
        if (!file_exists($viewFile)) {
            throw new Exception("Vista no encontrada: $view");
        }
        
        // Incluir la vista
        include $viewFile;
        
        // Obtener contenido del buffer y limpiarlo
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Renderiza una vista con un layout y lo envía directamente al navegador
     *
     * @param string $view Ruta de la vista relativa a la carpeta de vistas
     * @param array $data Datos para pasar a la vista
     * @param string $layout Layout a utilizar (ubicado en /views/templates/)
     * @return void
     */
    public static function renderWithLayout($view, $data = [], $layout = 'app') {
        // Renderizar el contenido de la vista
        $content = self::render($view, $data);
        
        // Pasar el contenido al layout
        $layoutData = array_merge($data, ['content' => $content]);
        
        // Renderizar el layout
        echo self::render('templates/' . $layout, $layoutData);
    }
    
    /**
     * Renderiza un bloque parcial de vista y devuelve el contenido
     *
     * @param string $partial Ruta del partial relativa a /views/partials/
     * @param array $data Datos para pasar al partial
     * @return string Contenido renderizado
     */
    public static function partial($partial, $data = []) {
        return self::render('partials/' . $partial, $data);
    }
    
    /**
     * Establece datos globales para todas las vistas
     *
     * @param array $data Datos globales
     * @return void
     */
    public static function setGlobalData($data) {
        self::$data = array_merge(self::$data, $data);
    }
    
    /**
     * Establece un dato global individual
     *
     * @param string $key Clave del dato
     * @param mixed $value Valor del dato
     * @return void
     */
    public static function setGlobal($key, $value) {
        self::$data[$key] = $value;
    }
    
    /**
     * Renderiza un JSON y establece las cabeceras adecuadas
     *
     * @param mixed $data Datos a convertir a JSON
     * @param int $status Código de estado HTTP
     * @return void
     */
    public static function renderJson($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Cambia la ruta base de las vistas
     *
     * @param string $path Nueva ruta base
     * @return void
     */
    public static function setViewsPath($path) {
        self::$viewsPath = rtrim($path, '/') . '/';
    }
    
    /**
     * Escapa HTML para prevenir XSS
     *
     * @param string $string Cadena a escapar
     * @return string Cadena escapada
     */
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Método abreviado para escape() en las vistas
     *
     * @param string $string Cadena a escapar
     * @return string Cadena escapada
     */
    public static function e($string) {
        return self::escape($string);
    }
    
    /**
     * Genera una URL completa para un asset
     *
     * @param string $path Ruta relativa del asset
     * @return string URL completa
     */
    public static function asset($path) {
        // Obtener la URL base de la configuración
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        $baseUrl .= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        
        return $baseUrl . '/assets/' . ltrim($path, '/');
    }
}