# Documentación Técnica - SIGECLIN

## Especificación Técnica del Sistema

Esta documentación técnica detallada proporciona las directrices para el desarrollo del Sistema Integrado de Gestión de Campos Clínicos (SIGECLIN) utilizando Vite, PHP y SQLite.

## 1. Arquitectura del Sistema

### 1.1 Diagrama de Arquitectura

```
+-------------------------------------------+
| CLIENTE                                   |
|  +-------------+      +----------------+  |
|  | Navegador   |<---->| Assets         |  |
|  | Web         |      | (JS/CSS/Media) |  |
|  +-------------+      +----------------+  |
+-------------------|------------------------+
                    |
                    | HTTP/HTTPS
                    |
+-------------------|------------------------+
| SERVIDOR                                  |
|  +-------------+      +----------------+  |
|  | Frontend    |<---->| Backend PHP    |  |
|  | (Vite)      |      | (API REST)     |  |
|  +-------------+      +----------------+  |
|                              |            |
|                              v            |
|                       +----------------+  |
|                       | Base de Datos  |  |
|                       | (SQLite)       |  |
|                       +----------------+  |
|                              |            |
|                              v            |
|                       +----------------+  |
|                       | Almacenamiento |  |
|                       | de Archivos    |  |
|                       +----------------+  |
+-------------------------------------------+
```

### 1.2 Patrón de Arquitectura

El sistema implementará una arquitectura MVC (Modelo-Vista-Controlador) con una clara separación entre:

- **Frontend**: Aplicación SPA (Single Page Application) construida con Vite
- **Backend**: API REST desarrollada en PHP
- **Persistencia**: Base de datos SQLite

## 2. Tecnologías y Stack Técnico

### 2.1 Frontend

- **Framework de Desarrollo**: Vite 4.x
- **Lenguajes**: JavaScript ES6+, HTML5, CSS3
- **Framework CSS**: Bootstrap 5 (customizado con colores institucionales)
- **Bibliotecas JS**:
  - Chart.js 3.x (para gráficos y visualizaciones)
  - Fullcalendar 6.x (para calendario de rotaciones)
  - Frappe Gantt (para visualización de cartas Gantt)
  - SortableJS (para funcionalidad drag-and-drop en Kanban)
  - FontAwesome 6.x (para iconografía)
  - PapaParse (para manipulación CSV)
  - jsPDF (para exportación a PDF)
  - ExcelJS (para exportación a Excel)
  - Axios (para comunicación con API REST)
  - Filepond (para carga de archivos con preview)

### 2.2 Backend

- **Lenguaje**: PHP 8.1+
- **Arquitectura**: MVC personalizado
- **Manipulación de datos**: PDO para conexión con SQLite
- **Seguridad**:
  - Implementación JWT para autenticación
  - Password_hash nativo de PHP para encriptación
  - CSP (Content Security Policy)
  - CSRF protection
- **Procesamiento de imágenes**: GD Library / Imagick
- **Gestión de documentos**: mPDF para generación de PDFs
- **Bibliotecas**:
  - PHPMailer (para envío de notificaciones por correo)
  - PHPSpreadsheet (para manipulación de Excel)
  - Intervention/Image (para procesamiento de imágenes)

### 2.3 Base de Datos

- **Motor**: SQLite 3
- **Abstracción**: PHP PDO
- **Respaldo**: Automatizado mediante scripts PHP
- **Herramientas**: SQLite Browser para gestión manual (desarrollo)

### 2.4 Entorno de Desarrollo

- **Servidor local**: XAMPP / WAMP / MAMP
- **Control de versiones**: Git
- **Repositorio**: GitHub/GitLab
- **Editor recomendado**: VS Code con extensiones:
  - PHP Intelephense
  - Vite
  - SQLite
  - ESLint
  - Prettier

## 3. Esquema de Base de Datos

### 3.1 Diagrama Físico de la Base de Datos

```sql
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
```

### 3.2 Definición de Índices Clave

Se han definido índices para optimizar las consultas más frecuentes:
- Búsquedas por RUT de usuario
- Filtrado por tipo de usuario
- Consultas de ofertas por institución
- Filtrado de solicitudes por estado
- Búsqueda de rotaciones por fechas
- Filtrado de documentos por tipo y estudiante

## 4. Estructura del Proyecto

