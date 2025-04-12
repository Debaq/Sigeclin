#!/bin/bash
# setup_sigeclin.sh
# Script para crear la estructura inicial del proyecto SIGECLIN

# Colores para mensajes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Iniciando configuración del proyecto SIGECLIN...${NC}"

# 1. Crear la estructura principal de directorios
echo -e "${GREEN}Creando estructura principal de directorios...${NC}"
mkdir -p sigeclin/public/{assets/{css,js,fonts,images},uploads/{photos,documents,programs,agreements}}
mkdir -p sigeclin/app/{config,controllers,models,views/{partials,templates,auth,admin,director,coordinator,student,errors},helpers,core,middlewares}
mkdir -p sigeclin/frontend/{src/{js/{pages,components,utils},css/{components,pages,base},assets}}
mkdir -p sigeclin/database/{migrations,seeds,backups}
mkdir -p sigeclin/storage/{logs,cache,temp}
mkdir -p sigeclin/tests
mkdir -p sigeclin/setup

# 2. Crear archivos básicos en public
echo -e "${GREEN}Creando archivos básicos en public...${NC}"
touch sigeclin/public/index.php
touch sigeclin/public/.htaccess

# 3. Crear archivos de configuración en app/config
echo -e "${GREEN}Creando archivos de configuración...${NC}"
touch sigeclin/app/config/config.php
touch sigeclin/app/config/database.php
touch sigeclin/app/config/routes.php

# 4. Crear archivos principales en el core
echo -e "${GREEN}Creando archivos core...${NC}"
touch sigeclin/app/core/App.php
touch sigeclin/app/core/Router.php
touch sigeclin/app/core/Request.php
touch sigeclin/app/core/Response.php
touch sigeclin/app/core/Database.php

# 5. Crear controladores principales
echo -e "${GREEN}Creando controladores...${NC}"
touch sigeclin/app/controllers/AuthController.php
touch sigeclin/app/controllers/UserController.php
touch sigeclin/app/controllers/InstitutionController.php
touch sigeclin/app/controllers/OfferController.php
touch sigeclin/app/controllers/RequestController.php
touch sigeclin/app/controllers/StudentController.php
touch sigeclin/app/controllers/RotationController.php
touch sigeclin/app/controllers/KanbanController.php
touch sigeclin/app/controllers/ReportController.php
touch sigeclin/app/controllers/ApiController.php

# 6. Crear modelos principales
echo -e "${GREEN}Creando modelos...${NC}"
touch sigeclin/app/models/User.php
touch sigeclin/app/models/Institution.php
touch sigeclin/app/models/Offer.php
touch sigeclin/app/models/Service.php
touch sigeclin/app/models/Career.php
touch sigeclin/app/models/Tutor.php
touch sigeclin/app/models/Agreement.php
touch sigeclin/app/models/Milestone.php
touch sigeclin/app/models/Supply.php
touch sigeclin/app/models/Subject.php
touch sigeclin/app/models/Request.php
touch sigeclin/app/models/StudentProfile.php
touch sigeclin/app/models/StudentDocument.php
touch sigeclin/app/models/Rotation.php
touch sigeclin/app/models/KanbanTask.php
touch sigeclin/app/models/Notification.php
touch sigeclin/app/models/Report.php

# 7. Crear helpers
echo -e "${GREEN}Creando helpers...${NC}"
touch sigeclin/app/helpers/auth.php
touch sigeclin/app/helpers/validation.php
touch sigeclin/app/helpers/file.php
touch sigeclin/app/helpers/date.php

# 8. Crear middlewares
echo -e "${GREEN}Creando middlewares...${NC}"
touch sigeclin/app/middlewares/Auth.php
touch sigeclin/app/middlewares/Admin.php
touch sigeclin/app/middlewares/Director.php
touch sigeclin/app/middlewares/Coordinator.php
touch sigeclin/app/middlewares/Student.php

# 9. Crear archivos de base de datos
echo -e "${GREEN}Creando archivos de base de datos...${NC}"
touch sigeclin/database/migrations/init.sql
touch sigeclin/database/seeds/users.php
touch sigeclin/database/seeds/institutions.php
touch sigeclin/database/.gitkeep

