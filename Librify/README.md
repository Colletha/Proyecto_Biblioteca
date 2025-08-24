# Sistema de Biblioteca Escolar

Sistema web completo para la administración de una biblioteca escolar desarrollado en PHP, MySQL, HTML5, CSS3, JavaScript y Bootstrap 5.

## 🚀 Características Principales

- **Sistema de Autenticación**: Login seguro con roles (Administrador, Bibliotecario, Alumno)
- **Gestión de Libros**: CRUD completo con búsqueda y filtros
- **Gestión de Usuarios**: Administración de usuarios por roles
- **Sistema de Préstamos**: Registro y control de préstamos/devoluciones
- **Sistema de Multas**: Cálculo automático por retrasos
- **Reportes Completos**: Estadísticas y reportes imprimibles
- **Interfaz Responsiva**: Compatible con dispositivos móviles

## 📋 Requisitos del Sistema

- XAMPP (Apache + MySQL + PHP 7.4+)
- Navegador web moderno
- 50MB de espacio en disco

## 🛠️ Instalación en XAMPP

### Paso 1: Preparar el Entorno
1. Descargar e instalar XAMPP desde [https://www.apachefriends.org](https://www.apachefriends.org)
2. Iniciar Apache y MySQL desde el panel de control de XAMPP

### Paso 2: Instalar el Sistema
1. Extraer todos los archivos del proyecto en la carpeta `htdocs` de XAMPP
   \`\`\`
   C:\xampp\htdocs\biblioteca-escolar\
   \`\`\`

### Paso 3: Configurar la Base de Datos
1. Abrir phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Crear una nueva base de datos llamada `biblioteca_escolar`
3. Importar el archivo `database/biblioteca_escolar.sql`
4. Verificar que todas las tablas se hayan creado correctamente

### Paso 4: Configurar la Conexión
1. Editar el archivo `config/database.php` si es necesario
2. Verificar que los datos de conexión sean correctos:
   \`\`\`php
   $host = 'localhost';
   $dbname = 'biblioteca_escolar';
   $username = 'root';
   $password = '';
   \`\`\`

### Paso 5: Acceder al Sistema
1. Abrir el navegador y ir a: [http://localhost/biblioteca-escolar](http://localhost/biblioteca-escolar)
2. Usar las credenciales de prueba para acceder

## 👥 Credenciales de Acceso

### Administrador
- **Usuario**: admin@biblioteca.com
- **Contraseña**: admin123
- **Permisos**: Acceso completo al sistema

### Bibliotecario
- **Usuario**: bibliotecario@biblioteca.com
- **Contraseña**: biblio123
- **Permisos**: Gestión de libros, usuarios y préstamos

### Alumno
- **Usuario**: alumno@biblioteca.com
- **Contraseña**: alumno123
- **Permisos**: Solo consulta de libros disponibles

## 📁 Estructura del Proyecto

\`\`\`
biblioteca-escolar/
├── assets/
│   ├── css/
│   │   └── dashboard.css
│   └── js/
│       └── dashboard.js
├── config/
│   ├── database.php
│   └── session.php
├── database/
│   └── biblioteca_escolar.sql
├── includes/
│   ├── navbar.php
│   └── sidebar.php
├── libros/
│   ├── index.php
│   ├── crear.php
│   ├── editar.php
│   └── eliminar.php
├── prestamos/
│   ├── index.php
│   ├── crear.php
│   └── devolver.php
├── reportes/
│   ├── index.php
│   ├── reporte_usuarios.php
│   ├── reporte_prestamos_activos.php
│   └── reporte_libros_disponibles.php
├── usuarios/
│   ├── index.php
│   ├── crear.php
│   ├── editar.php
│   └── eliminar.php
├── dashboard.php
├── login.php
├── logout.php
└── README.md
\`\`\`

## 🔧 Funcionalidades por Rol

### Administrador
- ✅ Gestión completa de usuarios
- ✅ Gestión completa de libros
- ✅ Gestión de préstamos y devoluciones
- ✅ Acceso a todos los reportes
- ✅ Configuración del sistema

### Bibliotecario
- ✅ Gestión de libros (crear, editar, eliminar)
- ✅ Gestión de préstamos y devoluciones
- ✅ Acceso a reportes
- ❌ No puede gestionar usuarios administradores

### Alumno
- ✅ Consulta de libros disponibles
- ✅ Ver sus propios préstamos
- ❌ No puede realizar operaciones administrativas

## 📊 Módulos del Sistema

### 1. Gestión de Libros
- Registro de nuevos libros
- Edición de información
- Control de inventario
- Búsqueda y filtros
- Categorización

### 2. Gestión de Usuarios
- Registro de usuarios por rol
- Edición de perfiles
- Control de acceso
- Validación de datos

### 3. Sistema de Préstamos
- Registro de préstamos
- Control de fechas límite
- Proceso de devolución
- Cálculo automático de multas
- Historial completo

### 4. Sistema de Reportes
- Reporte por usuario
- Préstamos activos
- Libros disponibles
- Estadísticas generales
- Exportación para impresión

## 🔒 Seguridad Implementada

- Contraseñas encriptadas con password_hash()
- Validación de sesiones
- Protección contra inyección SQL (PDO)
- Validación de datos de entrada
- Control de acceso por roles
- Sanitización de salidas HTML

## 🐛 Solución de Problemas

### Error de Conexión a la Base de Datos
1. Verificar que MySQL esté ejecutándose en XAMPP
2. Comprobar las credenciales en `config/database.php`
3. Asegurar que la base de datos `biblioteca_escolar` exista

### Problemas de Permisos
1. Verificar que los archivos tengan permisos de lectura/escritura
2. Comprobar que Apache tenga acceso a la carpeta del proyecto

### Errores de Sesión
1. Verificar que las cookies estén habilitadas
2. Comprobar la configuración de sesiones en PHP

## 📝 Notas de Desarrollo

- Desarrollado siguiendo las mejores prácticas de PHP
- Código comentado y estructurado
- Base de datos normalizada
- Interfaz responsiva con Bootstrap 5
- Validaciones tanto del lado cliente como servidor

## 🎓 Proyecto Académico

Este sistema fue desarrollado como proyecto final para Quinto Bachillerato en Computación, cumpliendo con todos los requisitos establecidos en las tres fases del proyecto:

- **Fase 1**: Análisis, diseño y sistema de login ✅
- **Fase 2**: CRUDs y gestión de préstamos ✅
- **Fase 3**: Reportes y documentación ✅

## 📞 Soporte

Para soporte técnico o consultas sobre el sistema, contactar al desarrollador del proyecto.

---

**Versión**: 1.0  
**Fecha**: 2024  
**Tecnologías**: PHP 8.0+, MySQL 8.0+, Bootstrap 5, HTML5, CSS3, JavaScript