```
sigeclin/
├── public/              # Punto de entrada público
│   ├── index.php        # Archivo de entrada principal
│   ├── assets/          # Recursos estáticos compilados
│   │   ├── css/         # Estilos compilados
│   │   ├── js/          # JavaScript compilado
│   │   ├── fonts/       # Fuentes
│   │   └── images/      # Imágenes estáticas
│   ├── uploads/         # Directorio para archivos subidos (permisos especiales)
│   │   ├── photos/      # Fotos de perfil
│   │   ├── documents/   # Documentos y certificados
│   │   ├── programs/    # Programas de asignaturas
│   │   └── agreements/  # Convenios institucionales
│   └── .htaccess        # Configuración Apache
├── app/                 # Código de la aplicación
│   ├── config/          # Configuración
│   │   ├── config.php   # Configuración general
│   │   ├── database.php # Configuración de base de datos
│   │   └── routes.php   # Definición de rutas
│   ├── controllers/     # Controladores
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── InstitutionController.php
│   │   ├── OfferController.php
│   │   ├── RequestController.php
│   │   ├── StudentController.php
│   │   ├── RotationController.php
│   │   ├── KanbanController.php
│   │   ├── ReportController.php
│   │   └── ApiController.php
│   ├── models/          # Modelos
│   │   ├── User.php
│   │   ├── Institution.php
│   │   ├── Offer.php
│   │   ├── Service.php
│   │   ├── Career.php
│   │   ├── Tutor.php
│   │   ├── Agreement.php
│   │   ├── Milestone.php
│   │   ├── Supply.php
│   │   ├── Subject.php
│   │   ├── Request.php
│   │   ├── StudentProfile.php
│   │   ├── StudentDocument.php
│   │   ├── Rotation.php
│   │   ├── KanbanTask.php
│   │   ├── Notification.php
│   │   └── Report.php
│   ├── views/           # Vistas
│   │   ├── partials/    # Fragmentos reutilizables
│   │   ├── templates/   # Plantillas base
│   │   ├── auth/        # Vistas de autenticación
│   │   ├── admin/       # Vistas de administrador
│   │   ├── director/    # Vistas de director
│   │   ├── coordinator/ # Vistas de coordinador
│   │   ├── student/     # Vistas de estudiante
│   │   └── errors/      # Páginas de error
│   ├── helpers/         # Funciones auxiliares
│   │   ├── auth.php     # Funciones de autenticación
│   │   ├── validation.php # Validación de datos
│   │   ├── file.php     # Manejo de archivos
│   │   └── date.php     # Funciones de fecha
│   ├── core/            # Núcleo del framework
│   │   ├── App.php      # Clase principal
│   │   ├── Router.php   # Enrutador
│   │   ├── Request.php  # Manejo de peticiones
│   │   ├── Response.php # Manejo de respuestas
│   │   └── Database.php # Conexión a base de datos
│   └── middlewares/     # Middlewares
│       ├── Auth.php     # Verificación de autenticación
│       ├── Admin.php    # Restricción a administradores
│       ├── Director.php # Restricción a directores
│       ├── Coordinator.php # Restricción a coordinadores
│       └── Student.php  # Restricción a estudiantes
├── frontend/           # Código fuente frontend
│   ├── src/            # Código fuente
│   │   ├── js/         # JavaScript
│   │   │   ├── pages/  # Código específico por página
│   │   │   ├── components/ # Componentes reutilizables
│   │   │   ├── utils/  # Utilidades
│   │   │   └── main.js # Punto de entrada
│   │   ├── css/        # Estilos
│   │   │   ├── components/ # Estilos por componente
│   │   │   ├── pages/  # Estilos específicos por página
│   │   │   ├── base/   # Estilos base
│   │   │   └── main.css # Punto de entrada CSS
│   │   └── assets/     # Recursos estáticos
│   ├── package.json    # Dependencias frontend
│   ├── vite.config.js  # Configuración de Vite
│   └── index.html      # Plantilla HTML principal
├── database/           # Archivos relacionados con la base de datos
│   ├── sigeclin.sqlite # Archivo de base de datos SQLite
│   ├── migrations/     # Scripts de migración
│   │   └── init.sql    # Esquema inicial
│   ├── seeds/          # Datos de prueba
│   │   ├── users.php   # Datos de usuarios
│   │   └── institutions.php # Datos de instituciones
│   └── backups/        # Respaldos de base de datos
├── storage/            # Almacenamiento de archivos
│   ├── logs/           # Registros del sistema
│   ├── cache/          # Archivos de caché
│   └── temp/           # Archivos temporales
├── tests/              # Pruebas automatizadas
├── setup/              # Scripts de instalación
│   └── install.php     # Script principal de instalación
├── .env.example        # Ejemplo de variables de entorno
├── .gitignore          # Archivos ignorados por Git
├── composer.json       # Dependencias PHP (si aplica)
├── README.md           # Documentación general
└── LICENSE             # Licencia del proyecto
```

## 5. API REST

### 5.1 Estándares de API

