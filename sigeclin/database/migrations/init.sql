-- Inicialización de la base de datos SIGECLIN
-- Este script crea todas las tablas necesarias e índices para optimización

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    rut TEXT UNIQUE NOT NULL,
    correo TEXT UNIQUE NOT NULL,
    contrasena TEXT NOT NULL,
    telefono TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacion DATETIME,
    tipo TEXT CHECK (tipo IN ('administrador', 'director', 'coordinador', 'estudiante')) NOT NULL,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    token_recuperacion TEXT,
    vencimiento_token DATETIME,
    ultimo_acceso DATETIME
);

-- Tabla de Instituciones
CREATE TABLE instituciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    direccion TEXT,
    telefono TEXT,
    email_contacto TEXT,
    logo_url TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1))
);

-- Tabla de Ofertas Formadoras
CREATE TABLE ofertas_formadoras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    capacidad_formadora INTEGER NOT NULL,
    fecha_inicio_disponibilidad DATE NOT NULL,
    fecha_fin_disponibilidad DATE NOT NULL,
    anio INTEGER NOT NULL,
    semestre INTEGER NOT NULL CHECK (semestre IN (1, 2)),
    contacto_nombre TEXT,
    contacto_telefono TEXT,
    contacto_email TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacion DATETIME,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
);

-- Tabla de Servicios
CREATE TABLE servicios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oferta_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    ubicacion TEXT,
    capacidad INTEGER NOT NULL,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (oferta_id) REFERENCES ofertas_formadoras (id)
);

-- Tabla de Carreras
CREATE TABLE carreras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT UNIQUE NOT NULL,
    codigo TEXT UNIQUE NOT NULL,
    descripcion TEXT,
    color TEXT DEFAULT '#CCCCCC',
    activa INTEGER DEFAULT 1 CHECK (activa IN (0, 1))
);

-- Tabla de Tutores
CREATE TABLE tutores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oferta_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    especialidad TEXT,
    telefono TEXT,
    email TEXT,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (oferta_id) REFERENCES ofertas_formadoras (id)
);

-- Tabla de Convenios
CREATE TABLE convenios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    ruta_archivo TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_termino DATE NOT NULL,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
);

-- Tabla de Hitos Críticos
CREATE TABLE hitos_criticos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oferta_id INTEGER NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_limite DATE NOT NULL,
    completado INTEGER DEFAULT 0 CHECK (completado IN (0, 1)),
    fecha_completado DATE,
    responsable TEXT,
    prioridad TEXT CHECK (prioridad IN ('alta', 'media', 'baja')) DEFAULT 'media',
    FOREIGN KEY (oferta_id) REFERENCES ofertas_formadoras (id)
);

-- Tabla de Insumos
CREATE TABLE insumos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oferta_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    ultima_entrega DATE,
    proxima_entrega DATE NOT NULL,
    responsable TEXT,
    estado TEXT CHECK (estado IN ('pendiente', 'entregado', 'atrasado')) DEFAULT 'pendiente',
    FOREIGN KEY (oferta_id) REFERENCES ofertas_formadoras (id)
);

-- Tabla de Asignaturas
CREATE TABLE asignaturas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    carrera_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    codigo TEXT UNIQUE NOT NULL,
    descripcion TEXT,
    horas_practica_semanal INTEGER NOT NULL,
    ruta_programa TEXT,
    ruta_pautas_evaluacion TEXT,
    activa INTEGER DEFAULT 1 CHECK (activa IN (0, 1)),
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
);

-- Tabla de Solicitudes
CREATE TABLE solicitudes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coordinador_id INTEGER NOT NULL,
    oferta_id INTEGER NOT NULL,
    asignatura_id INTEGER NOT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado TEXT CHECK (estado IN ('pendiente', 'aprobada', 'rechazada', 'finalizada')) DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_aprobacion DATETIME,
    fecha_rechazo DATETIME,
    activa INTEGER DEFAULT 1 CHECK (activa IN (0, 1)),
    FOREIGN KEY (coordinador_id) REFERENCES usuarios (id),
    FOREIGN KEY (oferta_id) REFERENCES ofertas_formadoras (id),
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas (id)
);

-- Tabla de Perfiles de Estudiantes
CREATE TABLE estudiantes_perfiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER UNIQUE NOT NULL,
    carrera_id INTEGER NOT NULL,
    nivel TEXT,
    numero_contacto_alternativo TEXT,
    antecedentes_medicos TEXT,
    ruta_foto TEXT,
    datos_completos INTEGER DEFAULT 0 CHECK (datos_completos IN (0, 1)),
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
);

-- Tabla de Documentos de Estudiantes
CREATE TABLE documentos_estudiantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    tipo TEXT CHECK (tipo IN ('vacuna', 'IAAS', 'RCP', 'otro')) NOT NULL,
    nombre TEXT NOT NULL,
    ruta_archivo TEXT NOT NULL,
    fecha_carga DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE,
    verificado INTEGER DEFAULT 0 CHECK (verificado IN (0, 1)),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes_perfiles (id)
);

-- Tabla de Rotaciones
CREATE TABLE rotaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    solicitud_id INTEGER NOT NULL,
    estudiante_id INTEGER NOT NULL,
    servicio_id INTEGER NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado TEXT CHECK (estado IN ('pendiente', 'aprobada', 'en_curso', 'finalizada', 'modificacion_solicitada')) DEFAULT 'pendiente',
    observaciones TEXT,
    bloqueada INTEGER DEFAULT 0 CHECK (bloqueada IN (0, 1)),
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes (id),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes_perfiles (id),
    FOREIGN KEY (servicio_id) REFERENCES servicios (id)
);

