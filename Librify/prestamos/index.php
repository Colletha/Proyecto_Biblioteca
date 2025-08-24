<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

// Solo administradores y bibliotecarios pueden gestionar préstamos
if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Construir consulta con filtros
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.nombre LIKE :search OR u.apellido LIKE :search OR l.titulo LIKE :search OR u.carne LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($estado)) {
        $where_conditions[] = "p.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total 
                   FROM prestamos p 
                   JOIN usuarios u ON p.usuario_id = u.id 
                   JOIN libros l ON p.libro_id = l.id 
                   WHERE $where_clause";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener préstamos con paginación
    $query = "SELECT p.*, 
                     u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.carne,
                     l.titulo as libro_titulo, l.autor as libro_autor,
                     CASE 
                         WHEN p.estado = 'activo' AND p.fecha_devolucion_esperada < CURDATE() THEN 'vencido'
                         ELSE p.estado 
                     END as estado_real,
                     DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) as dias_retraso
              FROM prestamos p 
              JOIN usuarios u ON p.usuario_id = u.id 
              JOIN libros l ON p.libro_id = l.id 
              WHERE $where_clause 
              ORDER BY p.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar préstamos vencidos
    $db->exec("UPDATE prestamos SET estado = 'vencido' WHERE estado = 'activo' AND fecha_devolucion_esperada < CURDATE()");
    
} catch (Exception $e) {
    $error = "Error al cargar préstamos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos - Sistema Biblioteca</title>
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
                        <i class="fas fa-hand-holding"></i> Gestión de Préstamos
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="crear.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus"></i> Nuevo Préstamo
                        </a>
                        <a href="devolver.php" class="btn btn-success">
                            <i class="fas fa-undo"></i> Procesar Devolución
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php 
                        switch($_GET['success']) {
                            case 'created': echo 'Préstamo registrado exitosamente'; break;
                            case 'returned': echo 'Devolución procesada exitosamente'; break;
                            case 'renewed': echo 'Préstamo renovado exitosamente'; break;
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Usuario, libro o carné">
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?php echo $estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="vencido" <?php echo $estado === 'vencido' ? 'selected' : ''; ?>>Vencidos</option>
                                    <option value="devuelto" <?php echo $estado === 'devuelto' ? 'selected' : ''; ?>>Devueltos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Préstamos Activos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo'");
                                            echo $stmt->fetch()['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hand-holding fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Préstamos Vencidos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'vencido' OR (estado = 'activo' AND fecha_devolucion_esperada < CURDATE())");
                                            echo $stmt->fetch()['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Devueltos Hoy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'devuelto' AND DATE(fecha_devolucion_real) = CURDATE()");
                                            echo $stmt->fetch()['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-undo fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Préstamos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_records; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de préstamos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Lista de Préstamos 
                            <span class="badge bg-primary"><?php echo $total_records; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prestamos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-hand-holding fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron préstamos</h5>
                                <p class="text-muted">
                                    <?php echo !empty($search) || !empty($estado) ? 'Intenta ajustar los filtros de búsqueda' : 'Comienza registrando el primer préstamo'; ?>
                                </p>
                                <?php if (empty($search) && empty($estado)): ?>
                                    <a href="crear.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Registrar Primer Préstamo
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Libro</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Esperada</th>
                                            <th>Estado</th>
                                            <th>Días</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos as $prestamo): ?>
                                            <tr class="<?php echo $prestamo['estado_real'] === 'vencido' ? 'table-warning' : ''; ?>">
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($prestamo['usuario_nombre'] . ' ' . $prestamo['usuario_apellido']); ?></strong>
                                                        <?php if ($prestamo['carne']): ?>
                                                            <br><small class="text-muted">Carné: <?php echo htmlspecialchars($prestamo['carne']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($prestamo['libro_titulo']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($prestamo['libro_autor']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'bg-secondary';
                                                    $estado_texto = ucfirst($prestamo['estado_real']);
                                                    
                                                    switch($prestamo['estado_real']) {
                                                        case 'activo':
                                                            $badge_class = 'bg-info';
                                                            break;
                                                        case 'vencido':
                                                            $badge_class = 'bg-warning';
                                                            break;
                                                        case 'devuelto':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo $estado_texto; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($prestamo['estado_real'] === 'devuelto'): ?>
                                                        <small class="text-success">
                                                            <i class="fas fa-check"></i> Devuelto
                                                        </small>
                                                    <?php elseif ($prestamo['dias_retraso'] > 0): ?>
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> 
                                                            +<?php echo $prestamo['dias_retraso']; ?> días
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">
                                                            <?php echo abs($prestamo['dias_retraso']); ?> días restantes
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="ver.php?id=<?php echo $prestamo['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($prestamo['estado_real'] !== 'devuelto'): ?>
                                                            <a href="devolver.php?id=<?php echo $prestamo['id']; ?>" 
                                                               class="btn btn-sm btn-outline-success" title="Procesar devolución">
                                                                <i class="fas fa-undo"></i>
                                                            </a>
                                                            <a href="renovar.php?id=<?php echo $prestamo['id']; ?>" 
                                                               class="btn btn-sm btn-outline-warning" title="Renovar préstamo">
                                                                <i class="fas fa-sync"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Paginación de préstamos">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo $estado; ?>">
                                                    <i class="fas fa-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo $estado; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo $estado; ?>">
                                                    Siguiente <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