- Se utilizará el formato JSON para todas las respuestas
- Autenticación mediante JWT (JSON Web Tokens)
- Se implementará versionado de API (v1)
- Se utilizarán códigos de estado HTTP estándar
- Todas las rutas de API estarán prefijadas con `/api/v1/`

### 5.2 Endpoints Principales

```
# Autenticación
POST   /api/v1/auth/login        - Iniciar sesión
POST   /api/v1/auth/logout       - Cerrar sesión
POST   /api/v1/auth/reset        - Solicitar reset de contraseña

# Usuarios
GET    /api/v1/users             - Listar usuarios
POST   /api/v1/users             - Crear usuario
GET    /api/v1/users/{id}        - Obtener usuario específico
PUT    /api/v1/users/{id}        - Actualizar usuario
DELETE /api/v1/users/{id}        - Eliminar usuario
GET    /api/v1/users/profile     - Obtener perfil del usuario actual

# Instituciones
GET    /api/v1/institutions      - Listar instituciones
POST   /api/v1/institutions      - Crear institución
GET    /api/v1/institutions/{id} - Obtener institución específica
PUT    /api/v1/institutions/{id} - Actualizar institución
DELETE /api/v1/institutions/{id} - Eliminar institución

# Ofertas Formadoras
GET    /api/v1/offers            - Listar ofertas
POST   /api/v1/offers            - Crear oferta
GET    /api/v1/offers/{id}       - Obtener oferta específica
PUT    /api/v1/offers/{id}       - Actualizar oferta
DELETE /api/v1/offers/{id}       - Eliminar oferta
GET    /api/v1/offers/{id}/services - Listar servicios de oferta
GET    /api/v1/offers/{id}/tutors   - Listar tutores de oferta

# Servicios
GET    /api/v1/services          - Listar servicios
POST   /api/v1/services          - Crear servicio
GET    /api/v1/services/{id}     - Obtener servicio específico
PUT    /api/v1/services/{id}     - Actualizar servicio
DELETE /api/v1/services/{id}     - Eliminar servicio

# Carreras
GET    /api/v1/careers           - Listar carreras
POST   /api/v1/careers           - Crear carrera
GET    /api/v1/careers/{id}      - Obtener carrera específica
PUT    /api/v1/careers/{id}      - Actualizar carrera
DELETE /api/v1/careers/{id}      - Eliminar carrera
GET    /api/v1/careers/{id}/subjects - Listar asignaturas de carrera

# Solicitudes
GET    /api/v1/requests          - Listar solicitudes
POST   /api/v1/requests          - Crear solicitud
GET    /api/v1/requests/{id}     - Obtener solicitud específica
PUT    /api/v1/requests/{id}     - Actualizar solicitud
DELETE /api/v1/requests/{id}     - Eliminar solicitud
PUT    /api/v1/requests/{id}/approve - Aprobar solicitud
PUT    /api/v1/requests/{id}/reject  - Rechazar solicitud

# Estudiantes
GET    /api/v1/students          - Listar estudiantes
POST   /api/v1/students          - Crear perfil de estudiante
GET    /api/v1/students/{id}     - Obtener estudiante específico
PUT    /api/v1/students/{id}     - Actualizar estudiante
DELETE /api/v1/students/{id}     - Eliminar estudiante
GET    /api/v1/students/{id}/documents - Listar documentos de estudiante
POST   /api/v1/students/{id}/documents - Subir documento de estudiante

# Rotaciones
GET    /api/v1/rotations         - Listar rotaciones
POST   /api/v1/rotations         - Crear rotación
GET    /api/v1/rotations/{id}    - Obtener rotación específica
PUT    /api/v1/rotations/{id}    - Actualizar rotación
DELETE /api/v1/rotations/{id}    - Eliminar rotación
PUT    /api/v1/rotations/{id}/lock - Bloquear rotación
PUT    /api/v1/rotations/{id}/unlock - Desbloquear rotación

# Kanban
GET    /api/v1/kanban            - Listar tareas de kanban
POST   /api/v1/kanban            - Crear tarea kanban
GET    /api/v1/kanban/{id}       - Obtener tarea específica
PUT    /api/v1/kanban/{id}       - Actualizar tarea
DELETE /api/v1/kanban/{id}       - Eliminar tarea
PUT    /api/v1/kanban/{id}/status - Actualizar estado de tarea

# Reportes
GET    /api/v1/reports           - Listar reportes
POST   /api/v1/reports/center    - Generar reporte por centro
POST   /api/v1/reports/career    - Generar reporte por carrera
GET    /api/v1/reports/{id}      - Descargar reporte específico
```

### 5.3 Formato de Respuestas

