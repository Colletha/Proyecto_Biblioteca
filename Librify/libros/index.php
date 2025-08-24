<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAuth();

// Solo administradores y bibliotecarios pueden gestionar libros
if (!hasRole('administrador') && !hasRole('bibliotecario')) {
    header('Location: ../dashboard.php?error=no_permission');
    exit();
}

$search = $_GET['search'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener categorías para el filtro
    $stmt = $db->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir consulta con filtros
    $where_conditions = ["l.activo = 1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(l.titulo LIKE :search OR l.autor LIKE :search OR l.isbn LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $where_conditions[] = "l.categoria_id = :categoria";
        $params[':categoria'] = $categoria;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM libros l WHERE $where_clause";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener libros con paginación
    $query = "SELECT l.*, c.nombre as categoria_nombre 
              FROM libros l 
              LEFT JOIN categorias c ON l.categoria_id = c.id 
              WHERE $where_clause 
              ORDER BY l.titulo 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar libros: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Libros - Sistema Biblioteca</title>
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
                        <i class="fas fa-book"></i> Gestión de Libros
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="crear.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Libro
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
                            case 'created': echo 'Libro agregado exitosamente'; break;
                            case 'updated': echo 'Libro actualizado exitosamente'; break;
                            case 'deleted': echo 'Libro eliminado exitosamente'; break;
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Título, autor o ISBN">
                            </div>
                            <div class="col-md-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
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
                
                <!-- Tabla de libros -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Lista de Libros 
                            <span class="badge bg-primary"><?php echo $total_records; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($libros)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron libros</h5>
                                <p class="text-muted">
                                    <?php echo !empty($search) || !empty($categoria) ? 'Intenta ajustar los filtros de búsqueda' : 'Comienza agregando tu primer libro'; ?>
                                </p>
                                <?php if (empty($search) && empty($categoria)): ?>
                                    <a href="crear.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar Primer Libro
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Categoría</th>
                                            <th>ISBN</th>
                                            <th>Disponibles</th>
                                            <th>Total</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($libros as $libro): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($libro['titulo']); ?></strong>
                                                    <?php if ($libro['cantidad_disponible'] == 0): ?>
                                                        <span class="badge bg-danger ms-2">Sin stock</span>
                                                    <?php elseif ($libro['cantidad_disponible'] <= 2): ?>
                                                        <span class="badge bg-warning ms-2">Poco stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                                                <td>
                                                    <?php if ($libro['categoria_nombre']): ?>
                                                        <span class="badge bg-info">
                                                            <?php echo htmlspecialchars($libro['categoria_nombre']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin categoría</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($libro['isbn'] ?? 'N/A'); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $libro['cantidad_disponible'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $libro['cantidad_disponible']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $libro['cantidad_total']; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="ver.php?id=<?php echo $libro['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="editar.php?id=<?php echo $libro['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="eliminar.php?id=<?php echo $libro['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger btn-delete" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Paginación de libros">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria; ?>">
                                                    <i class="fas fa-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria; ?>">
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