-- Tabla de Tareas Kanban
CREATE TABLE tareas_kanban (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    descripcion TEXT,
    asignado_a INTEGER,
    estado TEXT CHECK (estado IN ('pendiente', 'en_progreso', 'completada', 'bloqueada')) DEFAULT 'pendiente',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_limite DATE,
    prioridad TEXT CHECK (prioridad IN ('alta', 'media', 'baja')) DEFAULT 'media',
    color_etiqueta TEXT DEFAULT '#CCCCCC',
    FOREIGN KEY (asignado_a) REFERENCES usuarios (id)
);

-- Tabla de Notificaciones
CREATE TABLE notificaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    leida INTEGER DEFAULT 0 CHECK (leida IN (0, 1)),
    ruta_redireccion TEXT,
    tipo TEXT CHECK (tipo IN ('alerta', 'informacion', 'exito', 'error')) DEFAULT 'informacion',
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
);

-- Tabla de Reportes
CREATE TABLE reportes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    generado_por INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    tipo TEXT CHECK (tipo IN ('rotacion', 'estudiante', 'centro', 'programa', 'carrera')) NOT NULL,
    ruta_archivo TEXT NOT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generado_por) REFERENCES usuarios (id)
);

-- Tabla de Relación Coordinadores-Carreras
CREATE TABLE coordinadores_carreras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coordinador_id INTEGER NOT NULL,
    carrera_id INTEGER NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo INTEGER DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (coordinador_id) REFERENCES usuarios (id),
    FOREIGN KEY (carrera_id) REFERENCES carreras (id),
    UNIQUE(coordinador_id, carrera_id)
);

-- Tabla de Logs del Sistema
CREATE TABLE logs_sistema (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    accion TEXT NOT NULL,
    tabla_afectada TEXT,
    registro_afectado INTEGER,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    direccion_ip TEXT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
);

-- Índices para Optimización
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo);
CREATE INDEX idx_usuarios_rut ON usuarios(rut);
CREATE INDEX idx_ofertas_institucion ON ofertas_formadoras(institucion_id);
CREATE INDEX idx_ofertas_anio_semestre ON ofertas_formadoras(anio, semestre);
CREATE INDEX idx_servicios_oferta ON servicios(oferta_id);
CREATE INDEX idx_tutores_oferta ON tutores(oferta_id);
CREATE INDEX idx_solicitudes_coordinador ON solicitudes(coordinador_id);
CREATE INDEX idx_solicitudes_oferta ON solicitudes(oferta_id);
CREATE INDEX idx_solicitudes_estado ON solicitudes(estado);
CREATE INDEX idx_rotaciones_solicitud ON rotaciones(solicitud_id);
CREATE INDEX idx_rotaciones_estudiante ON rotaciones(estudiante_id);
CREATE INDEX idx_rotaciones_servicio ON rotaciones(servicio_id);
CREATE INDEX idx_rotaciones_fechas ON rotaciones(fecha_inicio, fecha_fin);
CREATE INDEX idx_documentos_estudiante ON documentos_estudiantes(estudiante_id);
CREATE INDEX idx_documentos_tipo ON documentos_estudiantes(tipo);
CREATE INDEX idx_notificaciones_usuario ON notificaciones(usuario_id);
CREATE INDEX idx_notificaciones_leida ON notificaciones(leida);

-- Insertar un usuario administrador inicial
INSERT INTO usuarios (
    nombre, 
    rut, 
    correo, 
    contrasena, 
    tipo, 
    activo
) VALUES (
    'Administrador', 
    '11111111-1', 
    'admin@sigeclin.cl', 
    '$2y$10$TMk7IWP7whc2vFdQ2S0Zr.sG6nEpTWF.nsb8ZL/YeZKTmPHnXnqpK', -- Contraseña: admin123
    'administrador', 
    1
);

-- Insertar carreras iniciales
INSERT INTO carreras (nombre, codigo, descripcion, color, activa) VALUES
('Medicina', 'MED', 'Carrera de Medicina', '#FF5733', 1),
('Enfermería', 'ENF', 'Carrera de Enfermería', '#33A8FF', 1),
('Obstetricia', 'OBS', 'Carrera de Obstetricia y Puericultura', '#33FFA8', 1),
('Kinesiología', 'KIN', 'Carrera de Kinesiología', '#A833FF', 1),
('Tecnología Médica', 'TEM', 'Carrera de Tecnología Médica', '#FFD133', 1);

-- Insertar instituciones iniciales
INSERT INTO instituciones (nombre, direccion, telefono, email_contacto, activo) VALUES
('Hospital Base de Valdivia', 'Av. Simpson 850, Valdivia', '+56632263000', 'contacto@hospitalvaldivia.cl', 1),
('Hospital Base de Osorno', 'Av. René Soriano 1135, Osorno', '+56642336400', 'contacto@hospitalosorno.cl', 1),
('Hospital Base de Puerto Montt', 'Los Aromos 65, Puerto Montt', '+56652362000', 'contacto@hospitalpm.cl', 1),
('Cesfam Externo', 'Calle Principal 123, Valdivia', '+56632123456', 'contacto@cesfam.cl', 1),
('Clínica Alemana Valdivia', 'Beauchef 765, Valdivia', '+56632246100', 'contacto@clinicaalemana.cl', 1);