```json
// Respuesta exitosa
{
  "status": "success",
  "data": {
    // Datos específicos de la respuesta
  },
  "message": "Operación realizada con éxito"
}

// Respuesta de error
{
  "status": "error",
  "code": 400, // Código HTTP
  "message": "Descripción del error",
  "errors": [
    // Detalles específicos de los errores (si aplica)
  ]
}
```

## 6. Seguridad

### 6.1 Autenticación y Autorización

- Sistema de autenticación basado en JWT (JSON Web Tokens)
- Almacenamiento de contraseñas con hash usando `password_hash()`
- Control de acceso basado en roles (RBAC)
- Sesiones con tiempo de expiración configurable
- Bloqueo de cuentas después de intentos fallidos (configurable)
- Renovación automática de tokens

### 6.2 Protección de Datos

- Validación de entrada en cliente y servidor
- Prevención de ataques XSS mediante escape de salida
- Prevención de inyección SQL mediante uso de PDO con parámetros preparados
- Implementación de CSRF tokens para formularios
- Headers de seguridad (CSP, X-XSS-Protection, etc.)
- Limitación de rate para prevenir ataques de fuerza bruta

### 6.3 Manejo de Archivos

- Validación de tipos MIME para uploads
- Renombrado aleatorio de archivos para prevenir acceso no autorizado
- Permisos de directorio restringidos
- Limitación de tamaño de archivos
- Escaneo básico de contenido malicioso

## 7. Implementación Frontend

### 7.1 Estructura de Componentes

#### Componentes Globales
- `Header.js` - Barra de navegación superior
- `Sidebar.js` - Menú lateral contextual según rol
- `Footer.js` - Pie de página
- `Notifications.js` - Centro de notificaciones
- `LoadingSpinner.js` - Indicador de carga
- `Modal.js` - Modal reutilizable
- `Pagination.js` - Componente de paginación
- `Breadcrumbs.js` - Navegación de migas de pan

#### Componentes de Formulario
- `TextField.js` - Campo de texto con validación
- `SelectField.js` - Campo de selección desplegable
- `DateField.js` - Selector de fecha con calendario
- `FileUpload.js` - Carga de archivos con previsualización
- `TextareaField.js` - Campo de texto multilínea
- `Checkbox.js` - Casilla de verificación
- `RadioGroup.js` - Grupo de botones de opción
- `FormGroup.js` - Contenedor de formularios
- `SubmitButton.js` - Botón de envío con estado de carga

#### Componentes Específicos
- `KanbanBoard.js` - Tablero Kanban con columnas y tareas
- `GanttChart.js` - Visualización de carta Gantt para rotaciones
- `StudentCard.js` - Tarjeta de perfil de estudiante
- `DocumentViewer.js` - Visualizador de documentos PDF
- `RotationCalendar.js` - Calendario de rotaciones
- `StatsDashboard.js` - Panel de estadísticas y métricas
- `InstitutionCard.js` - Tarjeta de institución con detalles
- `ServicesList.js` - Lista de servicios con capacidad
- `RequestTracker.js` - Seguimiento de estado de solicitudes

### 7.2 Gestión de Estado

Se implementará un sistema de gestión de estado basado en módulos:

```javascript
// Ejemplo de estructura de estado
const appState = {
  auth: {
    isAuthenticated: false,
    currentUser: null,
    token: null,
    role: null
  },
  institutions: {
    list: [],
    current: null,
    loading: false,
    error: null
  },
  offers: {
    list: [],
    current: null,
    loading: false,
    error: null
  },
  students: {
    list: [],
    current: null,
    loading: false,
    error: null
  },
  rotations: {
    list: [],
    current: null,
    byService: {},
    byCareer: {},
    loading: false,
    error: null
  },
  kanban: {
    tasks: [],
    columns: ['pendiente', 'en_progreso', 'completada', 'bloqueada'],
    loading: false,
    error: null
  },
  ui: {
    sidebarOpen: true,
    currentView: 'dashboard',
    notifications: [],
    alerts: [],
    modals: {
      isOpen: false,
      content: null,
      title: ''
    }
  }
};
```

### 7.3 Sistema de Rutas Frontend

