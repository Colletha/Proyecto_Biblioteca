-- Base de datos para Sistema de Biblioteca Escolar
-- Compatible con MySQL/XAMPP

CREATE DATABASE IF NOT EXISTS biblioteca_escolar;
USE biblioteca_escolar;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    carne VARCHAR(20) UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de categorías de libros
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de libros
CREATE TABLE libros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    editorial VARCHAR(100),
    isbn VARCHAR(20) UNIQUE,
    categoria_id INT,
    cantidad_total INT NOT NULL DEFAULT 1,
    cantidad_disponible INT NOT NULL DEFAULT 1,
    ubicacion VARCHAR(100),
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de préstamos
CREATE TABLE prestamos (
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

-- Tabla de multas
CREATE TABLE multas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    dias_retraso INT NOT NULL DEFAULT 0,
    pagada BOOLEAN DEFAULT FALSE,
    fecha_pago DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
);

-- Insertar roles por defecto
INSERT INTO roles (nombre, descripcion) VALUES
('administrador', 'Acceso completo al sistema'),
('bibliotecario', 'Gestión de libros y préstamos'),
('alumno', 'Consulta de libros y préstamos propios');

-- Insertar categorías por defecto
INSERT INTO categorias (nombre, descripcion) VALUES
('Ficción', 'Novelas y cuentos de ficción'),
('No Ficción', 'Libros informativos y educativos'),
('Ciencias', 'Libros de ciencias naturales y exactas'),
('Historia', 'Libros de historia y biografías'),
('Literatura', 'Clásicos de la literatura universal'),
('Tecnología', 'Libros de informática y tecnología'),
('Arte', 'Libros de arte y cultura'),
('Deportes', 'Libros relacionados con deportes');

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, email, carne, password, rol_id) VALUES
('Admin', 'Sistema', 'admin@biblioteca.edu', 'ADMIN001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('María', 'González', 'bibliotecaria@biblioteca.edu', 'BIB001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Juan', 'Pérez', 'juan.perez@estudiante.edu', '2024001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- Insertar libros de ejemplo
INSERT INTO libros (titulo, autor, editorial, isbn, categoria_id, cantidad_total, cantidad_disponible, ubicacion) VALUES
('Cien años de soledad', 'Gabriel García Márquez', 'Editorial Sudamericana', '9788437604947', 5, 3, 3, 'Estante A-1'),
('El principito', 'Antoine de Saint-Exupéry', 'Salamandra', '9788498381498', 5, 2, 2, 'Estante A-2'),
('Breve historia del tiempo', 'Stephen Hawking', 'Crítica', '9788484329213', 3, 1, 1, 'Estante C-1'),
('Don Quijote de la Mancha', 'Miguel de Cervantes', 'Real Academia Española', '9788420469690', 5, 2, 1, 'Estante A-3'),
('Introducción a la Programación', 'Varios Autores', 'McGraw Hill', '9786071509901', 6, 4, 4, 'Estante T-1');
