<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$errors = [];
$success = false;

if ($_POST) {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $libro_id = intval($_POST['libro_id'] ?? 0);
    $dias_prestamo = intval($_POST['dias_prestamo'] ?? 7);
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validaciones
    if ($usuario_id <= 0) {
        $errors[] = 'Debe seleccionar un usuario';
    }
    if ($libro_id <= 0) {
        $errors[] = 'Debe seleccionar un libro';
    }
    if ($dias_prestamo < 1 || $dias_prestamo > 30) {
        $errors[] = 'Los días de préstamo deben estar entre 1 y 30';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar que el usuario existe y está activo
            $stmt = $db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE id = :id AND activo = 1");
            $stmt->bindParam(':id', $usuario_id);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                $errors[] = 'Usuario no encontrado o inactivo';
            }
            
            // Verificar que el libro existe y está disponible
            $stmt = $db->prepare("SELECT id, titulo, cantidad_disponible FROM libros WHERE id = :id AND activo = 1");
            $stmt->bindParam(':id', $libro_id);
            $stmt->execute();
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$libro) {
                $errors[] = 'Libro no encontrado o inactivo';
            } elseif ($libro['cantidad_disponible'] <= 0) {
                $errors[] = 'No hay ejemplares disponibles de este libro';
            }
            
            // Verificar que el usuario no tenga préstamos vencidos
            $stmt = $db->prepare("SELECT COUNT(*) as vencidos FROM prestamos WHERE usuario_id = :usuario_id AND (estado = 'vencido' OR (estado = 'activo' AND fecha_devolucion_esperada < CURDATE()))");
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();
            $vencidos = $stmt->fetch()['vencidos'];
            
            if ($vencidos > 0) {
                $errors[] = 'El usuario tiene préstamos vencidos. Debe devolverlos antes de realizar un nuevo préstamo';
            }
            
            if (empty($errors)) {
                $db->beginTransaction();
                
                try {
                    // Calcular fecha de devolución
                    $fecha_prestamo = date('Y-m-d');
                    $fecha_devolucion = date('Y-m-d', strtotime("+$dias_prestamo days"));
                    
                    // Insertar préstamo
                    $query = "INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_devolucion_esperada, observaciones) 
                             VALUES (:usuario_id, :libro_id, :fecha_prestamo, :fecha_devolucion, :observaciones)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':usuario_id', $usuario_id);
                    $stmt->bindParam(':libro_id', $libro_id);
                    $stmt->bindParam(':fecha_prestamo', $fecha_prestamo);
                    $stmt->bindParam(':fecha_devolucion', $fecha_devolucion);
                    $stmt->bindParam(':observaciones', $observaciones);
                    $stmt->execute();
                    
                    // Actualizar cantidad disponible del libro
                    $stmt = $db->prepare("UPDATE libros SET cantidad_disponible = cantidad_disponible - 1 WHERE id = :id");
                    $stmt->bindParam(':id', $libro_id);
                    $stmt->execute();
                    
                    $db->commit();
                    header('Location: index.php?success=created');
                    exit();
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = 'Error al procesar el préstamo: ' . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

// Obtener usuarios activos
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT id, nombre, apellido, carne FROM usuarios WHERE activo = 1 ORDER BY apellido, nombre");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

// Obtener libros disponibles
try {
    $stmt = $db->query("SELECT id, titulo, autor, cantidad_disponible FROM libros WHERE activo = 1 AND cantidad_disponible > 0 ORDER BY titulo");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $libros = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Préstamo - Sistema Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-plus"></i> Nuevo Préstamo
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron errores:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle"></i> Información del Préstamo
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="prestamo-form">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="usuario_id" class="form-label">
                                                <i class="fas fa-user"></i> Usuario *
                                            </label>
                                            <select class="form-select" id="usuario_id" name="usuario_id" required>
                                                <option value="">Seleccionar usuario</option>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <option value="<?php echo $usuario['id']; ?>"
                                                            <?php echo (isset($usuario_id) && $usuario_id == $usuario['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($usuario['apellido'] . ', ' . $usuario['nombre']); ?>
                                                        <?php if ($usuario['carne']): ?>
                                                            (<?php echo htmlspecialchars($usuario['carne']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="libro_id" class="form-label">
                                                <i class="fas fa-book"></i> Libro *
                                            </label>
                                            <select class="form-select" id="libro_id" name="libro_id" required>
                                                <option value="">Seleccionar libro</option>
                                                <?php foreach ($libros as $libro): ?>
                                                    <option value="<?php echo $libro['id']; ?>"
                                                            <?php echo (isset($libro_id) && $libro_id == $libro['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($libro['titulo']); ?> - <?php echo htmlspecialchars($libro['autor']); ?>
                                                        (<?php echo $libro['cantidad_disponible']; ?> disponibles)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="dias_prestamo" class="form-label">
                                                <i class="fas fa-calendar-alt"></i> Días de Préstamo *
                                            </label>
                                            <select class="form-select" id="dias_prestamo" name="dias_prestamo" required>
                                                <option value="7" <?php echo (isset($dias_prestamo) && $dias_prestamo == 7) ? 'selected' : 'selected'; ?>>7 días (1 semana)</option>
                                                <option value="14" <?php echo (isset($dias_prestamo) && $dias_prestamo == 14) ? 'selected' : ''; ?>>14 días (2 semanas)</option>
                                                <option value="21" <?php echo (isset($dias_prestamo) && $dias_prestamo == 21) ? 'selected' : ''; ?>>21 días (3 semanas)</option>
                                                <option value="30" <?php echo (isset($dias_prestamo) && $dias_prestamo == 30) ? 'selected' : ''; ?>>30 días (1 mes)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-calendar"></i> Fecha de Préstamo
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-check"></i> Fecha de Devolución
                                            </label>
                                            <input type="text" class="form-control" id="fecha_devolucion_display" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">
                                            <i class="fas fa-sticky-note"></i> Observaciones
                                        </label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                                  placeholder="Observaciones adicionales sobre el préstamo (opcional)"><?php echo htmlspecialchars($observaciones ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Registrar Préstamo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i> Información Importante
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>Todos los campos marcados con * son obligatorios</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>Solo se muestran libros con ejemplares disponibles</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>Los usuarios con préstamos vencidos no pueden solicitar nuevos libros</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <small>El período de préstamo estándar es de 7 días</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-clock"></i> Períodos de Préstamo
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-1"><strong>7 días:</strong> Préstamo estándar</li>
                                    <li class="mb-1"><strong>14 días:</strong> Libros de consulta</li>
                                    <li class="mb-1"><strong>21 días:</strong> Proyectos especiales</li>
                                    <li class="mb-1"><strong>30 días:</strong> Investigación académica</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Calcular fecha de devolución automáticamente
        function calcularFechaDevolucion() {
            const dias = parseInt(document.getElementById('dias_prestamo').value);
            const fechaDevolucion = new Date();
            fechaDevolucion.setDate(fechaDevolucion.getDate() + dias);
            
            const opciones = { year: 'numeric', month: '2-digit', day: '2-digit' };
            document.getElementById('fecha_devolucion_display').value = fechaDevolucion.toLocaleDateString('es-ES', opciones);
        }
        
        // Calcular fecha inicial
        calcularFechaDevolucion();
        
        // Recalcular cuando cambie los días
        document.getElementById('dias_prestamo').addEventListener('change', calcularFechaDevolucion);
        
        // Validación del formulario
        document.getElementById('prestamo-form').addEventListener('submit', function(e) {
            if (!validateForm('prestamo-form')) {
                e.preventDefault();
                showNotification('Por favor complete todos los campos obligatorios', 'warning');
            }
        });
    </script>
</body>
</html>