```javascript
// Estructura de rutas frontend
const routes = [
  // Rutas Públicas
  { path: '/', component: 'LandingPage', exact: true },
  { path: '/login', component: 'LoginPage' },
  { path: '/forgot-password', component: 'ForgotPasswordPage' },
  { path: '/reset-password/:token', component: 'ResetPasswordPage' },
  
  // Rutas Privadas Generales (requieren autenticación)
  { path: '/dashboard', component: 'Dashboard', private: true },
  { path: '/profile', component: 'ProfilePage', private: true },
  { path: '/notifications', component: 'NotificationsPage', private: true },
  
  // Rutas Administrador
  { path: '/admin/users', component: 'UserManagementPage', role: 'administrador' },
  { path: '/admin/institutions', component: 'InstitutionsManagementPage', role: 'administrador' },
  { path: '/admin/careers', component: 'CareersManagementPage', role: 'administrador' },
  { path: '/admin/requests', component: 'RequestsApprovalPage', role: 'administrador' },
  { path: '/admin/reports', component: 'ReportsGenerationPage', role: 'administrador' },
  
  // Rutas Director
  { path: '/director/offers', component: 'OffersManagementPage', role: 'director' },
  { path: '/director/services', component: 'ServicesManagementPage', role: 'director' },
  { path: '/director/tutors', component: 'TutorsManagementPage', role: 'director' },
  { path: '/director/coordinators', component: 'CoordinatorsManagementPage', role: 'director' },
  { path: '/director/rotations', component: 'RotationsApprovalPage', role: 'director' },
  { path: '/director/kanban', component: 'KanbanBoardPage', role: 'director' },
  
  // Rutas Coordinador
  { path: '/coordinator/requests', component: 'RequestsManagementPage', role: 'coordinador' },
  { path: '/coordinator/students', component: 'StudentsManagementPage', role: 'coordinador' },
  { path: '/coordinator/rotations', component: 'RotationsManagementPage', role: 'coordinador' },
  { path: '/coordinator/subjects', component: 'SubjectsManagementPage', role: 'coordinador' },
  { path: '/coordinator/kanban', component: 'KanbanBoardPage', role: 'coordinador' },
  
  // Rutas Estudiante
  { path: '/student/profile', component: 'StudentProfilePage', role: 'estudiante' },
  { path: '/student/documents', component: 'DocumentsUploadPage', role: 'estudiante' },
  { path: '/student/rotations', component: 'RotationsViewPage', role: 'estudiante' },
  
  // Ruta 404
  { path: '*', component: 'NotFoundPage' }
];
```

### 7.4 Paleta de Colores Institucionales

Se implementará un sistema de variables CSS para mantener la consistencia de colores:

```css
:root {
  /* Colores Institucionales UACh */
  --uach-red: #C8102E;      /* Pantone 200 */
  --uach-blue: #D4E5F7;     /* Pantone 290 */
  --uach-yellow: #FFC72C;   /* Pantone 123 */
  --uach-green: #00843D;    /* Pantone 349 */
  --uach-black: #000000;    /* Black */
  --uach-gray-1: #666666;   /* 60% Black */
  --uach-gray-2: #999999;   /* 40% Black */
  --uach-gray-3: #B3B3B3;   /* 30% Black */
  --uach-puerto-montt: #0097C4;  /* Pantone 314C */
  
  /* Variaciones funcionales */
  --primary: var(--uach-puerto-montt);
  --primary-light: #33ADCF;
  --primary-dark: #007A9E;
  --secondary: var(--uach-red);
  --secondary-light: #D34054;
  --secondary-dark: #A00C24;
  --accent: var(--uach-yellow);
  --success: var(--uach-green);
  --warning: var(--uach-yellow);
  --danger: var(--uach-red);
  --info: #17a2b8;
  
  /* Tonos de gris neutros */
  --gray-100: #f8f9fa;
  --gray-200: #e9ecef;
  --gray-300: #dee2e6;
  --gray-400: #ced4da;
  --gray-500: #adb5bd;
  --gray-600: var(--uach-gray-3);
  --gray-700: var(--uach-gray-2);
  --gray-800: var(--uach-gray-1);
  --gray-900: #212529;
  
  /* Colores de texto */
  --text-primary: var(--gray-900);
  --text-secondary: var(--gray-700);
  --text-muted: var(--gray-600);
  --text-light: var(--gray-100);
  
  /* Colores de fondo */
  --bg-primary: #ffffff;
  --bg-secondary: var(--gray-100);
  --bg-tertiary: var(--gray-200);
  
  /* Bordes y sombras */
  --border-color: var(--gray-300);
  --border-radius: 0.25rem;
  --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
```

## 8. Implementación Backend

### 8.1 Estructura MVC

#### Ejemplo de Controlador

