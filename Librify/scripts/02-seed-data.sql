-- Script para insertar datos de prueba en el sistema de biblioteca

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, carne, correo, password, rol_id) VALUES 
('Admin', 'Sistema', 'ADMIN001', 'admin@biblioteca.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insertar bibliotecarios
INSERT INTO usuarios (nombre, apellido, carne, correo, password, rol_id) VALUES 
('María', 'González', 'BIB001', 'maria.gonzalez@biblioteca.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Carlos', 'Rodríguez', 'BIB002', 'carlos.rodriguez@biblioteca.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Insertar alumnos de ejemplo
INSERT INTO usuarios (nombre, apellido, carne, correo, password, rol_id) VALUES 
('Ana', 'López', '2024001', 'ana.lopez@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Pedro', 'Martínez', '2024002', 'pedro.martinez@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Laura', 'Hernández', '2024003', 'laura.hernandez@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Miguel', 'Torres', '2024004', 'miguel.torres@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- Insertar libros de ejemplo
INSERT INTO libros (titulo, autor, editorial, isbn, categoria_id, cantidad_total, cantidad_disponible, ubicacion, descripcion, fecha_publicacion) VALUES 
('Cien años de soledad', 'Gabriel García Márquez', 'Editorial Sudamericana', '978-950-07-0001-1', 5, 3, 2, 'A-001', 'Obra maestra del realismo mágico', '1967-06-05'),
('El principito', 'Antoine de Saint-Exupéry', 'Reynal & Hitchcock', '978-0-15-601219-5', 1, 5, 4, 'A-002', 'Clásico de la literatura infantil', '1943-04-06'),
('1984', 'George Orwell', 'Secker & Warburg', '978-0-452-28423-4', 1, 2, 1, 'A-003', 'Distopía clásica sobre el totalitarismo', '1949-06-08'),
('Sapiens', 'Yuval Noah Harari', 'Debate', '978-84-9992-275-0', 2, 4, 3, 'B-001', 'De animales a dioses: Breve historia de la humanidad', '2011-01-01'),
('El origen de las especies', 'Charles Darwin', 'John Murray', '978-0-14-043205-1', 3, 2, 2, 'C-001', 'Teoría de la evolución por selección natural', '1859-11-24'),
('Steve Jobs', 'Walter Isaacson', 'Simon & Schuster', '978-1-4516-4853-9', 4, 3, 2, 'D-001', 'Biografía del cofundador de Apple', '2011-10-24'),
('Clean Code', 'Robert C. Martin', 'Prentice Hall', '978-0-13-235088-4', 6, 2, 1, 'E-001', 'Manual de estilo para el desarrollo ágil de software', '2008-08-01'),
('El arte de la guerra', 'Sun Tzu', 'Bamboo Books', '978-0-19-518999-1', 4, 4, 4, 'F-001', 'Tratado militar clásico chino', '500-01-01');

-- Insertar algunos préstamos de ejemplo
INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_devolucion_esperada, estado) VALUES 
(4, 1, '2024-01-15', '2024-01-29', 'activo'),
(5, 3, '2024-01-10', '2024-01-24', 'activo'),
(6, 2, '2024-01-05', '2024-01-19', 'devuelto'),
(7, 4, '2024-01-20', '2024-02-03', 'activo');

-- Actualizar fecha de devolución real para el préstamo devuelto
UPDATE prestamos SET fecha_devolucion_real = '2024-01-18' WHERE id = 3;

-- Actualizar cantidad disponible de libros prestados
UPDATE libros SET cantidad_disponible = cantidad_disponible - 1 WHERE id IN (1, 3, 4);
