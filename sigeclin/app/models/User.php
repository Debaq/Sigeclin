<?php
/**
 * Modelo de Usuario
 * 
 * Este modelo maneja todas las operaciones CRUD relacionadas con usuarios
 * en la base de datos y proporciona métodos específicos para autenticación.
 */

class User {
    private $db;
    private $table = 'usuarios';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todos los usuarios
     * 
     * @param array $filters Filtros opcionales
     * @param int $page Número de página
     * @param int $perPage Elementos por página
     * @return array Datos de usuarios y total
     */
    public function getAll($filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];
        
        // Construir cláusula WHERE basada en filtros
        if (!empty($filters)) {
            $conditions = [];
            
            if (isset($filters['tipo'])) {
                $conditions[] = 'tipo = :tipo';
                $params[':tipo'] = $filters['tipo'];
            }
            
            if (isset($filters['activo'])) {
                $conditions[] = 'activo = :activo';
                $params[':activo'] = $filters['activo'];
            }
            
            if (isset($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $conditions[] = '(nombre LIKE :search OR correo LIKE :search OR rut LIKE :search)';
                $params[':search'] = $searchTerm;
            }
            
            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            }
        }
        
        // Consultar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM {$this->table} $whereClause";
        
        $this->db->prepare($countQuery);
        
        foreach ($params as $param => $value) {
            $this->db->bindValue($param, $value);
        }
        
        $this->db->execute();
        $result = $this->db->fetch();
        $total = $result['total'];
        
        // Consultar usuarios con paginación
        $query = "
            SELECT 
                id, nombre, rut, correo, telefono, 
                fecha_creacion, ultima_modificacion, tipo, activo, 
                ultimo_acceso
            FROM {$this->table}
            $whereClause
            ORDER BY nombre ASC
            LIMIT $perPage OFFSET $offset
        ";
        