```php
<?php
// app/controllers/RotationController.php

class RotationController
{
    private $model;
    private $auth;
    
    public function __construct()
    {
        $this->model = new Rotation();
        $this->auth = new AuthHelper();
    }
    
    public function index($request)
    {
        // Verificar permisos
        if (!$this->auth->hasPermission(['administrador', 'director', 'coordinador'])) {
            return Response::json([
                'status' => 'error',
                'message' => 'No tiene permisos para acceder a este recurso'
            ], 403);
        }
        
        // Obtener parámetros de consulta
        $filters = $request->getQueryParams();
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 20;
        
        // Aplicar filtros según rol
        $userId = $this->auth->getUserId();
        $userRole = $this->auth->getUserRole();
        
        if ($userRole === 'coordinador') {
            $filters['coordinator_id'] = $userId;
        }
        
        // Obtener rotaciones con paginación
        $result = $this->model->getAll($filters, $page, $perPage);
        
        return Response::json([
            'status' => 'success',
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($result['total'] / $perPage)
            ]
        ]);
    }
    
    public function show($request, $id)
    {
        // Verificar permisos
        if (!$this->auth->hasPermission(['administrador', 'director', 'coordinador', 'estudiante'])) {
            return Response::json([
                'status' => 'error',
                'message' => 'No tiene permisos para acceder a este recurso'
            ], 403);
        }
        
        // Obtener rotación
        $rotation = $this->model->getById($id);
        
        if (!$rotation) {
            return Response::json([
                'status' => 'error',
                'message' => 'Rotación no encontrada'
            ], 404);
        }
        
        // Verificar acceso según rol
        $userId = $this->auth->getUserId();
        $userRole = $this->auth->getUserRole();
        
        if ($userRole === 'estudiante' && $rotation['estudiante_id'] !== $userId) {
            return Response::json([
                'status' => 'error',
                'message' => 'No tiene permisos para acceder a esta rotación'
            ], 403);
        }
        
        if ($userRole === 'coordinador') {
            $hasAccess = $this->model->validateCoordinatorAccess($userId, $rotation['solicitud_id']);
            if (!$hasAccess) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'No tiene permisos para acceder a esta rotación'
                ], 403);
            }
        }
        
        return Response::json([
            'status' => 'success',
            'data' => $rotation
        ]);
    }
    
    // Otros métodos (store, update, destroy, etc.)
}
```

#### Ejemplo de Modelo

```php
<?php
// app/models/Rotation.php

class Rotation
{
    private $db;
    private $table = 'rotaciones';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function getAll($filters = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];
        
        // Construir cláusula WHERE basada en filtros
        if (!empty($filters)) {
            $conditions = [];
            
            if (isset($filters['solicitud_id'])) {
                $conditions[] = 'r.solicitud_id = :solicitud_id';
                $params[':solicitud_id'] = $filters['solicitud_id'];
            }
            
            if (isset($filters['estudiante_id'])) {
                $conditions[] = 'r.estudiante_id = :estudiante_id';
                $params[':estudiante_id'] = $filters['estudiante_id'];
            }
            
            if (isset($filters['servicio_id'])) {
                $conditions[] = 'r.servicio_id = :servicio_id';
                $params[':servicio_id'] = $filters['servicio_id'];
            }
            
            if (isset($filters['estado'])) {
                $conditions[] = 'r.estado = :estado';
                $params[':estado'] = $filters['estado'];
            }
            
            if (isset($filters['fecha_inicio'])) {
                $conditions[] = 'r.fecha_inicio >= :fecha_inicio';
                $params[':fecha_inicio'] = $filters['fecha_inicio'];
            }
            
            if (isset($filters['fecha_fin'])) {
                $conditions[] = 'r.fecha_fin <= :fecha_fin';
                $params[':fecha_fin'] = $filters['fecha_fin'];
            }
            
            if (isset($filters['coordinator_id'])) {
                $conditions[] = 's.coordinador_id = :coordinator_id';
                $params[':coordinator_id'] = $filters['coordinator_id'];
            }
            
            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            }
        }
        
        // Consultar total de registros
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM {$this->table} r
            LEFT JOIN solicitudes s ON r.solicitud_id = s.id
            $whereClause
        ";
        $stmt = $this->db->prepare($countQuery);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Consultar registros con paginación
        $query = "
            SELECT r.*, 
                   s.estado as solicitud_estado,
                   ep.usuario_id as estudiante_usuario_id,
                   u.nombre as estudiante_nombre,
                   u.rut as estudiante_rut,
                   se.nombre as servicio_nombre,
                   se.oferta_id as oferta_id,
                   of.nombre as oferta_nombre,
                   ins.nombre as institucion_nombre
            FROM {$this->table} r
            LEFT JOIN solicitudes s ON r.solicitud_id = s.id
            LEFT JOIN estudiantes_perfiles ep ON r.estudiante_id = ep.id
            LEFT JOIN usuarios u ON ep.usuario_id = u.id
            LEFT JOIN servicios se ON r.servicio_id = se.id
            LEFT JOIN ofertas_formadoras of ON se.oferta_id = of.id
            LEFT JOIN instituciones ins ON of.institucion_id = ins.id
            $whereClause
            ORDER BY r.fecha_inicio ASC, r.fecha_fin ASC
            LIMIT $perPage OFFSET $offset
        ";
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'total' => $total
        ];
    }
    
    public function getById($id)
    {
        $query = "
            SELECT r.*, 
                   s.estado as solicitud_estado,
                   s.asignatura_id,
                   ep.usuario_id as estudiante_usuario_id,
                   u.nombre as estudiante_nombre,
                   u.rut as estudiante_rut,
                   se.nombre as servicio_nombre,
                   se.oferta_id as oferta_id,
                   of.nombre as oferta_nombre,
                   ins.nombre as institucion_nombre,
                   a.nombre as asignatura_nombre,
                   a.codigo as asignatura_codigo,
                   c.nombre as carrera_nombre
            FROM {$this->table} r
            LEFT JOIN solicitudes s ON r.solicitud_id = s.id
            LEFT JOIN estudiantes_perfiles ep ON r.estudiante_id = ep.id
            LEFT JOIN usuarios u ON ep.usuario_id = u.id
            LEFT JOIN servicios se ON r.servicio_id = se.id
            LEFT JOIN ofertas_formadoras of ON se.oferta_id = of.id
            LEFT JOIN instituciones ins ON of.institucion_id = ins.id
            LEFT JOIN asignaturas a ON s.asignatura_id = a.id
            LEFT JOIN carreras c ON a.carrera_id = c.id
            WHERE r.id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function validateCoordinatorAccess($coordinadorId, $solicitudId)
    {
        $query = "
            SELECT COUNT(*) as access
            FROM solicitudes
            WHERE id = :solicitud_id AND coordinador_id = :coordinador_id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':solicitud_id', $solicitudId);
        $stmt->bindValue(':coordinador_id', $coordinadorId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['access'] > 0;
    }
    
    // Otros métodos (create, update, delete, etc.)
}
```

