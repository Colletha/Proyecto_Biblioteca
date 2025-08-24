<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

// Solo administradores pueden gestionar usuarios
if (!hasRole('administrador')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$search = $_GET['search'] ?? '';
$rol = $_GET['rol'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener roles para el filtro
    $stmt = $db->query("SELECT * FROM roles ORDER BY nombre");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir consulta con filtros
    $where_conditions = ["u.activo = 1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.nombre LIKE :search OR u.apellido LIKE :search OR u.email LIKE :search OR u.carne LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($rol)) {
        $where_conditions[] = "u.rol_id = :rol";
        $params[':rol'] = $rol;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM usuarios u WHERE $where_clause";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener usuarios con paginación
    $query = "SELECT u.*, r.nombre as rol_nombre 
              FROM usuarios u 
              JOIN roles r ON u.rol_id = r.id 
              WHERE $where_clause 
              ORDER BY u.apellido, u.nombre 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema Biblioteca</title>
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
                        <i class="fas fa-users"></i> Gestión de Usuarios
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="crear.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Agregar Usuario
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
                            case 'created': echo 'Usuario agregado exitosamente'; break;
                            case 'updated': echo 'Usuario actualizado exitosamente'; break;
                            case 'deleted': echo 'Usuario eliminado exitosamente'; break;
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-5">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nombre, apellido, email o carné">
                            </div>
                            <div class="col-md-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="">Todos los roles</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" 
                                                <?php echo $rol == $r['id'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($r['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Lista de Usuarios 
                            <span class="badge bg-primary"><?php echo $total_records; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usuarios)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron usuarios</h5>
                                <p class="text-muted">
                                    <?php echo !empty($search) || !empty($rol) ? 'Intenta ajustar los filtros de búsqueda' : 'Comienza agregando usuarios al sistema'; ?>
                                </p>
                                <?php if (empty($search) && empty($rol)): ?>
                                    <a href="crear.php" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Agregar Primer Usuario
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Carné</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-2">
                                                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <?php if ($usuario['carne']): ?>
                                                        <code><?php echo htmlspecialchars($usuario['carne']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $usuario['rol_nombre'] === 'administrador' ? 'bg-danger' : 
                                                            ($usuario['rol_nombre'] === 'bibliotecario' ? 'bg-warning' : 'bg-info'); 
                                                    ?>">
                                                        <?php echo ucfirst($usuario['rol_nombre']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="ver.php?id=<?php echo $usuario['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="editar.php?id=<?php echo $usuario['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                            <a href="eliminar.php?id=<?php echo $usuario['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger btn-delete" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
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
                                <nav aria-label="Paginación de usuarios">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo $rol; ?>">
                                                    <i class="fas fa-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo $rol; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo $rol; ?>">
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
    
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
