<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$id = intval($_GET['id'] ?? 0);
$errors = [];
$prestamo = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($id > 0) {
        // Obtener préstamo específico
        $stmt = $db->prepare("SELECT p.*, 
                             u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.carne,
                             l.titulo as libro_titulo, l.autor as libro_autor,
                             DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) as dias_retraso
                             FROM prestamos p 
                             JOIN usuarios u ON p.usuario_id = u.id 
                             JOIN libros l ON p.libro_id = l.id 
                             WHERE p.id = :id AND p.estado IN ('activo', 'vencido')");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prestamo) {
            header('Location: index.php?error=not_found');
            exit();
        }
    }
    
} catch (Exception $e) {
    $error = "Error al cargar préstamo: " . $e->getMessage();
}

if ($_POST) {
    $prestamo_id = intval($_POST['prestamo_id'] ?? 0);
    $observaciones_devolucion = trim($_POST['observaciones_devolucion'] ?? '');
    $estado_libro = $_POST['estado_libro'] ?? 'bueno';
    
    if ($prestamo_id <= 0) {
        $errors[] = 'Debe seleccionar un préstamo válido';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Obtener información del préstamo
            $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id AND estado IN ('activo', 'vencido')");
            $stmt->bindParam(':id', $prestamo_id);
            $stmt->execute();
            $prestamo_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prestamo_data) {
                $errors[] = 'Préstamo no encontrado o ya devuelto';
            } else {
                // Actualizar préstamo como devuelto
                $observaciones_final = $prestamo_data['observaciones'];
                if (!empty($observaciones_devolucion)) {
                    $observaciones_final .= "\n\nDevolución: " . $observaciones_devolucion;
                }
                
                $stmt = $db->prepare("UPDATE prestamos SET 
                                     estado = 'devuelto', 
                                     fecha_devolucion_real = CURDATE(),
                                     observaciones = :observaciones,
                                     updated_at = CURRENT_TIMESTAMP
                                     WHERE id = :id");
                $stmt->bindParam(':observaciones', $observaciones_final);
                $stmt->bindParam(':id', $prestamo_id);
                $stmt->execute();
                
                // Incrementar cantidad disponible del libro
                $stmt = $db->prepare("UPDATE libros SET cantidad_disponible = cantidad_disponible + 1 WHERE id = :id");
                $stmt->bindParam(':id', $prestamo_data['libro_id']);
                $stmt->execute();
                
                // Si hay retraso, crear multa (opcional)
                $dias_retraso = max(0, (strtotime(date('Y-m-d')) - strtotime($prestamo_data['fecha_devolucion_esperada'])) / (60 * 60 * 24));
                if ($dias_retraso > 0) {
                    $monto_multa = $dias_retraso * 1.00; // $1 por día de retraso
                    $stmt = $db->prepare("INSERT INTO multas (prestamo_id, monto, dias_retraso) VALUES (:prestamo_id, :monto, :dias_retraso)");
                    $stmt->bindParam(':prestamo_id', $prestamo_id);
                    $stmt->bindParam(':monto', $monto_multa);
                    $stmt->bindParam(':dias_retraso', $dias_retraso);
                    $stmt->execute();
                }
                
                $db->commit();
                header('Location: index.php?success=returned');
                exit();
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error al procesar la devolución: ' . $e->getMessage();
        }
    }
}