        $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $this->db->bindValue($param, $value);
        }
        
        $this->db->execute();
        $users = $this->db->fetchAll();
        
        return [
            'data' => $users,
            'total' => $total
        ];
    }
    
    /**
     * Obtener usuario por ID
     * 
     * @param int $id ID del usuario
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getById($id) {
        $query = "
            SELECT 
                id, nombre, rut, correo, contrasena, telefono, 
                fecha_creacion, ultima_modificacion, tipo, activo, 
                token_recuperacion, vencimiento_token, ultimo_acceso
            FROM {$this->table}
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':id', $id);
        $this->db->execute();
        
        return $this->db->fetch();
    }
    
    /**
     * Obtener usuario por correo electrónico
     * 
     * @param string $email Correo electrónico
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getByEmail($email) {
        $query = "
            SELECT 
                id, nombre, rut, correo, contrasena, telefono, 
                fecha_creacion, ultima_modificacion, tipo, activo, 
                token_recuperacion, vencimiento_token, ultimo_acceso
            FROM {$this->table}
            WHERE correo = :correo
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':correo', $email);
        $this->db->execute();
        
        return $this->db->fetch();
    }
    
    /**
     * Obtener usuario por RUT
     * 
     * @param string $rut RUT del usuario
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getByRut($rut) {
        $query = "
            SELECT 
                id, nombre, rut, correo, contrasena, telefono, 
                fecha_creacion, ultima_modificacion, tipo, activo, 
                token_recuperacion, vencimiento_token, ultimo_acceso
            FROM {$this->table}
            WHERE rut = :rut
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':rut', $rut);
        $this->db->execute();
        
        return $this->db->fetch();
    }
    
    /**
     * Obtener usuario por token de recuperación
     * 
     * @param string $token Token de recuperación
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getUserByResetToken($token) {
        $query = "
            SELECT 
                id, nombre, rut, correo, contrasena, telefono, 
                fecha_creacion, ultima_modificacion, tipo, activo, 
                token_recuperacion, vencimiento_token, ultimo_acceso
            FROM {$this->table}
            WHERE token_recuperacion = :token
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':token', $token);
        $this->db->execute();
        
        return $this->db->fetch();
    }
    
    /**
     * Crear nuevo usuario
     * 
     * @param array $data Datos del usuario
     * @return int|bool ID del nuevo usuario o false si falla
     */
    public function create($data) {
        // Verificar datos requeridos
        if (!isset($data['nombre']) || !isset($data['rut']) || !isset($data['correo']) || 
            !isset($data['contrasena']) || !isset($data['tipo'])) {
            return false;
        }
        
        // Verificar si ya existe un usuario con el mismo correo o RUT
        $existingEmail = $this->getByEmail($data['correo']);
        $existingRut = $this->getByRut($data['rut']);
        
        if ($existingEmail || $existingRut) {
            return false;
        }
        
        // Hash de la contraseña
        $hashedPassword = password_hash(
            $data['contrasena'], 
            Config::get('security.password_algo'), 
            ['cost' => Config::get('security.password_cost')]
        );
        
        $query = "
            INSERT INTO {$this->table} (
                nombre, rut, correo, contrasena, telefono, 
                tipo, activo, fecha_creacion
            ) VALUES (
                :nombre, :rut, :correo, :contrasena, :telefono, 
                :tipo, :activo, CURRENT_TIMESTAMP
            )
        ";
        
        $params = [
            ':nombre' => $data['nombre'],
            ':rut' => $data['rut'],
            ':correo' => $data['correo'],
            ':contrasena' => $hashedPassword,
            ':telefono' => $data['telefono'] ?? null,
            ':tipo' => $data['tipo'],
            ':activo' => $data['activo'] ?? 1
        ];
        
        $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $this->db->bindValue($param, $value);
        }
        
        $success = $this->db->execute();
        
        if ($success) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar datos de usuario
     * 
     * @param int $id ID del usuario
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update($id, $data) {
        // Obtener usuario actual para comparar campos únicos
        $currentUser = $this->getById($id);
        
        if (!$currentUser) {
            return false;
        }
        
        // Verificar si se está actualizando el correo y si ya existe
        if (isset($data['correo']) && $data['correo'] !== $currentUser['correo']) {
            $existingEmail = $this->getByEmail($data['correo']);
            if ($existingEmail) {
                return false;
            }
        }
        
        // Verificar si se está actualizando el RUT y si ya existe
        if (isset($data['rut']) && $data['rut'] !== $currentUser['rut']) {
            $existingRut = $this->getByRut($data['rut']);
            if ($existingRut) {
                return false;
            }
        }
        
        // Construir query dinámica
        $setClause = [];
        $params = [];
        
        if (isset($data['nombre'])) {
            $setClause[] = 'nombre = :nombre';
            $params[':nombre'] = $data['nombre'];
        }
        
        if (isset($data['rut'])) {
            $setClause[] = 'rut = :rut';
            $params[':rut'] = $data['rut'];
        }
        
        if (isset($data['correo'])) {
            $setClause[] = 'correo = :correo';
            $params[':correo'] = $data['correo'];
        }
        
        if (isset($data['telefono'])) {
            $setClause[] = 'telefono = :telefono';
            $params[':telefono'] = $data['telefono'];
        }
        
        if (isset($data['tipo'])) {
            $setClause[] = 'tipo = :tipo';
            $params[':tipo'] = $data['tipo'];
        }
        
        if (isset($data['activo'])) {
            $setClause[] = 'activo = :activo';
            $params[':activo'] = $data['activo'];
        }
        
        // Si no hay nada que actualizar
        if (empty($setClause)) {
            return true;
        }
        
        // Añadir campo de última modificación
        $setClause[] = 'ultima_modificacion = CURRENT_TIMESTAMP';
        
        // Añadir ID al array de parámetros
        $params[':id'] = $id;
        
        $query = "
            UPDATE {$this->table}
            SET " . implode(', ', $setClause) . "
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $this->db->bindValue($param, $value);
        }
        
        return $this->db->execute();
    }
    
    /**
     * Actualizar contraseña de usuario
     * 
     * @param int $id ID del usuario
     * @param string $hashedPassword Contraseña ya hasheada
     * @return bool True si se actualizó correctamente
     */
    public function updatePassword($id, $hashedPassword) {
        $query = "
            UPDATE {$this->table}
            SET contrasena = :contrasena,
                token_recuperacion = NULL,
                vencimiento_token = NULL,
                ultima_modificacion = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':contrasena', $hashedPassword);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Guardar token de recuperación de contraseña
     * 
     * @param int $id ID del usuario
     * @param string $token Token de recuperación
     * @param string $expiry Fecha de expiración (formato Y-m-d H:i:s)
     * @return bool True si se guardó correctamente
     */
    public function saveResetToken($id, $token, $expiry) {
        $query = "
            UPDATE {$this->table}
            SET token_recuperacion = :token,
                vencimiento_token = :expiry
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':token', $token);
        $this->db->bindValue(':expiry', $expiry);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Actualizar la fecha de último acceso
     * 
     * @param int $id ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function updateLastAccess($id) {
        $query = "
            UPDATE {$this->table}
            SET ultimo_acceso = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Desactivar usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se desactivó correctamente
     */
    public function deactivate($id) {
        $query = "
            UPDATE {$this->table}
            SET activo = 0,
                ultima_modificacion = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Activar usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se activó correctamente
     */
    public function activate($id) {
        $query = "
            UPDATE {$this->table}
            SET activo = 1,
                ultima_modificacion = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $this->db->prepare($query);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Eliminar usuario (no recomendado, mejor desactivar)
     * 
     * @param int $id ID del usuario
     * @return bool True si se eliminó correctamente
     */
    public function delete($id) {
        // Verificar si el usuario existe
        $user = $this->getById($id);
        
        if (!$user) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        
        $this->db->prepare($query);
        $this->db->bindValue(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Verificar si un correo está disponible (no existe o pertenece al usuario especificado)
     * 
     * @param string $email Correo electrónico
     * @param int $userId ID del usuario (opcional, para edición)
     * @return bool True si está disponible
     */
    public function isEmailAvailable($email, $userId = null) {
        $query = "SELECT id FROM {$this->table} WHERE correo = :correo";
        
        $this->db->prepare($query);
        $this->db->bindValue(':correo', $email);
        $this->db->execute();
        
        $result = $this->db->fetch();
        
        // Si no existe, está disponible
        if (!$result) {
            return true;
        }
        
        // Si existe pero es del mismo usuario, está disponible
        if ($userId && $result['id'] == $userId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si un RUT está disponible (no existe o pertenece al usuario especificado)
     * 
     * @param string $rut RUT
     * @param int $userId ID del usuario (opcional, para edición)
     * @return bool True si está disponible
     */
    public function isRutAvailable($rut, $userId = null) {
        $query = "SELECT id FROM {$this->table} WHERE rut = :rut";
        
        $this->db->prepare($query);
        $this->db->bindValue(':rut', $rut);
        $this->db->execute();
        
        $result = $this->db->fetch();
        
        // Si no existe, está disponible
        if (!$result) {
            return true;
        }
        
        // Si existe pero es del mismo usuario, está disponible
        if ($userId && $result['id'] == $userId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener usuarios por tipo/rol
     * 
     * @param string $type Tipo de usuario
     * @param bool $activeOnly Solo usuarios activos
     * @return array Usuarios del tipo especificado
     */
    public function getByType($type, $activeOnly = true) {
        $query = "
            SELECT 
                id, nombre, rut, correo, telefono, 
                fecha_creacion, tipo, activo, ultimo_acceso
            FROM {$this->table}
            WHERE tipo = :tipo
        ";
        
        if ($activeOnly) {
            $query .= " AND activo = 1";
        }
        
        $query .= " ORDER BY nombre ASC";
        
        $this->db->prepare($query);
        $this->db->bindValue(':tipo', $type);
        $this->db->execute();
        
        return $this->db->fetchAll();
    }
    
    /**
     * Buscar usuarios
     * 
     * @param string $term Término de búsqueda
     * @param array $filters Filtros adicionales
     * @return array Usuarios que coinciden con la búsqueda
     */
    public function search($term, $filters = []) {
        $term = '%' . $term . '%';
        
        $whereClause = "(nombre LIKE :term OR correo LIKE :term OR rut LIKE :term)";
        $params = [':term' => $term];
        
        // Añadir filtros adicionales
        if (isset($filters['tipo'])) {
            $whereClause .= " AND tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        
        if (isset($filters['activo'])) {
            $whereClause .= " AND activo = :activo";
            $params[':activo'] = $filters['activo'];
        }
        
        $query = "
            SELECT 
                id, nombre, rut, correo, telefono, 
                fecha_creacion, tipo, activo, ultimo_acceso
            FROM {$this->table}
            WHERE $whereClause
            ORDER BY nombre ASC
            LIMIT 20
        ";
        
        $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $this->db->bindValue($param, $value);
        }
        
        $this->db->execute();
        
        return $this->db->fetchAll();
    }
    
    /**
     * Contar usuarios por tipo
     * 
     * @param bool $activeOnly Solo usuarios activos
     * @return array Conteo por tipo
     */
    public function countByType($activeOnly = true) {
        $whereClause = $activeOnly ? "WHERE activo = 1" : "";
        
        $query = "
            SELECT tipo, COUNT(*) as total
            FROM {$this->table}
            $whereClause
            GROUP BY tipo
        ";
        
        $this->db->prepare($query);
        $this->db->execute();
        
        $results = $this->db->fetchAll();
        $counts = [];
        
        foreach ($results as $row) {
            $counts[$row['tipo']] = $row['total'];
        }
        
        return $counts;
    }
}