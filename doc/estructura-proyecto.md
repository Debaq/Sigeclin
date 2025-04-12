# SIGECLIN - Sistema Integrado de Gestión de Campos Clínicos

## 1. Descripción General

El sistema de gestión de campos clínicos es una plataforma web diseñada para organizar y gestionar solicitudes de prácticas clínicas para estudiantes del área de salud. Permite administrar instituciones, ofertas formadoras, solicitudes, rotaciones de estudiantes y seguimiento mediante un tablero Kanban.

## 2. Roles de Usuario

### 2.1 Administrador
- Acceso completo al sistema
- Creación y gestión de cuentas de director
- Aprobación final de solicitudes
- Gestión completa del tablero Kanban
- Visualización de reportes y estadísticas
- Administración de permisos y roles

### 2.2 Director
- Creación y gestión de ofertas formadoras
- Creación de perfiles de coordinadores
- Creación de perfiles de estudiantes
- Aprobación de rotaciones
- Visualización y gestión del tablero Kanban
- Generación de reportes

### 2.3 Coordinador
- Gestión de una o más coordinaciones de carrera
- Creación de solicitudes de campo clínico
- Gestión de perfiles de estudiantes
- Asignación de rotaciones
- Visualización del tablero Kanban
- Modificación de distribuciones de rotación

### 2.4 Estudiante
- Visualización y actualización de su perfil
- Carga de documentación (fotografía, certificados)
- Revisión de sus rotaciones asignadas
- Actualización de antecedentes personales y médicos

## 3. Módulos Principales

### 3.1 Gestión de Usuarios
- Sistema de autenticación
- Registro y gestión de usuarios según roles
- Recuperación de contraseñas
- Gestión de permisos
- Activación/desactivación de cuentas

### 3.2 Gestión de Instituciones
- Registro de instituciones formadoras
- Gestión de datos de contacto
- Almacenamiento de convenios (PDF)
- Historial de colaboraciones

### 3.3 Gestión de Ofertas Formadoras
- Creación de ofertas con capacidad formadora
- Definición de fechas de disponibilidad
- Registro de contactos y tutores
- Gestión de servicios asociados
- Control de insumos
- Seguimiento de hitos críticos

### 3.4 Gestión de Solicitudes
- Creación de solicitudes por coordinadores
- Vinculación con programas y asignaturas
- Carga de documentación académica
- Sistema de aprobación/rechazo
- Historial de cambios

### 3.5 Gestión de Estudiantes
- Registro de datos personales
- Gestión de documentación (vacunas, certificados)
- Asignación a rotaciones
- Historial académico
- Control de requisitos cumplidos

### 3.6 Gestión de Rotaciones
- Creación manual o simulada de distribuciones
- Asignación de estudiantes a servicios
- Control de fechas y horarios
- Bloqueo/desbloqueo de modificaciones
- Visualización tipo Gantt

### 3.7 Tablero Kanban
- Visualización de tareas pendientes
- Categorización por prioridades
- Asignación de responsables
- Seguimiento de hitos críticos
- Alertas de vencimiento
- Control de insumos

### 3.8 Sistema de Reportes
- Generación de reportes por centro formador
- Generación de reportes por unidad formadora
- Generación de reportes por programa
- Generación de reportes por estudiante
- Generación de reportes por carrera
- Exportación en PDF y Excel

## 4. Base de Datos

### 4.1 Tablas Principales
- Usuarios
- Instituciones
- Ofertas Formadoras
- Servicios
- Carreras
- Tutores
- Convenios
- Hitos Críticos
- Insumos
- Asignaturas
- Solicitudes
- Perfiles de Estudiantes
- Documentos de Estudiantes
- Rotaciones
- Tareas Kanban
- Notificaciones
- Reportes

### 4.2 Relaciones Clave
- Usuario-Rol
- Institución-Oferta
- Oferta-Servicio
- Solicitud-Asignatura
- Estudiante-Documentos
- Solicitud-Rotación
- Usuario-Tarea Kanban

## 5. Interfaz de Usuario

### 5.1 Diseño General
- Paleta de colores institucionales:
  - Rojo UACh: Pantone 200
  - Celeste UACh: Pantone 290
  - Amarillo UACh: Pantone 123
  - Verde UACh: Pantone 349
  - Negro UACh: Black
  - Gris 1: 60% Black
  - Gris 2: 40% Black
  - Gris 3: 30% Black
  - Azul Sede Puerto Montt: Pantone 314C
- Diseño responsive para todas las pantallas
- Logo institucional en lugar destacado
- Navegación intuitiva según rol
- Dashboards personalizados

