# SIGECLIN - Sistema Integrado de Gestión de Campos Clínicos

## Descripción
Sistema web diseñado para organizar y gestionar solicitudes de prácticas clínicas para estudiantes del área de salud. Permite administrar instituciones, ofertas formadoras, solicitudes, rotaciones de estudiantes y seguimiento mediante un tablero Kanban.

## Requisitos
- PHP 8.1 o superior
- Node.js 16.x o superior
- Extensiones PHP: PDO SQLite, GD/Imagick, FileInfo, JSON, Mbstring, OpenSSL

## Instalación

### Backend
1. Clonar el repositorio
2. Configurar el servidor web para apuntar a la carpeta `public`
3. Copiar `.env.example` a `.env` y configurar variables
4. Ejecutar `php setup/install.php` para inicializar la base de datos

### Frontend
1. Navegar a la carpeta `frontend`
2. Ejecutar `npm install` para instalar dependencias
3. Ejecutar `npm run dev` para desarrollo o `npm run build` para producción

## Estructura del Proyecto
- `public/`: Punto de entrada público
- `app/`: Código PHP de la aplicación (MVC)
- `frontend/`: Código fuente de la interfaz (React + Vite)
- `database/`: Archivos de base de datos SQLite y migraciones
- `storage/`: Almacenamiento de archivos y logs

## Licencia
Este proyecto es propiedad de la Universidad Austral de Chile