### 8.2 Helpers y Utilidades

#### Ejemplo de Helper de Autenticación

```php
<?php
// app/helpers/auth.php

class AuthHelper
{
    private $user = null;
    
    public function __construct()
    {
        $this->initialize();
    }
    
    private function initialize()
    {
        // Verificar token JWT en headers
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            }
        }
        
        if ($token) {
            try {
                // Verificar y decodificar token
                $key = Config::get('app.jwt_secret');
                $decoded = \Firebase\JWT\JWT::decode($token, $key, ['HS256']);
                
                // Buscar usuario en base de datos
                $userModel = new User();
                $this->user = $userModel->getById($decoded->user_id);
                
                // Verificar si el usuario está activo
                if (!$this->user || !$this->user['activo']) {
                    $this->user = null;
                }
            } catch (Exception $e) {
                // Token inválido o expirado
                $this->user = null;
            }
        }
    }
    
    public function isAuthenticated()
    {
        return $this->user !== null;
    }
    
    public function getUserId()
    {
        return $this->user ? $this->user['id'] : null;
    }
    
    public function getUserRole()
    {
        return $this->user ? $this->user['tipo'] : null;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function hasPermission($allowedRoles)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        return in_array($this->user['tipo'], $allowedRoles);
    }
    
    public function generateToken($user)
    {
        $key = Config::get('app.jwt_secret');
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60 * 24); // 24 horas
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user['id'],
            'email' => $user['correo'],
            'role' => $user['tipo']
        ];
        
        return \Firebase\JWT\JWT::encode($payload, $key);
    }
}
```

## 9. Visualizaciones y Reportes

### 9.1 Cartas Gantt para Rotaciones

Se implementarán dos tipos de visualizaciones Gantt:

#### Por Campo Clínico
- Eje X: Línea de tiempo (fechas)
- Eje Y: Servicios del centro clínico
- Cada barra representa una rotación
- Código de colores por carrera
- Filtros por fecha, servicio y estado

#### Por Carrera
- Eje X: Línea de tiempo (fechas)
- Eje Y: Estudiantes de la carrera
- Cada barra representa una rotación
- Código de colores por centro clínico/servicio
- Filtros por asignatura, fecha y estado

### 9.2 Tablero Kanban

El tablero Kanban tendrá las siguientes características:

- Columnas predefinidas: Pendiente, En Progreso, Completada, Bloqueada
- Tarjetas con información de tarea
- Código de colores por prioridad
- Asignación de responsables con avatar
- Fechas límite con indicadores visuales
- Drag & Drop para cambiar estado
- Filtros por responsable, prioridad y tipo

### 9.3 Reportes Exportables

Se generarán reportes en los siguientes formatos:

- PDF: Reportes formales para impresión
- Excel: Datos tabulares para análisis
- CSV: Exportación de datos simples

#### Tipos de Reportes

1. **Reporte de Rotaciones por Centro**
   - Listado de todas las rotaciones en un centro específico
   - Agrupado por servicio
   - Información de estudiantes y programas
   - Fechas y estados