### 5.2 Componentes Principales
- Formularios de gestión
- Tablero Kanban interactivo
- Calendario de rotaciones
- Visualizador de documentos
- Sistema de notificaciones
- Generador de reportes
- Diagramas de Gantt para rotaciones

## 6. Arquitectura Técnica

### 6.1 Frontend
- Vite como herramienta de desarrollo
- HTML5, CSS3 y JavaScript
- Diseño responsive (mobile-first)
- Framework CSS (Bootstrap u otro)
- Librerías para gráficos y visualizaciones
- Validación de formularios cliente/servidor

### 6.2 Backend
- PHP estructurado con patrón MVC
- API REST para comunicación con frontend
- Sistema de autenticación y autorización
- Lógica de negocio modular
- Gestión de archivos y documentos
- Sistema de notificaciones

### 6.3 Base de Datos
- SQLite como motor de base de datos
- Estructura relacional normalizada
- Índices para optimización de consultas
- Respaldos automatizados
- Transacciones para operaciones críticas

### 6.4 Seguridad
- Autenticación segura
- Cifrado de contraseñas
- Control de acceso basado en roles
- Protección contra inyección SQL
- Validación de datos de entrada
- Registro de actividades (logs)

## 7. Flujos de Trabajo Principales

### 7.1 Creación de Oferta Formadora
1. Administrador crea cuenta de director
2. Director ingresa al sistema
3. Director crea institución (si no existe)
4. Director crea oferta formadora
5. Director configura servicios, tutores e hitos
6. Director crea coordinadores asociados a la oferta

### 7.2 Creación de Solicitud
1. Coordinador ingresa al sistema
2. Coordinador selecciona oferta formadora
3. Coordinador crea solicitud
4. Coordinador carga programas y pautas
5. Coordinador registra estudiantes
6. Coordinador envía solicitud para aprobación

### 7.3 Gestión de Perfil de Estudiante
1. Estudiante recibe credenciales
2. Estudiante ingresa al sistema
3. Estudiante completa información personal
4. Estudiante carga fotografía
5. Estudiante carga certificados (vacunas, IAAS, RCP)
6. Sistema actualiza estado de completitud

### 7.4 Aprobación de Solicitud
1. Administrador revisa solicitud
2. Administrador verifica requisitos
3. Administrador aprueba o rechaza solicitud
4. Sistema notifica al coordinador
5. Si es aprobada, se habilita la distribución de rotaciones

### 7.5 Distribución de Rotaciones
1. Coordinador crea distribución (manual o simulada)
2. Coordinador asigna estudiantes a servicios
3. Coordinador define fechas de rotación
4. Director revisa distribución
5. Director aprueba o solicita cambios
6. Al aprobar, director puede bloquear modificaciones
7. Sistema genera carta Gantt de rotaciones

### 7.6 Seguimiento con Kanban
1. Director/Administrador crea tareas en Kanban
2. Sistema genera tareas automáticas desde hitos
3. Usuarios asignados reciben notificaciones
4. Usuarios actualizan estado de tareas
5. Sistema alerta sobre vencimientos próximos

## 8. Reportes y Salidas

### 8.1 Reportes Disponibles
- Listado de rotaciones por centro formador
- Listado de rotaciones por unidad formadora
- Listado de rotaciones por programa
- Historial de rotaciones por estudiante
- Estadísticas por carrera
- Control de documentación de estudiantes
- Estado de hitos críticos
- Inventario de insumos

### 8.2 Formatos de Exportación
- PDF
- Excel
- CSV
- Gantt (visualización e impresión)

## 9. Notificaciones

### 9.1 Tipos de Notificaciones
- Asignación de rotación
- Aprobación/rechazo de solicitud
- Vencimiento próximo de hito
- Actualización de documentación
- Modificación de rotación
- Tareas pendientes
- Requerimiento de insumos

### 9.2 Canales de Notificación
- Notificaciones en plataforma
- Correo electrónico (opcional)

## 10. Plan de Implementación

### 10.1 Fases de Desarrollo
1. **Fase 1**: Configuración del entorno y base de datos
2. **Fase 2**: Sistema de autenticación y gestión de usuarios
3. **Fase 3**: Gestión de instituciones y ofertas
4. **Fase 4**: Gestión de solicitudes y estudiantes
5. **Fase 5**: Sistema de rotaciones
6. **Fase 6**: Tablero Kanban y notificaciones
7. **Fase 7**: Reportes y exportaciones
8. **Fase 8**: Pruebas y optimización

### 10.2 Prioridades de Implementación
1. Funcionalidades core (usuarios, autenticación)
2. Gestión de datos maestros (instituciones, ofertas)
3. Flujos de trabajo principales
4. Funcionalidades de seguimiento (Kanban)
5. Reportes y análisis
6. Optimizaciones y mejoras de UX