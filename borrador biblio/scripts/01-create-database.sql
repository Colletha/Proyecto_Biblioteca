-- Script para crear la base de datos del sistema de biblioteca escolar
-- Ejecutar este script primero para crear todas las tablas necesarias

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar roles por defecto
INSERT INTO roles (nombre, descripcion) VALUES 
('administrador', 'Acceso completo al sistema'),
('bibliotecario', 'Gestión de libros y préstamos'),
('alumno', 'Consulta de libros y préstamos propios');

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    carne VARCHAR(20) UNIQUE,
    correo VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de categorías de libros
CREATE TABLE IF NOT EXISTS categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar categorías por defecto
INSERT INTO categorias (nombre, descripcion) VALUES 
('Ficción', 'Novelas y cuentos'),
('No Ficción', 'Libros informativos y educativos'),
('Ciencias', 'Libros de ciencias naturales y exactas'),
('Historia', 'Libros de historia y biografías'),
('Literatura', 'Clásicos de la literatura'),
('Tecnología', 'Libros de informática y tecnología'),
('Arte', 'Libros de arte y cultura'),
('Deportes', 'Libros relacionados con deportes');

-- Tabla de libros
CREATE TABLE IF NOT EXISTS libros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    editorial VARCHAR(100),
    isbn VARCHAR(20) UNIQUE,
    categoria_id INT NOT NULL,
    cantidad_total INT NOT NULL DEFAULT 1,
    cantidad_disponible INT NOT NULL DEFAULT 1,
    ubicacion VARCHAR(50),
    descripcion TEXT,
    fecha_publicacion DATE,
    estado ENUM('disponible', 'agotado', 'mantenimiento') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de préstamos
CREATE TABLE IF NOT EXISTS prestamos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    libro_id INT NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_devolucion_esperada DATE NOT NULL,
    fecha_devolucion_real DATE NULL,
    estado ENUM('activo', 'devuelto', 'vencido') DEFAULT 'activo',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (libro_id) REFERENCES libros(id)
);

-- Tabla de multas (opcional para control de retrasos)
CREATE TABLE IF NOT EXISTS multas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    dias_retraso INT NOT NULL DEFAULT 0,
    pagada BOOLEAN DEFAULT FALSE,
    fecha_multa DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
);

-- Índices para mejorar rendimiento
CREATE INDEX idx_usuarios_carne ON usuarios(carne);
CREATE INDEX idx_usuarios_correo ON usuarios(correo);
CREATE INDEX idx_libros_titulo ON libros(titulo);
CREATE INDEX idx_libros_autor ON libros(autor);
CREATE INDEX idx_libros_isbn ON libros(isbn);
CREATE INDEX idx_prestamos_usuario ON prestamos(usuario_id);
CREATE INDEX idx_prestamos_libro ON prestamos(libro_id);
CREATE INDEX idx_prestamos_estado ON prestamos(estado);
CREATE INDEX idx_prestamos_fecha ON prestamos(fecha_prestamo);