2. **Reporte de Rotaciones por Carrera**
   - Listado de rotaciones para estudiantes de una carrera
   - Agrupado por estudiante o por asignatura
   - Centros y servicios asignados
   - Fechas y estados

3. **Reporte de Cumplimiento de Estudiantes**
   - Estado de documentación por estudiante
   - Certificados subidos y pendientes
   - Fechas de vencimiento

4. **Reporte de Hitos Críticos**
   - Listado de hitos por centro formador
   - Estado de cumplimiento
   - Fechas límite y responsables

5. **Reporte de Insumos**
   - Inventario de insumos por centro
   - Fechas de última y próxima entrega
   - Estado actual

## 10. Optimización y Rendimiento

### 10.1 Optimización de Base de Datos

- Índices clave implementados (ver sección 3.2)
- Consultas optimizadas con JOINs apropiados
- Paginación en todas las consultas de listado
- Transacciones para operaciones críticas
- Prevención de N+1 queries

### 10.2 Optimización Frontend

- Lazy loading de componentes
- Code splitting por rutas
- Minificación y compresión de assets
- Caching de API con estrategias adecuadas
- Optimización de imágenes (WebP donde sea posible)
- Implementación de service workers para assets estáticos

### 10.3 Caché

- Implementación de caché a nivel de API para recursos poco cambiantes
- Caché de consultas frecuentes en backend
- Estrategia de invalidación de caché por eventos

## 11. Pruebas

### 11.1 Pruebas Unitarias

Se implementarán pruebas unitarias para:
- Modelos de datos
- Helpers y utilidades
- Componentes de UI aislados

### 11.2 Pruebas de Integración

- Pruebas de API end-to-end
- Flujos de trabajo completos
- Interacción entre componentes

### 11.3 Pruebas de Carga

- Simulación de múltiples usuarios concurrentes
- Pruebas de límites de carga de archivos
- Rendimiento en dispositivos de gama baja

## 12. Despliegue

### 12.1 Requisitos del Servidor

- Servidor web Apache/Nginx
- PHP 8.1 o superior
- Extensiones PHP requeridas:
  - PDO SQLite
  - GD/Imagick
  - FileInfo
  - JSON
  - Mbstring
  - OpenSSL
- Permisos de escritura en directorios de almacenamiento
- HTTPS configurado

### 12.2 Proceso de Despliegue

1. Preparación del servidor
   - Instalación de dependencias
   - Configuración del servidor web
   - Configuración de permisos

2. Despliegue del código
   - Clonación desde repositorio
   - Compilación de assets frontend
   - Configuración de variables de entorno

3. Inicialización de la base de datos
   - Ejecución de migraciones
   - Carga de datos iniciales (administrador)

4. Verificación
   - Pruebas de acceso
   - Verificación de funcionalidades clave
   - Monitoreo inicial

### 12.3 Backups y Mantenimiento

- Respaldos diarios automáticos de la base de datos
- Respaldos manuales antes de actualizaciones
- Logs rotados periódicamente
- Monitoreo de espacio en disco y rendimiento

## 13. Consideraciones de Escalabilidad

Aunque el sistema está diseñado inicialmente con SQLite, se ha estructurado para permitir una migración a sistemas más robustos en el futuro:

- Abstracción de base de datos mediante PDO
- Separación clara entre lógica de negocio y acceso a datos
- API REST bien definida que permitiría escalar horizontalmente

Para una mayor escalabilidad futura, se podrían considerar:
- Migración a MySQL/PostgreSQL
- Implementación de caché distribuido
- Containerización con Docker
- Implementación de sistema de colas para tareas asíncronas

## 14. Documentación Adicional

### 14.1 Para Desarrolladores

- Guía de instalación del entorno de desarrollo
- Documentación de API
- Convenciones de código
- Flujo de trabajo con Git

### 14.2 Para Usuarios

- Manual de administración
- Manual por rol (Director, Coordinador, Estudiante)
- Guías de procedimientos específicos
- Preguntas frecuentes

## 15. Glosario Técnico

- **API REST**: Interfaz de Programación de Aplicaciones basada en el estilo arquitectónico REST
- **JWT**: JSON Web Token, método para autenticación basado en tokens
- **MVC**: Modelo-Vista-Controlador, patrón de arquitectura de software
- **PDO**: PHP Data Objects, capa de abstracción para acceso a bases de datos
- **SQLite**: Sistema de gestión de bases de datos relacional contenido en un archivo
- **Vite**: Herramienta de desarrollo frontend para aplicaciones web modernas
- **SPA**: Single Page Application, aplicación web que carga una única página HTML
- **CSRF**: Cross-Site Request Forgery, tipo de ataque web
- **XSS**: Cross-Site Scripting, tipo de ataque web