# 10. Crear archivos de configuración del proyecto
echo -e "${GREEN}Creando archivos de configuración del proyecto...${NC}"
touch sigeclin/.env.example
touch sigeclin/.gitignore
touch sigeclin/README.md
touch sigeclin/LICENSE
touch sigeclin/setup/install.php

# 11. Añadir .gitkeep a directorios vacíos para mantenerlos en control de versiones
echo -e "${GREEN}Añadiendo .gitkeep a directorios vacíos...${NC}"
find sigeclin -type d -empty -exec touch {}/.gitkeep \;

# 12. Establecer permisos adecuados para directorios que requieren escritura
echo -e "${GREEN}Estableciendo permisos...${NC}"
chmod -R 755 sigeclin/public
chmod -R 777 sigeclin/public/uploads
chmod -R 777 sigeclin/storage

# 13. Crear package.json y vite.config.js para frontend
echo -e "${GREEN}Configurando frontend con Vite y React...${NC}"
cd sigeclin/frontend

# Crear package.json para React + Vite
cat > package.json << 'EOL'
{
  "name": "sigeclin-frontend",
  "private": true,
  "version": "0.1.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "axios": "^1.6.0",
    "bootstrap": "^5.3.2",
    "chart.js": "^4.4.0",
    "filepond": "^4.30.4",
    "frappe-gantt": "^0.6.1",
    "fullcalendar": "^6.1.9",
    "jspdf": "^2.5.1",
    "exceljs": "^4.3.0",
    "papaparse": "^5.4.1",
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.18.0",
    "sortablejs": "^1.15.0"
  },
  "devDependencies": {
    "@types/react": "^18.2.15",
    "@types/react-dom": "^18.2.7",
    "@vitejs/plugin-react": "^4.0.3",
    "eslint": "^8.45.0",
    "eslint-plugin-react": "^7.32.2",
    "eslint-plugin-react-hooks": "^4.6.0",
    "eslint-plugin-react-refresh": "^0.4.3",
    "sass": "^1.69.5",
    "vite": "^4.4.5"
  }
}
EOL

# Crear vite.config.js
cat > vite.config.js << 'EOL'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/api')
      }
    }
  },
  build: {
    outDir: '../public/assets',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          ui: ['bootstrap'],
          charts: ['chart.js', 'frappe-gantt', 'fullcalendar'],
        }
      }
    }
  }
})
EOL

# Crear index.html para Vite
cat > index.html << 'EOL'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SIGECLIN - Sistema Integrado de Gestión de Campos Clínicos</title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/js/main.js"></script>
  </body>
</html>
EOL

# Crear main.js básico
mkdir -p src/js
cat > src/js/main.js << 'EOL'
import React from 'react'
import ReactDOM from 'react-dom/client'
import '../css/main.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <div className="container mt-5">
      <h1>SIGECLIN</h1>
      <p>Sistema Integrado de Gestión de Campos Clínicos</p>
    </div>
  </React.StrictMode>
)
EOL

# Crear main.css básico
mkdir -p src/css
cat > src/css/main.css << 'EOL'
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
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
}

h1 {
  color: var(--primary);
}
EOL

cd ../..

# 14. Agregar contenido básico al README
cat > sigeclin/README.md << 'EOL'
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
EOL

# 15. Agregar contenido básico al .gitignore
cat > sigeclin/.gitignore << 'EOL'
# Directorios
node_modules/
vendor/
public/assets/
storage/logs/*
storage/cache/*
storage/temp/*

# Excepciones para mantener estructura de directorios
!storage/logs/.gitkeep
!storage/cache/.gitkeep
!storage/temp/.gitkeep

# Base de datos
database/*.sqlite
database/backups/*
!database/backups/.gitkeep

# Archivos de configuración
.env

# Archivos de sistema
.DS_Store
Thumbs.db

# Archivos de IDEs y editores
.idea/
.vscode/
*.sublime-project
*.sublime-workspace
EOL

echo -e "${BLUE}Estructura inicial del proyecto SIGECLIN creada con éxito!${NC}"
echo -e "${GREEN}Para configurar el frontend:${NC}"
echo -e "  cd sigeclin/frontend"
echo -e "  npm install"
echo -e "  npm run dev"