// Obtener préstamos activos si no se especificó uno
if (!$prestamo) {
    try {
        $stmt = $db->query("SELECT p.*, 
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.carne,
                           l.titulo as libro_titulo, l.autor as libro_autor,
                           DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) as dias_retraso
                           FROM prestamos p 
                           JOIN usuarios u ON p.usuario_id = u.id 
                           JOIN libros l ON p.libro_id = l.id 
                           WHERE p.estado IN ('activo', 'vencido')
                           ORDER BY p.fecha_devolucion_esperada ASC");
        $prestamos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $prestamos_activos = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Devolución - Sistema Biblioteca</title>
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
                        <i class="fas fa-undo"></i> Procesar Devolución
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
                
                <?php if ($prestamo): ?>
                    <!-- Formulario para préstamo específico -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i> Información del Préstamo
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user"></i> Usuario</h6>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($prestamo['usuario_nombre'] . ' ' . $prestamo['usuario_apellido']); ?></strong></p>
                                            <?php if ($prestamo['carne']): ?>
                                                <p class="text-muted mb-0">Carné: <?php echo htmlspecialchars($prestamo['carne']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-book"></i> Libro</h6>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($prestamo['libro_titulo']); ?></strong></p>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($prestamo['libro_autor']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-calendar"></i> Fecha Préstamo</h6>
                                            <p><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-calendar-check"></i> Fecha Esperada</h6>
                                            <p><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-clock"></i> Estado</h6>
                                            <?php if ($prestamo['dias_retraso'] > 0): ?>
                                                <p class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    Vencido (+<?php echo $prestamo['dias_retraso']; ?> días)
                                                </p>
                                            <?php else: ?>
                                                <p class="text-success">
                                                    <i class="fas fa-check"></i> A tiempo
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" id="devolucion-form">
                                        <input type="hidden" name="prestamo_id" value="<?php echo $prestamo['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="estado_libro" class="form-label">
                                                <i class="fas fa-clipboard-check"></i> Estado del Libro
                                            </label>
                                            <select class="form-select" id="estado_libro" name="estado_libro">
                                                <option value="bueno">Buen estado</option>
                                                <option value="regular">Estado regular</option>
                                                <option value="malo">Mal estado</option>
                                                <option value="perdido">Perdido</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="observaciones_devolucion" class="form-label">
                                                <i class="fas fa-sticky-note"></i> Observaciones de Devolución
                                            </label>
                                            <textarea class="form-control" id="observaciones_devolucion" name="observaciones_devolucion" rows="3"
                                                      placeholder="Observaciones sobre el estado del libro o la devolución"></textarea>
                                        </div>
                                        
                                        <?php if ($prestamo['dias_retraso'] > 0): ?>
                                            <div class="alert alert-warning" role="alert">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Atención:</strong> Este préstamo tiene <?php echo $prestamo['dias_retraso']; ?> días de retraso. 
                                                Se generará automáticamente una multa de $<?php echo number_format($prestamo['dias_retraso'] * 1.00, 2); ?>.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="index.php" class="btn btn-secondary me-md-2">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i> Procesar Devolución
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
                                        <i class="fas fa-info-circle"></i> Información Adicional
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($prestamo['observaciones']): ?>
                                        <h6>Observaciones del Préstamo:</h6>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($prestamo['observaciones'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <h6>Proceso de Devolución:</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-1"><i class="fas fa-check text-success"></i> Verificar estado del libro</li>
                                        <li class="mb-1"><i class="fas fa-check text-success"></i> Registrar observaciones</li>
                                        <li class="mb-1"><i class="fas fa-check text-success"></i> Actualizar disponibilidad</li>
                                        <li class="mb-1"><i class="fas fa-check text-success"></i> Calcular multas si aplica</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Lista de préstamos activos para seleccionar -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Préstamos Activos para Devolución
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($prestamos_activos)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-muted">No hay préstamos activos</h5>
                                    <p class="text-muted">Todos los libros han sido devueltos.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Libro</th>
                                                <th>Fecha Esperada</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prestamos_activos as $p): ?>
                                                <tr class="<?php echo $p['dias_retraso'] > 0 ? 'table-warning' : ''; ?>">
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($p['usuario_nombre'] . ' ' . $p['usuario_apellido']); ?></strong>
                                                        <?php if ($p['carne']): ?>
                                                            <br><small class="text-muted">Carné: <?php echo htmlspecialchars($p['carne']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($p['libro_titulo']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($p['libro_autor']); ?></small>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])); ?></td>
                                                    <td>
                                                        <?php if ($p['dias_retraso'] > 0): ?>
                                                            <span class="badge bg-warning">
                                                                Vencido (+<?php echo $p['dias_retraso']; ?> días)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info">Activo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="devolver.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-undo"></i> Devolver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
