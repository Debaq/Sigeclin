<?php
/**
 * Configuración de la base de datos
 * 
 * Este archivo contiene la configuración de conexión a la base de datos SQLite
 * y define la clase Database siguiendo el patrón Singleton para gestionar
 * las conexiones a la base de datos.
 */

class Database {
    private static $instance = null;
    private $connection;
    private $error;
    private $statement;
    private $dbPath;
    private $inTransaction = false;

    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        try {
            // Ruta al archivo de base de datos SQLite
            $this->dbPath = dirname(dirname(__DIR__)) . '/database/sigeclin.sqlite';
            
            // Crear el directorio si no existe
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // Establecer opciones de PDO
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Crear nueva conexión PDO
            $this->connection = new PDO('sqlite:' . $this->dbPath, null, null, $options);
            
            // Habilitar FOREIGN KEYS en SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON;');
            
            // Verificar si la base de datos está vacía y ejecutar migración inicial si es necesario
            $this->initializeDatabase();
            
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Error de conexión a la base de datos: ' . $this->error);
            throw new Exception('Error de conexión a la base de datos: ' . $this->error);
        }
    }
    
    /**
     * Obtener instancia única de la clase Database (Singleton)
     * 
     * @return Database Instancia de la clase Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar la base de datos si está vacía
     */
    private function initializeDatabase() {
        try {
            // Verificar si existen tablas
            $result = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'");
            
            // Si no existe la tabla usuarios, ejecutar migración inicial
            if ($result->fetchColumn() === false) {
                $initSqlPath = dirname(dirname(__DIR__)) . '/database/migrations/init.sql';
                
                if (file_exists($initSqlPath)) {
                    $sql = file_get_contents($initSqlPath);
                    $this->connection->exec($sql);
                } else {
                    error_log('Archivo de migración inicial no encontrado: ' . $initSqlPath);
                }
            }
        } catch (PDOException $e) {
            error_log('Error al inicializar la base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Preparar una consulta SQL
     * 
     * @param string $sql Consulta SQL a preparar
     * @return bool True si la preparación fue exitosa, False en caso contrario
     */
    public function prepare($sql) {
        $this->statement = $this->connection->prepare($sql);
        return $this->statement !== false;
    }
    
    /**
     * Vincular un valor a un parámetro en la consulta preparada
     * 
     * @param mixed $param Parámetro a vincular (nombre o posición)
     * @param mixed $value Valor a vincular
     * @param mixed $type Tipo de dato (PDO::PARAM_*)
     * @return bool True si la vinculación fue exitosa, False en caso contrario
     */
    public function bindValue($param, $value, $type = null) {
        if ($type === null) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        return $this->statement->bindValue($param, $value, $type);
    }
    
    /**
     * Ejecutar la consulta preparada
     * 
     * @param array $params Parámetros para la consulta (opcional)
     * @return bool True si la ejecución fue exitosa, False en caso contrario
     */
    public function execute($params = []) {
        if (!empty($params)) {
            return $this->statement->execute($params);
        }
        return $this->statement->execute();
    }
    
    /**
     * Ejecutar consulta y devolver todos los resultados
     * 
     * @param string $sql Consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta (opcional)
     * @return array Resultados de la consulta
     */
    public function query($sql, $params = []) {
        $this->prepare($sql);
        
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $this->bindValue($param, $value);
            }
        }
        
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    /**
     * Obtener un solo registro
     * 
     * @return mixed Registro obtenido o false si no hay resultados
     */
    public function fetch() {
        return $this->statement->fetch();
    }
    
    /**
     * Obtener todos los registros
     * 
     * @return array Registros obtenidos
     */
    public function fetchAll() {
        return $this->statement->fetchAll();
    }
    
    /**
     * Obtener el valor de una sola columna
     * 
     * @param int $columnNumber Número de columna (opcional, por defecto 0)
     * @return mixed Valor de la columna
     */
    public function fetchColumn($columnNumber = 0) {
        return $this->statement->fetchColumn($columnNumber);
    }
    
    /**
     * Obtener cantidad de filas afectadas
     * 
     * @return int Número de filas afectadas
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    /**
     * Obtener el último ID insertado
     * 
     * @return string Último ID insertado
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Iniciar una transacción
     * 
     * @return bool True si se inició correctamente
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->connection->beginTransaction();
            return $this->inTransaction;
        }
        return false;
    }
    
    /**
     * Confirmar una transacción
     * 
     * @return bool True si se confirmó correctamente
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->connection->commit();
        }
        return false;
    }
    
    /**
     * Revertir una transacción
     * 
     * @return bool True si se revirtió correctamente
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->connection->rollBack();
        }
        return false;
    }
    
    /**
     * Verificar si hay una transacción activa
     * 
     * @return bool True si hay una transacción activa
     */
    public function inTransaction() {
        return $this->inTransaction;
    }
    
    /**
     * Crear respaldo de la base de datos
     * 
     * @param string $backupName Nombre del archivo de respaldo (opcional)
     * @return bool True si el respaldo se creó correctamente
     */
    public function backup($backupName = null) {
        try {
            // Definir nombre del archivo de respaldo
            if ($backupName === null) {
                $backupName = 'backup_' . date('Y-m-d_H-i-s') . '.sqlite';
            }
            
            // Ruta completa al archivo de respaldo
            $backupPath = dirname(dirname(__DIR__)) . '/database/backups/' . $backupName;
            
            // Crear directorio de respaldos si no existe
            $backupDir = dirname($backupPath);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Hacer copia del archivo de base de datos
            if (copy($this->dbPath, $backupPath)) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Error al crear respaldo de la base de datos: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecutar consulta directa (usar con precaución)
     * 
     * @param string $sql Consulta SQL a ejecutar
     * @return bool True si la ejecución fue exitosa
     */
    public function exec($sql) {
        try {
            return $this->connection->exec($sql) !== false;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Error al ejecutar consulta directa: ' . $this->error);
            return false;
        }
    }
    
    /**
     * Obtener el error más reciente
     * 
     * @return string Mensaje de error
     */
    public function getError() {
        return $this->error;
    }
}