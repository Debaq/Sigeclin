# SIGECLIN - Sistema Integrado de Gestión de Campos Clínicos UACh

![Logo UACh](https://placeholder-for-uach-logo.png)

## Descripción

SIGECLIN (Sistema Integrado de Gestión de Campos Clínicos) es una plataforma web desarrollada para la Universidad Austral de Chile (UACh), sede Puerto Montt Ciencias de la Salud. Esta herramienta permite organizar y gestionar de manera eficiente las solicitudes de campos clínicos, la distribución de estudiantes en rotaciones, y el seguimiento de las prácticas profesionales mediante un tablero Kanban integrado.

## Características Principales

- **Gestión multi-rol**: Administradores, directores, coordinadores y estudiantes
- **Administración de instituciones y ofertas formadoras**
- **Solicitudes de campos clínicos**
- **Gestión de rotaciones de estudiantes**
- **Tablero Kanban para seguimiento de actividades**
- **Reportes personalizados por diferentes criterios**
- **Manejo de documentos (convenios, certificados, etc.)**
- **Interfaz responsive con colores institucionales UACh**

## Tecnologías Utilizadas

- **Frontend**: Vite, HTML5, CSS3, JavaScript
- **Backend**: PHP (Estructura MVC)
- **Base de Datos**: SQLite
- **Diseño Responsive**: Compatible con dispositivos móviles y escritorio

## Requisitos del Sistema

### Requisitos de Servidor
- Servidor web con soporte para PHP 7.4 o superior
- Soporte para SQLite 3
- Espacio en disco para almacenamiento de archivos (mínimo 500MB recomendado)
- Memoria: 2GB RAM mínimo recomendado

### Requisitos de Cliente
- Navegador web moderno (Chrome, Firefox, Safari, Edge en sus versiones recientes)
- JavaScript habilitado
- Resolución de pantalla mínima recomendada: 1280x720

## Instalación

### Preparación del Entorno
1. Clonar el repositorio:
   ```bash
   git clone https://github.com/uach/sistema-gestion-campos-clinicos.git
   cd sistema-gestion-campos-clinicos
   ```

2. Configurar servidor web:
   - Configurar un servidor web (Apache/Nginx) apuntando al directorio `public`
   - Asegurarse que PHP tenga permisos de escritura en el directorio `storage`

3. Inicializar la base de datos:
   ```bash
   php setup/init-database.php
   ```

4. Configurar el entorno:
   - Copiar `.env.example` a `.env`
   - Editar `.env` con la configuración específica del entorno

5. Compilar assets frontend:
   ```bash
   cd frontend
   npm install
   npm run build
   ```

## Estructura del Proyecto

```
sistema-gestion-campos-clinicos/
├── public/                  # Punto de entrada para el servidor web
│   ├── index.php            # Archivo principal de entrada
│   ├── assets/              # Archivos compilados CSS/JS
│   └── uploads/             # Archivos subidos por usuarios
├── app/                     # Código backend PHP
│   ├── Controllers/         # Controladores
│   ├── Models/              # Modelos de datos
│   ├── Views/               # Vistas y plantillas
│   ├── Helpers/             # Funciones auxiliares
│   └── Config/              # Archivos de configuración
├── frontend/                # Código frontend Vite
│   ├── src/                 # Código fuente
│   │   ├── components/      # Componentes reutilizables
│   │   ├── pages/           # Páginas principales
│   │   ├── styles/          # Estilos CSS
│   │   └── main.js          # Punto de entrada frontend
│   ├── package.json         # Dependencias frontend
│   └── vite.config.js       # Configuración de Vite
├── database/                # Archivos de base de datos SQLite
│   ├── migrations/          # Scripts de migración
│   └── seeds/               # Datos iniciales
├── storage/                 # Almacenamiento de archivos
│   ├── documents/           # Documentos subidos
│   ├── logs/                # Registros de la aplicación
│   └── temp/                # Archivos temporales
├── setup/                   # Scripts de instalación
├── vendor/                  # Dependencias PHP (si las hay)
├── .env.example             # Ejemplo de configuración
├── composer.json            # Dependencias PHP (si las hay)
└── README.md                # Este archivo
```

## Roles de Usuario y Funcionalidades

### Administrador
- Acceso completo al sistema
- Gestión de usuarios y permisos
- Aprobación final de solicitudes
- Administración del tablero Kanban
- Generación de todos los reportes

### Director
- Creación y gestión de ofertas formadoras
- Creación de perfiles de coordinadores y estudiantes
- Administración de la oferta formadora
- Aprobación de rotaciones
- Gestión del tablero Kanban

### Coordinador
- Gestión de coordinaciones de carrera
- Creación y seguimiento de solicitudes
- Gestión de perfiles de estudiantes
- Creación y modificación de distribuciones de rotación
- Visualización del tablero Kanban

### Estudiante
- Actualización de perfil personal
- Carga de documentos (fotografía, certificados)
- Gestión de datos médicos y de contacto
- Visualización de asignaciones de rotación

## Flujos de Trabajo Principales

### Creación y Gestión de Ofertas Formadoras
1. El administrador crea cuenta(s) de director
2. El director crea la institución y la oferta formadora
3. El director configura capacidades, servicios y tutores
4. El director crea coordinadores asociados a la oferta

### Solicitud de Campos Clínicos
1. El coordinador selecciona una oferta formadora
2. El coordinador crea una solicitud con programas y documentación
3. El coordinador registra estudiantes asociados a la solicitud
4. El administrador revisa y aprueba/rechaza la solicitud

### Gestión de Rotaciones
1. El coordinador crea/modifica la distribución de rotaciones
2. El director revisa y aprueba las rotaciones
3. El director puede bloquear/desbloquear modificaciones
4. El sistema genera reportes de distribución (carta Gantt por campo clínico y por carrera)

### Gestión de Perfiles de Estudiantes
1. Estudiantes acceden al sistema con credenciales proporcionadas
2. Estudiantes completan información personal y médica
3. Estudiantes cargan documentos requeridos (foto, certificados)
4. Coordinadores verifican la completitud de perfiles

## Configuraciones Personalizables

### Colores Institucionales
- Rojo UACh: Pantone 200
- Celeste UACh: Pantone 290
- Amarillo UACh: Pantone 123
- Verde UACh: Pantone 349
- Negro UACh: Black
- Gris 1: 60% Black
- Gris 2: 40% Black
- Gris 3: 30% Black
- Azul Sede Puerto Montt: Pantone 314C

Estos colores están implementados en el sistema mediante variables CSS y pueden ser ajustados en `frontend/src/styles/variables.css`.

## Seguridad

- Autenticación segura con contraseñas cifradas
- Control de acceso basado en roles (RBAC)
- Protección contra inyección SQL
- Validación de datos en cliente y servidor
- Registro completo de actividades (logs)
- Cifrado de datos sensibles

## Mantenimiento y Respaldos

### Respaldos Automáticos
El sistema realiza respaldos automáticos de la base de datos SQLite diariamente. Los archivos de respaldo se almacenan en `storage/backups/`.

Para realizar un respaldo manual:
```bash
php app/Commands/backup.php
```

### Restauración
Para restaurar desde un respaldo:
```bash
php app/Commands/restore.php --file=nombre_respaldo.sqlite
```

## Reportes Disponibles

- **Por Centro Formador**: Distribución de estudiantes por institución
- **Por Unidad Formadora**: Distribución por servicios específicos
- **Por Programa**: Agrupación por asignaturas
- **Por Estudiante**: Historial individual de rotaciones
- **Por Carrera**: Estadísticas agrupadas por carrera

Todos los reportes pueden exportarse en formato PDF y Excel.

## Soporte Técnico

Para reportar problemas o solicitar asistencia, contactar a:
- **Correo**: soporte.campos-clinicos@uach.cl
- **Teléfono**: +56 XX XXXX XXXX

## Equipo de Desarrollo

- Universidad Austral de Chile
- Facultad de Medicina
- Sede Puerto Montt Ciencias de la Salud

## Licencia

Este software es propiedad de la Universidad Austral de Chile. Todos los derechos reservados.

---

© 2025 Universidad Austral de Chile - Sede Puerto Montt Ciencias de la Salud